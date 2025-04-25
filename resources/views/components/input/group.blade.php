@props(['label' => '', 'col' => '12'])

<div class="col-md-{{ $col }}">
    <label class="form-label">{{ $label }}</label>
    {{ $slot }}
</div>
