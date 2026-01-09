<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-md overflow-hidden flex flex-col h-screen max-h-[80vh]">
                <!-- Chat Header -->
                <div class="px-6 py-4 border-b border-gray-200 bg-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('chat.index') }}" 
                               class="md:hidden text-gray-600 hover:text-gray-900">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </a>
                            
                            <!-- Other User Info -->
                            <div class="flex items-center space-x-3">
                                <div class="relative">
                                    <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                        <span class="text-sm font-medium text-gray-700">
                                            {{ substr($otherUser->name, 0, 1) }}
                                        </span>
                                    </div>
                                    
                                    <!-- Online Status -->
                                    @if($otherUser->is_online)
                                        <div class="absolute bottom-0 right-0 h-3 w-3 bg-green-500 rounded-full border-2 border-white"></div>
                                    @endif
                                </div>
                                
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">{{ $otherUser->name }}</h2>
                                    <p class="text-sm text-gray-500">
                                        @if($otherUser->is_online)
                                            <span class="text-green-600">Online</span>
                                        @else
                                            Last seen {{ $otherUser->last_seen ? $otherUser->last_seen->diffForHumans() : 'a while ago' }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Mark as Read Button -->
                        <form action="{{ route('chat.markAsRead', $conversation->id) }}" method="POST" class="hidden md:block">
                            @csrf
                            <button type="submit" 
                                    class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                Mark as Read
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Messages Area -->
                <div class="flex-1 overflow-y-auto p-4 space-y-4" id="messages-container">
                    @if($messages->isEmpty())
                        <!-- Empty State -->
                        <div class="flex flex-col items-center justify-center h-full text-center py-12">
                            <div class="text-5xl mb-4">👋</div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Start the conversation!</h3>
                            <p class="text-gray-500">Send a message to {{ $otherUser->name }} to get started.</p>
                        </div>
                    @else
                        <!-- Messages -->
                        @foreach($messages as $message)
                            @php
                                $isOwnMessage = $message->sender_id === auth()->id();
                            @endphp
                            
                            <div class="flex {{ $isOwnMessage ? 'justify-end' : 'justify-start' }}">
                                <div class="{{ $isOwnMessage ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-900' }} 
                                     rounded-2xl px-4 py-2 max-w-xs md:max-w-md lg:max-w-lg">
                                    <p class="text-sm">{{ $message->message }}</p>
                                    <div class="flex justify-end items-center mt-1">
                                        <span class="text-xs opacity-80">
                                            {{ $message->created_at->format('g:i A') }}
                                        </span>
                                        @if($isOwnMessage && $message->is_read)
                                            <span class="ml-1 text-xs">✓✓</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
                
                <!-- Message Input Form -->
                <div class="border-t border-gray-200 bg-white p-4">
                    <form action="{{ route('chat.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $otherUser->id }}">
                        
                        <div class="flex space-x-3">
                            <div class="flex-1">
                                <textarea 
                                    name="message"
                                    rows="1"
                                    placeholder="Type a message..."
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                                    required
                                    maxlength="5000"
                                    oninput="this.style.height = 'auto'; this.style.height = (this.scrollHeight) + 'px'"
                                    style="min-height: 44px; max-height: 120px;">{{ old('message') }}</textarea>
                                
                                @error('message')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <button type="submit"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors self-end">
                                Send
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Auto-scroll to bottom of messages --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messagesContainer = document.getElementById('messages-container');
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
            
            // Auto-resize textarea
            const textarea = document.querySelector('textarea[name="message"]');
            if (textarea) {
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });
            }
        });
    </script>
</x-app-layout>