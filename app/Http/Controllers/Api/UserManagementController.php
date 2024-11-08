<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PadelMatch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserManagementController extends Controller
{
    public function userSearch(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1',
        ]);

        $query = $request->query('query'); // Retrieve the search query from the request

        $users = User::where('email', 'LIKE', "%{$query}%")->get();

        if ($users->isEmpty()) {
            return $this->sendError('No users found.', [], 404);
        }
        $formattedUsers = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'image' => $user->image
                    ? url('Profile/' . $user->image)
                    : url('avatar/profile.jpg'),
                'level' => $user->level,
            ];
        });
        return $this->sendResponse(['users' => $formattedUsers], 'Users retrieved successfully.');
    }

    public function userDetails($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return $this->sendError('No user found.', [], 404);
        }
        $userDetails = [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'user_name' => $user->user_name,
            'email' => $user->email,
            'location'=> $user->location,
            'matches_played'=> $user->matches_played,
            'image' => $user->image ? url('Profile/', $user->image) :  url('avatar','profile.jpg'),
            'level' => $user->level,
            'level_name' => $user->level_name,
            'points' => $user->points,
            'role' => $user->role,
            'created_at' => $user->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
        ];
        return $this->sendResponse($userDetails, 'User details retrieved successfully.');
    }
    public function deleteUser(Request $request, $userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return $this->sendError("No user found.", [], 404);
        }
        if (auth()->user()->role !== 'ADMIN') {
            return $this->sendError("Unauthorized action.", [], 403);
        }
        PadelMatch::where('creator_id', $userId)->delete();
        $user->delete();
        return $this->sendResponse([], "User successfully deleted.");
    }
    public function changeRole(Request $request,$userId)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:active,banned',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors());
        }
        $user = User::find($userId);
        if (!$user) {
            return $this->sendError("User not found.");
        }
        $user->status = $request->status;
        $user->save();
        return $this->sendResponse($user, 'User status updated successfully.');

    }
    public function getUsers()
    {
        try {
            $users = User::where('role','MEMBER')->orderBy("created_at", "desc")->paginate(10);
            $formattedUsers = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'location'=> $user->location,
                    'level'=> $user->level,
                    'status'=> $user->status,
                    'image' => $user->image ? url('Profile/'. $user->image) : url('avatar','profile.jpg'),
                ];
            });
            $result = [
                'users' => $formattedUsers,
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'next_page_url' => $users->nextPageUrl(),
                    'prev_page_url' => $users->previousPageUrl(),
                ],
            ];
            return $this->sendResponse($result, 'Users retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve users.', [$e->getMessage()], 500);
        }
    }
}
