<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Acceso') · Festival de la Leyenda Vallenata</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body class="auth-body">
<div class="auth-bg" style="background-image:url('{{ asset('img/vallenato.png') }}')"></div>
<div class="auth-shade"></div>

<div class="auth-stage">
    <div class="auth-glass">
        <figure class="auth-photo" style="background-image:url('{{ asset('img/vallenato.png') }}')">
            <figcaption>
                <span class="auth-eyebrow">Festival de la Leyenda Vallenata</span>
                <h2>Tu voz hace parte de la fiesta.</h2>
                <p>Vota por tus favoritos: un voto por persona, resultados transparentes.</p>
            </figcaption>
        </figure>

        <main class="auth-form-side">
            <a href="{{ route('home') }}" class="auth-logo-mobile"><span class="brand-mark"><i class="bi bi-music-note-beamed"></i></span> Votaciones <span>Leyenda Vallenata</span></a>
            @if(session('ok'))
                <div class="alert alert-success py-2" role="alert"><i class="bi bi-check-circle-fill"></i><div>{{ session('ok') }}</div></div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger py-2" role="alert"><i class="bi bi-exclamation-triangle-fill"></i><div>{{ session('error') }}</div></div>
            @endif
            @yield('contenido')
            <p class="text-center mt-4 mb-0"><a class="auth-back" href="{{ route('home') }}"><i class="bi bi-arrow-left me-1"></i>Volver al inicio</a></p>
        </main>
    </div>
    <p class="auth-foot"><i class="bi bi-shield-lock me-1"></i>Conexión segura · Fundación Festival de la Leyenda Vallenata © {{ date('Y') }}</p>
</div>
<script>
document.querySelectorAll('[data-toggle-pass]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = document.getElementById(btn.dataset.togglePass);
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.textContent = show ? 'Ocultar' : 'Mostrar';
        btn.setAttribute('aria-pressed', show);
    });
});
document.querySelectorAll('form[data-loading]').forEach(function (form) {
    form.addEventListener('submit', function () {
        var b = form.querySelector('button[type=submit]');
        if (b && form.checkValidity()) { b.disabled = true; b.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + b.dataset.loading; }
    });
});
</script>
</body>
</html>
