{{-- Badge para cualquier enum que tenga etiqueta() y color() --}}
@props(['estado'])
<span {{ $attributes->merge(['class' => 'badge text-bg-'.$estado->color()]) }}>{{ $estado->etiqueta() }}</span>
