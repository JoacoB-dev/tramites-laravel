import * as bootstrap from 'bootstrap';
import $ from 'jquery';

window.$ = window.jQuery = $;
window.bootstrap = bootstrap;

// Todas las llamadas AJAX mandan el token CSRF de Laravel y piden JSON
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        Accept: 'application/json',
    },
});

import './comunes';
import './tramites';
import './cuotas';
