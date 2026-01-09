<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Display a listing of users (excluding the current user).
     *
     * This method shows all users except the authenticated user,
     * allowing them to start new conversations.
     * Results are paginated (10 per page).
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $currentUser = Auth::user();
        
        // Get all users except the current user, paginated
        $users = User::where('id', '!=', $currentUser->id)
            ->paginate(10);
        
        return view('users.index', compact('users'));
    }

    /**
     * Search for users by name or email.
     *
     * This method allows searching users by name or email address.
     * It excludes the current user from results and paginates the output.
     *
     * @param Request $request The HTTP request containing search query
     * @return \Illuminate\View\View
     */
    public function search(Request $request)
    {
        // Validate the search query
        $request->validate([
            'query' => 'nullable|string|max:255',
        ]);
        
        $currentUser = Auth::user();
        $query = $request->input('query', '');
        
        // Build the query
        $usersQuery = User::where('id', '!=', $currentUser->id);
        
        if (!empty($query)) {
            $usersQuery->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            });
        }
        
        // Paginate results
        $users = $usersQuery->paginate(10)->appends(['query' => $query]);
        
        return view('users.index', compact('users', 'query'));
    }
}