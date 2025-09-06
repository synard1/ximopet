<!DOCTYPE html>
<html>
<head>
    <title>Chat Bubble Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/ai-chat-v2.css') }}">
    @livewireStyles
    <style>
        .test-bubble {
            width: 50px;
            height: 50px;
            background: linear-gradient(to right, #3b82f6, #8b5cf6);
            border-radius: 50%;
            animation: pulse 2s infinite;
            margin: 20px;
        }
    </style>
</head>
<body>
    <h1>Chat Bubble Test</h1>
    <p>This is a test page to verify the chat bubble component.</p>
    
    <div class="test-bubble"></div>
    <p>If you see a pulsing bubble above, CSS is working correctly.</p>
    
    <div id="chat-bubble-container">
        @livewire('ai-chat-v2.chat-bubble')
    </div>
    
    @livewireScripts
</body>
</html>