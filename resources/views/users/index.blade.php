<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
                        <h2 class="text-xl font-semibold text-gray-800">Find Users</h2>
                        
                        <!-- Search Form -->
                        <form action="{{ route('users.search') }}" method="GET" class="w-full md:w-auto">
                            <div class="flex">
                                <input type="text" 
                                       name="query"
                                       value="{{ request('query', '') }}"
                                       placeholder="Search by name or email..."
                                       class="px-4 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full md:w-64">
                                <button type="submit"
                                        class="bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-r-lg border border-l-0 border-gray-300">
                                    Search
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Users List -->
                <div class="divide-y divide-gray-200">
                    @if($users->isEmpty())
                        <!-- Empty State -->
                        <div class="text-center py-12">
                            <div class="text-5xl mb-4">👥</div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No users found</h3>
                            <p class="text-gray-500">
                                @if(request('query'))
                                    No users match your search "{{ request('query') }}". Try different keywords.
                                @else
                                    There are no other users to chat with.
                                @endif
                            </p>
                        </div>
                    @else
                        <!-- Users Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-4">
                            @foreach($users as $user)
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-center space-x-4">
                                        <!-- Avatar -->
                                        <div class="relative">
                                            <div class="h-12 w-12 rounded-full bg-gray-300 flex items-center justify-center">
                                                <span class="text-sm font-medium text-gray-700">
                                                    {{ substr($user->name, 0, 1) }}
                                                </span>
                                            </div>
                                            
                                            <!-- Online Status -->
                                            @if($user->is_online)
                                                <div class="absolute bottom-0 right-0 h-3 w-3 bg-green-500 rounded-full border-2 border-white"></div>
                                            @endif
                                        </div>
                                        
                                        <!-- User Info -->
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-sm font-medium text-gray-900 truncate">
                                                {{ $user->name }}
                                            </h3>
                                            <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                @if($user->is_online)
                                                    <span class="text-green-600">Online</span>
                                                @else
                                                    Offline
                                                @endif
                                            </p>
                                        </div>
                                        
                                        <!-- Start Chat Button -->
                                        <a href="{{ route('chat.show', $user->id) }}"
                                           class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-medium transition-colors">
                                            Chat
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <!-- Pagination -->
                        <div class="px-4 py-4 border-t border-gray-200">
                            {{ $users->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>