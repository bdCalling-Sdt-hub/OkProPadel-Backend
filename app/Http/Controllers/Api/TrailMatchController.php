<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\RequestTrailMacth;
use App\Models\TrailMatch;
use App\Models\User;
use App\Models\Volunteer;
use App\Notifications\TrailMatchCreatedNotification;
use Auth;
use Illuminate\Http\Request;
use Validator;

class TrailMatchController extends Controller
{
    public function requestMatch()
    {
        $requests = RequestTrailMacth::orderBy('id', 'desc')
            ->get();
        if ($requests->isEmpty()) {
            return $this->sendError("No match requests found.", [], 404);
        }
        $formattedRequests = $requests->map(function ($request) {
            return [
                'request_id' => $request->id,
                'user' =>[
                    'full_name'=>$request->user->full_name,
                    'user_name'=>$request->user->user_name,
                    'current_level'=>$request->user->level,
                    'image'=>$request->image ? url('Profile/',$request->image) :'',
                ],
                'request_level' => $request->request_level,
                'status' => $request->status,
                'created_at' => $request->created_at->format('Y-m-d H:i:s'),
                'club'=>$this->club() ?? ''
            ];
        });
        return $this->sendResponse($formattedRequests, 'Match requests retrieved successfully.');
    }
    private function club(){
        return Club::select('id','club_name')->get();
    }
    public function setUpTrailMatch(Request $request)
    {
        $request->validate([
            'volunteer_ids' => 'nullable|array',
            'volunteer_ids.*' => 'exists:users,id',
            'user_id' => 'required|exists:users,id',
            'club_id' => 'required|exists:clubs,id',
            'time' => 'required|date_format:H:i',
            'date' => 'required|date',
        ]);
        $userId = $request->user_id;
        if (!$userId) {
            return $this->sendError('Authentication required.', [], 401);
        }
        $trailMatch = TrailMatch::create([
            'user_id' => $userId,
            'club_id' => $request->club_id,
            'volunteer_id' => json_encode($request->volunteer_ids),
            'time' => $request->time,
            'date' => $request->date,
        ]);
        $user = User::find($userId);
        if($user){
            $user->notify(new TrailMatchCreatedNotification($trailMatch, $user));
        }

        return $this->sendResponse($trailMatch, 'Trail match set up successfully.');
    }
}
