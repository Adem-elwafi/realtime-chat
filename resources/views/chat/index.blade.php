<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-800">Chats</h2>
                        <a href="{{ route('users.index') }}" 
                           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            New Chat
                        </a>
                    </div>
                </div>

                <!-- Conversations List -->
                <div class="divide-y divide-gray-200">
                    @if($conversations->isEmpty())
                        <!-- Empty State -->
                        <div class="text-center py-12">
                            <div class="text-5xl mb-4">💬</div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No conversations yet</h3>
                            <p class="text-gray-500 mb-4">Start a conversation by clicking "New Chat"</p>
                            <a href="{{ route('users.index') }}" 
                               class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                                Find Users
                            </a>
                        </div>
                    @else
                        <!-- Conversations -->
                        @foreach($conversations as $convData)
                            @php
                                $conversation = $convData['conversation'];
                                $otherUser = $convData['other_user'];
                                $latestMessage = $convData['latest_message'];
                                $messagePreview = $convData['message_preview'];
                                $unreadCount = $convData['unread_count'];
                            @endphp
                            
                            <a href="{{ route('chat.show', $otherUser->id) }}" 
                               class="block p-4 hover:bg-gray-50 transition-colors">
                                <div class="flex items-center space-x-4">
                                    <!-- Avatar -->
                                    <div class="relative">
                                        <div class="h-12 w-12 rounded-full bg-gray-300 flex items-center justify-center">
                                            <span class="text-sm font-medium text-gray-700">
                                                {{ substr($otherUser->name, 0, 1) }}
                                            </span>
                                        </div>
                                        
                                        <!-- Online Status Indicator -->
                                        @if($otherUser->is_online)
                                            <div class="absolute bottom-0 right-0 h-3 w-3 bg-green-500 rounded-full border-2 border-white"></div>
                                        @endif
                                    </div>
                                    
                                    <!-- Conversation Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-start">
                                            <h3 class="text-sm font-medium text-gray-900 truncate">
                                                {{ $otherUser->name }}
                                            </h3>
                                            @if($latestMessage)
                                                <span class="text-xs text-gray-500">
                                                    {{ $latestMessage->created_at->format('g:i A') }}
                                                </span>
                                            @endif
                                        </div>
                                        
                                        <div class="flex justify-between items-center mt-1">
                                            <p class="text-sm text-gray-600 truncate">
                                                {{ $messagePreview }}
                                            </p>
                                            
                                            <!-- Unread Count Badge -->
                                            @if($unreadCount > 0)
                                                <span class="bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>