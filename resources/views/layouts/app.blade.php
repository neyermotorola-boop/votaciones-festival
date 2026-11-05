<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Votaciones') · Festival de la Leyenda Vallenata</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body class="page-body">
<nav class="navbar navbar-expand-md navbar-dark navbar-fest sticky-top">
    <div class="container">
        <a class="navbar-brand" href="{{ route('home') }}">
            <span class="brand-mark"><i class="bi bi-music-note-beamed"></i></span>
            <span>Votaciones <small>Leyenda Vallenata</small></span>
        </a>
        <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav" aria-label="Menú"><span class="navbar-toggler-icon"></span></button>
        <div id="nav" class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto align-items-md-center gap-md-1 pt-3 pt-md-0">
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Inicio</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('ediciones', 'edicion') ? 'active' : '' }}" href="{{ route('ediciones') }}">Ediciones</a></li>
                @auth
                    @if(auth()->user()->esAdmin())
                        <li class="nav-item ms-md-2"><a class="btn btn-sm btn-gold" href="{{ route('admin.panel') }}"><i class="bi bi-speedometer2 me-1"></i>Administración</a></li>
                    @endif
                    <li class="nav-item ms-md-2">
                        <span class="user-chip"><span class="av">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>{{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}</span>
                    </li>
                    <li class="nav-item">
                        <form method="post" action="{{ route('logout') }}">@csrf
                            <button class="btn btn-sm btn-glass"><i class="bi bi-box-arrow-right me-1"></i>Salir</button></form>
                    </li>
                @else
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Ingresar</a></li>
                    <li class="nav-item ms-md-1"><a class="btn btn-sm btn-gold" href="{{ route('registro') }}">Registrarme</a></li>
                @endauth
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4 py-md-5" style="max-width:1040px">
    @include('partials.flash')
    @yield('contenido')
</main>

<footer class="footer-f">
    <div class="container d-flex flex-column flex-md-row justify-content-between gap-2" style="max-width:1040px">
        <span><b>Fundación Festival de la Leyenda Vallenata</b> · Sistema de votaciones</span>
        <span>Proyecto académico · Desarrollo Web</span>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
