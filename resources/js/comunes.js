import $ from 'jquery';

$(function () {
    // Filtros: al cambiar un select se envía el formulario
    $('.js-autosubmit').on('change', function () {
        $(this).closest('form').trigger('submit');
    });

    // Confirmación antes de acciones delicadas
    $('form.js-confirmar').on('submit', function (e) {
        if (!window.confirm($(this).data('confirmar'))) {
            e.preventDefault();
        }
    });

    // Anular certificado: pide el motivo con un prompt
    $('form.js-anular').on('submit', function (e) {
        const motivo = window.prompt('Motivo de la anulación del certificado:');
        if (!motivo || !motivo.trim()) {
            e.preventDefault();
            return;
        }
        $(this).find('[name="motivo_anulacion"]').val(motivo.trim());
    });

    // CUIT: formatea mientras se escribe (20-12345678-6)
    $('.js-cuit').on('input', function () {
        const d = $(this).val().replace(/\D/g, '').slice(0, 11);
        let f = d;
        if (d.length > 2) f = d.slice(0, 2) + '-' + d.slice(2);
        if (d.length > 10) f = d.slice(0, 2) + '-' + d.slice(2, 10) + '-' + d.slice(10);
        $(this).val(f);
    });
});
