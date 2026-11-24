<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Package;
use App\Models\Provider;
use App\Models\User;
use App\Services\BudgetService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\InvalidWebhookSignatureException;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Webhook\WebhookSignatureValidator;
use Throwable;

class PaymentController extends Controller
{
    /**
     * Crea una preferencia de pago Sandbox para los proveedores seleccionados.
     */
    public function createPreference(Request $request, BudgetService $budgetService): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $validated = $request->validate([
            'event_id' => ['required', 'integer'],
            'package_id' => ['required', 'integer'],
            'provider_ids' => ['required', 'array', 'min:1'],
            'provider_ids.*' => ['required', 'integer', 'distinct', 'exists:providers,id'],
        ]);

        $event = $user->events()->findOrFail($validated['event_id']);
        abort_unless($event instanceof Event, 404);

        $package = $budgetService->suggestPackage($event)->firstWhere('id', (int) $validated['package_id']);
        abort_unless($package instanceof Package, 404);

        $availableProviders = $package->providers->keyBy('id');
        $selectedProviders = new Collection;

        foreach (array_map('intval', $validated['provider_ids']) as $providerId) {
            $provider = $availableProviders->get($providerId);

            if (! $provider instanceof Provider) {
                throw ValidationException::withMessages([
                    'provider_ids' => 'Selecciona únicamente proveedores incluidos en el paquete.',
                ]);
            }

            $selectedProviders->push($provider);
        }

        $referencePriceInCents = $selectedProviders->sum(
            fn (Provider $provider): int => (int) round((float) $provider->pivot->price * 100),
        );
        $availableBudgetInCents = (int) round($budgetService->calculateAvailableBudget($event) * 100);

        if ($referencePriceInCents > $availableBudgetInCents) {
            throw ValidationException::withMessages([
                'provider_ids' => 'El precio de los proveedores seleccionados supera el presupuesto disponible.',
            ]);
        }

        $paymentReference = (string) Str::uuid();
        $eventPackage = DB::transaction(function () use ($event, $package, $selectedProviders, $referencePriceInCents, $paymentReference): Package {
            $eventPackage = $event->packages()->create([
                'category' => $event->category,
                'name' => $package->name,
                'description' => $package->description,
                'estimated_price' => $referencePriceInCents / 100,
                'is_paid' => false,
                'payment_reference' => $paymentReference,
            ]);
            $providerPrices = [];

            foreach ($selectedProviders as $provider) {
                $providerPrices[$provider->id] = [
                    'price' => $provider->pivot->price,
                ];
            }

            $eventPackage->providers()->sync($providerPrices);

            return $eventPackage;
        });

        if (config('services.payments.simulation')) {
            return response()->json([
                'init_point' => route('payments.simulate', [
                    'type' => 'package',
                    'id' => $eventPackage->id,
                ]),
                'reference_price' => $referencePriceInCents / 100,
                'charge_amount' => 1.00,
            ]);
        }

        $accessToken = (string) config('services.mercadopago.access_token');
        $publicKey = (string) config('services.mercadopago.public_key');
        $notificationUrl = (string) config('services.mercadopago.webhook_url');
        $webhookSecret = (string) config('services.mercadopago.webhook_secret');

        if (! str_starts_with($accessToken, 'TEST-')
            || ! str_starts_with($publicKey, 'TEST-')
            || $webhookSecret === ''
            || parse_url($notificationUrl, PHP_URL_SCHEME) !== 'https'
            || filter_var($notificationUrl, FILTER_VALIDATE_URL) === false) {
            $eventPackage->delete();

            return response()->json([
                'message' => 'Configura credenciales TEST, la clave de firma y una URL HTTPS pública para el webhook de MercadoPago.',
            ], 503);
        }

