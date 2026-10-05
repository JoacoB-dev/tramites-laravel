@if (session('ok'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> {{ session('ok') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
@endif
@if ($errors->has('general'))
    <div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle"></i> {{ $errors->first('general') }}</div>
@endif
