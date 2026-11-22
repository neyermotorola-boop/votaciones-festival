<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.tema-head')
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
    <title>@yield('titulo', 'Administración') · Votaciones</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
</head>
<body class="admin-body">
<div class="admin-top">
    <a class="brand" href="{{ route('admin.panel') }}"><img src="{{ asset('img/emblema-festival.png') }}" alt="" class="brand-emblem">Administración</a>
    <div class="d-flex align-items-center gap-2">
        @include('partials.tema-toggle')
        <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-glass" aria-label="Cerrar sesión"><i class="bi bi-box-arrow-right"></i></button></form>
    </div>
</div>
<div class="admin-shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('admin.panel') }}">
            <img src="{{ asset('img/logo-festival.png') }}" alt="Fundación Festival de la Leyenda Vallenata" class="sidebar-logo">
            <small>Votaciones · Panel de administración</small>
        </a>
        <div class="nav-label">General</div>
        <nav class="nav flex-column gap-1">
            <a class="nav-link {{ request()->routeIs('admin.panel') ? 'active' : '' }}" href="{{ route('admin.panel') }}"><i class="bi bi-grid-1x2"></i>Panel</a>
            @if(auth()->user()->puede('ediciones'))
            <a class="nav-link {{ request()->routeIs('admin.ediciones.*') ? 'active' : '' }}" href="{{ route('admin.ediciones.index') }}"><i class="bi bi-calendar-event"></i>Ediciones</a>
            @endif
            @if(auth()->user()->puede('categorias'))
            <a class="nav-link {{ request()->routeIs('admin.categorias.*') ? 'active' : '' }}" href="{{ route('admin.categorias.index') }}"><i class="bi bi-collection"></i>Categorías</a>
            @endif
            @if(auth()->user()->puede('votaciones'))
            <a class="nav-link {{ request()->routeIs('admin.votaciones.*') ? 'active' : '' }}" href="{{ route('admin.votaciones.index') }}"><i class="bi bi-check2-square"></i>Votaciones</a>
            @endif
            @if(auth()->user()->puede('resultados'))
            <a class="nav-link {{ request()->routeIs('admin.votos') ? 'active' : '' }}" href="{{ route('admin.votos') }}"><i class="bi bi-list-check"></i>Votos</a>
            <a class="nav-link {{ request()->routeIs('admin.graficos') ? 'active' : '' }}" href="{{ route('admin.graficos') }}"><i class="bi bi-bar-chart-line"></i>Gráficos</a>
            @endif
        </nav>
        @if(auth()->user()->puede('reportes'))
        <div class="nav-label mt-3">Informes</div>
        <nav class="nav flex-column gap-1">
            <a class="nav-link {{ request()->routeIs('admin.reportes*') ? 'active' : '' }}" href="{{ route('admin.reportes') }}"><i class="bi bi-file-earmark-excel"></i>Reportes Excel</a>
        </nav>
        @endif
        @if(auth()->user()->esAdmin())
        <div class="nav-label mt-3">Seguridad</div>
        <nav class="nav flex-column gap-1">
            <a class="nav-link {{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}" href="{{ route('admin.usuarios.index') }}"><i class="bi bi-people"></i>Usuarios y permisos</a>
        </nav>
        @endif
        <div class="side-foot">
            <div class="theme-toggle-row"><span data-tema-texto>Tema claro</span>@include('partials.tema-toggle')</div>
            <a class="nav-link" href="{{ route('home') }}"><i class="bi bi-box-arrow-up-right"></i>Ver sitio público</a>
            <form method="post" action="{{ route('logout') }}">@csrf
                <button class="nav-link w-100 border-0 bg-transparent text-start"><i class="bi bi-box-arrow-right"></i>Cerrar sesión</button></form>
        </div>
    </aside>
    <main class="admin-main">
        @include('partials.flash')
        @yield('contenido')
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
