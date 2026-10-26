<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e($title ?? 'Gestor de fiestas | Bigbitbyte'); ?></title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="<?php echo e(route('eventos.dashboard')); ?>" aria-label="Bigbitbyte, inicio">
            <img class="brand-logo" src="<?php echo e(asset('images/bigbitbyte-logo.jpeg')); ?>" alt="Logo Bigbitbyte">
            <span>Bigbitbyte</span>
        </a>
    </header>
    <main class="page-shell">
        <?php echo $__env->yieldContent('content'); ?>
    </main>
    <div class="toast-region" id="toast-region" aria-live="polite" aria-atomic="true">
        <?php if(session('status')): ?>
            <div class="toast" role="status">
                <?php echo e(session('status')); ?>

                <button type="button" class="toast__close" aria-label="Cerrar notificación" onclick="this.parentElement.remove()">×</button>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php /**PATH C:\xammp\htdocs\laravel\proyecto\resources\views/layouts/app.blade.php ENDPATH**/ ?>