<!DOCTYPE html>
<html>
<head>
    <title>Livewire Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @livewireStyles
</head>
<body>
    <h1>Livewire Test</h1>
    
    @livewire('test-component')
    
    @livewireScripts
</body>
</html>