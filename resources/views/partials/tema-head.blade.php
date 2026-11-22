{{-- Aplica el tema (claro/oscuro) antes de pintar la página para evitar un destello. --}}
<meta name="color-scheme" content="light dark">
<script>
(function () {
    var t = null;
    try { t = localStorage.getItem('tema'); } catch (e) {}
    if (t !== 'dark' && t !== 'light') {
        t = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    document.documentElement.setAttribute('data-theme', t);
    document.documentElement.setAttribute('data-bs-theme', t);
})();
</script>
<script src="{{ asset('js/tema.js') }}?v={{ filemtime(public_path('js/tema.js')) }}" defer></script>
