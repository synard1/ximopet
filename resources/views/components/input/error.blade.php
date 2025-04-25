@props(['for'])

@error($for)
    <div class="text-danger small">{{ $message }}</div>
@enderror
