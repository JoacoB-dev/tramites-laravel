import $ from 'jquery';

$(function () {
    // --- Portal: formulario "Iniciar trámite" ---------------------------------
    const $form = $('#form-tramite');
    if ($form.length) {
        const $tipo = $('#tipo_tramite_id');
        const $adjuntos = $('#adjuntos');
        const maxKb = Number($form.data('max-kb'));
        const maxArchivos = Number($form.data('max-archivos'));

        // Muestra/oculta campos según el tipo elegido (datos en los data-* del <option>)
        const actualizarTipo = () => {
            const $op = $tipo.find(':selected');
            const elegido = $op.val() !== '';
            const esDomicilio = $op.data('codigo') === 'cambio_domicilio';
            const requiereAdjunto = Number($op.data('requiere-adjunto')) === 1;

            $('#campos-domicilio').toggleClass('d-none', !esDomicilio)
                .find('input').prop('required', esDomicilio);
            $('#adjunto-obligatorio').toggleClass('d-none', !requiereAdjunto);
            $adjuntos.prop('required', requiereAdjunto);

            $('#info-vacia').toggleClass('d-none', elegido);
            $('#info-detalle').toggleClass('d-none', !elegido);
            if (elegido) {
                $('#info-descripcion').text($op.data('descripcion'));
                $('#info-arancel').text($op.data('arancel'));
                $('#info-dias').text($op.data('dias'));
            }
        };

        // Lista los archivos elegidos y avisa si alguno no es PDF o es muy grande
        const validarArchivos = () => {
            const archivos = Array.from($adjuntos[0].files);
            const errores = [];
            const $lista = $('#lista-adjuntos').empty();

            if (archivos.length > maxArchivos) {
                errores.push(`Podés adjuntar hasta ${maxArchivos} archivos.`);
            }
            archivos.forEach((f) => {
                const kb = Math.round(f.size / 1024);
                const esPdf = f.type === 'application/pdf' || f.name.toLowerCase().endsWith('.pdf');
                $('<li>').html(`<i class="bi bi-file-earmark-pdf"></i> `).append(document.createTextNode(`${f.name} (${kb} KB)`))
                    .toggleClass('text-danger', !esPdf || kb > maxKb).appendTo($lista);
                if (!esPdf) errores.push(`"${f.name}" no es un PDF.`);
                if (kb > maxKb) errores.push(`"${f.name}" supera los ${maxKb / 1024} MB.`);
            });

            $adjuntos[0].setCustomValidity(errores.join(' '));
            return errores;
        };

        $tipo.on('change', actualizarTipo);
        $adjuntos.on('change', validarArchivos);
        actualizarTipo();

        $form.on('submit', function (e) {
            const errores = validarArchivos();
            if (errores.length) {
                e.preventDefault();
                window.alert(errores.join('\n'));
            }
        });
    }

    // --- Panel: cambio de estado de un trámite --------------------------------
    const $formEstado = $('#form-estado');
    if ($formEstado.length) {
        const actualizarObservacion = () => {
            const requiere = Number($formEstado.find('[name="estado"]:checked').data('requiere-observacion')) === 1;
            $('#observacion').prop('required', requiere);
            $('#obs-obligatoria').toggleClass('d-none', !requiere);
        };
        $formEstado.on('change', '[name="estado"]', actualizarObservacion);
        actualizarObservacion();
    }
});
