<!-- resources/views/chat/show.blade.php -->
<x-app-layout>
    <div class="max-w-3xl mx-auto mt-8">
        <h1 class="text-2xl font-bold mb-4">Chat with {{ $otherUser->name }}</h1>
        <div 
            id="chat-app"
            data-conversation-id="{{ $conversation->id }}"
            data-initial-messages="{{ json_encode($messagesForReact) }}"
            data-current-user-id="{{ auth()->id() }}"
            data-other-user-id="{{ $otherUser->id }}"               
            class="border rounded-lg shadow"
        ></div>
        {{-- Script is loaded from the layout; avoid duplicate includes --}}
    </div>
</x-app-layout>
