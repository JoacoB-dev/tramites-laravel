import $ from 'jquery';
import { Modal } from 'bootstrap';

// Registro de pagos desde el listado de cuotas, sin recargar la página
$(function () {
    const $modalEl = $('#modal-pago');
    if (!$modalEl.length) return;

    const modal = new Modal($modalEl[0]);
    const $form = $('#form-pago');
    const $error = $('#pago-error');
    let $fila = null;

    $('#tabla-cuotas').on('click', '.js-registrar-pago', function () {
        $fila = $(this).closest('tr');
        $form.attr('action', $(this).data('url'));
        $('#pago-descripcion').text($(this).data('descripcion'));
        $error.addClass('d-none').text('');
        modal.show();
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        const $boton = $('#pago-confirmar').prop('disabled', true);

        $.ajax({ url: $form.attr('action'), method: 'POST', data: $form.serialize() })
            .done((resp) => {
                const c = resp.cuota;
                $fila.find('.js-estado').html($('<span>').addClass(`badge text-bg-${c.color}`).text(c.estado_etiqueta));
                $fila.find('.js-pago').text(`${c.pagada_en} · ${c.medio_pago}`);
                $fila.find('.js-accion').empty();
                $fila.addClass('fila-actualizada');
                modal.hide();
            })
            .fail((xhr) => {
                // 422 = error de validación de Laravel; 403 = ya pagada o sin permiso
                const r = xhr.responseJSON || {};
                const mensaje = r.errors ? Object.values(r.errors).flat().join(' ') : (r.message || 'No se pudo registrar el pago.');
                $error.removeClass('d-none').text(mensaje);
            })
            .always(() => $boton.prop('disabled', false));
    });
});
