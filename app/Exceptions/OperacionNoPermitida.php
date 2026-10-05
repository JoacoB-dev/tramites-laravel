<?php

namespace App\Exceptions;

use DomainException;

/**
 * Error de regla de negocio (transición de estado inválida, deuda pendiente, etc.).
 * El mensaje está pensado para mostrarse tal cual al usuario.
 */
class OperacionNoPermitida extends DomainException {}
