@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto mt-8">
    <h1 class="text-2xl font-bold mb-4">Chat with {{ $otherUser->name }}</h1>

    {{-- React will mount here --}}
    <div 
        id="chat-app"
        data-conversation-id="{{ $conversation->id }}"
        data-initial-messages="{{ json_encode($messagesForReact) }}"
        data-current-user-id="{{ auth()->id() }}"
        class="border rounded-lg shadow"
    ></div>

    {{-- Load Vite + React --}}
    @vite(['resources/js/app.js'])
</div>
@endsection