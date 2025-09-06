@extends('layout.master')

@section('content')
<div class="container">
    <h1>AI Chat V2 Test Page</h1>
    <p>This page is used to test the AI Chat V2 bubble component.</p>
    
    <div class="mt-4">
        <h2>Debug Information</h2>
        <ul>
            <li>Auth Check: {{ Auth::check() ? 'Authenticated' : 'Not Authenticated' }}</li>
            <li>User ID: {{ Auth::check() ? Auth::id() : 'N/A' }}</li>
            <li>User Name: {{ Auth::check() ? Auth::user()->name : 'N/A' }}</li>
        </ul>
    </div>
</div>
@endsection