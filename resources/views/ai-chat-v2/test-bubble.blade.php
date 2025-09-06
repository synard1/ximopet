<!DOCTYPE html>
<html>
<head>
    <title>AI Chat V2 Bubble Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/ai-chat-v2.css') }}">
    @livewireStyles
</head>
<body>
    <h1>AI Chat V2 Bubble Test</h1>
    <p>This page tests if the chat bubble component is working correctly.</p>
    
    <div style="position: fixed; bottom: 20px; right: 20px; z-index: 999999;">
        @livewire('ai-chat-v2.chat-bubble')
    </div>
    
    @livewireScripts
</body>
</html>