        try {
            MercadoPagoConfig::setAccessToken($accessToken);

            $preference = (new PreferenceClient)->create([
                'items' => [[
                    'id' => (string) $eventPackage->id,
                    'title' => $package->name,
                    'description' => sprintf(
                        'Precio de referencia: $%.2f MXN. Cobro Sandbox para auditoría: $1.00 MXN.',
                        $referencePriceInCents / 100,
                    ),
                    'quantity' => 1,
                    'currency_id' => 'MXN',
                    'unit_price' => 1.00,
                ]],
                'external_reference' => $paymentReference,
                'notification_url' => $notificationUrl,
                'metadata' => [
                    'event_id' => $event->id,
                    'package_id' => $eventPackage->id,
                    'catalog_package_id' => $package->id,
                    'reference_price' => number_format($referencePriceInCents / 100, 2, '.', ''),
                ],
            ]);

            $initPoint = $preference->sandbox_init_point;

            if (! is_string($initPoint) || filter_var($initPoint, FILTER_VALIDATE_URL) === false) {
                throw new \RuntimeException('MercadoPago no devolvió una URL de checkout Sandbox.');
            }

            $eventPackage->update(['mercadopago_preference_id' => $preference->id]);

            return response()->json([
                'init_point' => $initPoint,
                'reference_price' => $referencePriceInCents / 100,
                'charge_amount' => 1.00,
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $eventPackage->delete();

            return response()->json([
                'message' => 'No se pudo iniciar el pago de prueba. Inténtalo de nuevo.',
            ], 502);
        }
    }

    /**
     * Confirma con MercadoPago el pago notificado antes de marcar el paquete como pagado.
     */
    public function webhook(Request $request): JsonResponse
    {
        $topic = $request->input('type') ?? $request->query('topic') ?? $request->query('type');

        if ($topic !== 'payment') {
            return response()->json(['received' => true]);
        }

        $signatureDataId = $request->query('data.id') ?? $request->query('data_id');
        $bodyPaymentId = $request->input('data.id');
        $paymentId = $signatureDataId ?? $request->query('id') ?? $bodyPaymentId;

        if (! is_string($paymentId) && ! is_int($paymentId)) {
            return response()->json(['received' => true, 'processed' => false]);
        }

        $paymentId = (string) $paymentId;

        if (! ctype_digit($paymentId)
            || ($bodyPaymentId !== null && (string) $bodyPaymentId !== $paymentId)
            || ! is_string($signatureDataId)) {
            return response()->json(['received' => true, 'processed' => false]);
        }

        $webhookSecret = (string) config('services.mercadopago.webhook_secret');
        $accessToken = (string) config('services.mercadopago.access_token');

        if ($webhookSecret === '' || ! str_starts_with($accessToken, 'TEST-')) {
            return response()->json(['message' => 'MercadoPago Sandbox no está configurado.'], 503);
        }

        try {
            WebhookSignatureValidator::validate(
                $request->header('x-signature'),
                $request->header('x-request-id'),
                $signatureDataId,
                $webhookSecret,
            );
        } catch (InvalidWebhookSignatureException) {
            return response()->json(['message' => 'Notificación no autorizada.'], 401);
        }

        try {
            MercadoPagoConfig::setAccessToken($accessToken);
            $payment = (new PaymentClient)->get((int) $paymentId);
        } catch (MPApiException $exception) {
            if ($exception->getStatusCode() < 500) {
                return response()->json(['received' => true, 'processed' => false]);
            }

            report($exception);

            return response()->json(['message' => 'No se pudo verificar el pago con MercadoPago.'], 503);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'No se pudo verificar el pago con MercadoPago.'], 503);
        }

        $amountInCents = (int) round((float) ($payment->transaction_amount ?? 0) * 100);

        if ($payment->status !== 'approved'
            || $payment->live_mode !== false
            || $payment->currency_id !== 'MXN'
            || $amountInCents !== 100
            || ! is_string($payment->external_reference)) {
            return response()->json(['received' => true, 'processed' => false]);
        }

        $updated = DB::transaction(function () use ($payment): bool {
            $eventPackage = Package::query()
                ->where('payment_reference', $payment->external_reference)
                ->lockForUpdate()
                ->first();

            if (! $eventPackage instanceof Package
                || ($eventPackage->mercadopago_payment_id !== null
                    && $eventPackage->mercadopago_payment_id !== (string) $payment->id)) {
                return false;
            }

            if (! $eventPackage->is_paid) {
                $eventPackage->update([
                    'is_paid' => true,
                    'mercadopago_payment_id' => (string) $payment->id,
                ]);
            }

            return true;
        });

        return response()->json(['received' => true, 'processed' => $updated]);
    }
}
