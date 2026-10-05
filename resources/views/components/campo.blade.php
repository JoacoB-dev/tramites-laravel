{{-- Input de formulario con label y error de validación --}}
@props(['nombre', 'label', 'tipo' => 'text', 'valor' => null, 'ayuda' => null])
<div class="mb-3">
    <label for="{{ $nombre }}" class="form-label">{{ $label }}</label>
    <input type="{{ $tipo }}" id="{{ $nombre }}" name="{{ $nombre }}" value="{{ old($nombre, $valor) }}"
        {{ $attributes->merge(['class' => 'form-control'.($errors->has($nombre) ? ' is-invalid' : '')]) }}>
    @error($nombre)<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if ($ayuda)<div class="form-text">{{ $ayuda }}</div>@endif
</div>
