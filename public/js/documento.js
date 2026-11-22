/* Ayuda del campo de documento: formato esperado según el tipo y teclado numérico en celulares. */
(function () {
    var AYUDA = {
        CC: 'Cédula: 5 a 10 dígitos, sin puntos. Las de 10 dígitos empiezan por 1.',
        TI: 'Tarjeta de identidad: 10 u 11 dígitos.',
        CE: 'Cédula de extranjería: 5 a 10 dígitos.',
        PA: 'Pasaporte: 5 a 15 letras o números.'
    };
    document.querySelectorAll('select[name="tipo_documento"]').forEach(function (sel) {
        var form = sel.form, input = form && form.querySelector('input[name="documento"]');
        if (!input) return;
        var ayuda = document.createElement('div');
        ayuda.className = 'form-text';
        ayuda.setAttribute('aria-live', 'polite');
        input.insertAdjacentElement('afterend', ayuda);
        input.setAttribute('aria-describedby', (input.getAttribute('aria-describedby') || '') + ' ayuda-doc');
        ayuda.id = 'ayuda-doc';
        function actualizar() {
            var t = sel.value;
            ayuda.textContent = AYUDA[t] || 'Selecciona primero el tipo de documento.';
            input.setAttribute('inputmode', t === 'PA' ? 'text' : 'numeric');
        }
        sel.addEventListener('change', actualizar);
        actualizar();
    });
})();
