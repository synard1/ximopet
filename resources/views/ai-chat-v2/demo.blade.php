@extends('layout.master')

@section('title', 'AI Chat V2 Demo')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h1>AI Chat V2 Demo</h1>
            <p class="lead">This page demonstrates the AI Chat V2 bubble that appears on all authenticated pages.</p>
            
            <div class="card">
                <div class="card-header">
                    <h3>Demo Content</h3>
                </div>
                <div class="card-body">
                    <p>You should see a chat bubble in the bottom right corner of your screen.</p>
                    <p>Click on the bubble to open the chat interface.</p>
                    
                    <div class="mt-4">
                        <a href="{{ route('ai-chat-v2.index') }}" class="btn btn-primary">
                            Open Full Chat Interface
                        </a>
                        <a href="{{ route('ai-chat-v2.test') }}" class="btn btn-secondary">
                            Test Page
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <h4>Features</h4>
                <ul>
                    <li>Floating chat bubble that appears on all pages</li>
                    <li>Quick messaging interface</li>
                    <li>Full chat interface with history</li>
                    <li>Settings panel for provider configuration</li>
                    <li>Session management</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection