<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Gestor de fiestas | Bigbitbyte' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header class="site-header">
        <a class="brand" href="{{ route('eventos.dashboard') }}" aria-label="Bigbitbyte, inicio">
            <img class="brand-logo" src="{{ asset('images/bigbitbyte-logo.jpeg') }}" alt="Logo Bigbitbyte">
            <span>Bigbitbyte</span>
        </a>
    </header>
    <main class="page-shell">
        @yield('content')
    </main>
    <div class="toast-region" id="toast-region" aria-live="polite" aria-atomic="true"></div>
</body>
</html>
