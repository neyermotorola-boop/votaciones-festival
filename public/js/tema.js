/* Tema claro / oscuro: se guarda en el navegador (localStorage). */
(function () {
    var raiz = document.documentElement;

    function actual() {
        return raiz.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    }

    function ajustarGraficos(tema) {
        if (!window.Chart) return;
        var texto = tema === 'dark' ? '#e3d6d6' : '#5f5656';
        var rejilla = tema === 'dark' ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.08)';
        Chart.defaults.color = texto;
        Chart.defaults.borderColor = rejilla;
        Object.values(Chart.instances || {}).forEach(function (g) {
            var escalas = g.options.scales || {};
            Object.keys(escalas).forEach(function (k) {
                if (escalas[k].ticks) escalas[k].ticks.color = texto;
                if (escalas[k].grid) escalas[k].grid.color = rejilla;
            });
            if (g.config.type === 'doughnut' || g.config.type === 'pie') {
                g.data.datasets.forEach(function (d) { d.borderColor = tema === 'dark' ? '#1d1717' : '#fff'; });
            }
            if (g.options.plugins && g.options.plugins.legend && g.options.plugins.legend.labels) {
                g.options.plugins.legend.labels.color = texto;
            }
            g.update('none');
        });
    }

    function actualizarBotones(tema) {
        document.querySelectorAll('[data-tema-toggle]').forEach(function (b) {
            b.setAttribute('aria-pressed', tema === 'dark' ? 'true' : 'false');
            b.setAttribute('aria-label', tema === 'dark' ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro');
        });
        document.querySelectorAll('[data-tema-texto]').forEach(function (el) {
            el.textContent = tema === 'dark' ? 'Tema oscuro' : 'Tema claro';
        });
    }

    function aplicar(tema, guardar) {
        raiz.setAttribute('data-theme', tema);
        raiz.setAttribute('data-bs-theme', tema);
        if (guardar) {
            try { localStorage.setItem('tema', tema); } catch (e) {}
        }
        actualizarBotones(tema);
        ajustarGraficos(tema);
    }

    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-tema-toggle]');
        if (b) aplicar(actual() === 'dark' ? 'light' : 'dark', true);
    });

    // Si el usuario no ha elegido, sigue el modo del sistema operativo.
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
            var guardado = null;
            try { guardado = localStorage.getItem('tema'); } catch (err) {}
            if (!guardado) aplicar(e.matches ? 'dark' : 'light', false);
        });
    }

    function iniciar() { aplicar(actual(), false); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar); else iniciar();
    window.addEventListener('load', function () { ajustarGraficos(actual()); });
})();
