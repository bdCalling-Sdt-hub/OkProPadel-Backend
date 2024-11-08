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
                    'id'=>$request->user->id,
                    'full_name'=>$request->user->full_name,
                    'email'=>$request->user->email,
                    'location'=>$request->user->location,
                    'current_level'=>$request->user->level,
                    'image'=>$request->image ? url('Profile/',$request->image) : url('avatar/profile.jpg'),
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
    public function setUpTrailMatch(Request $request,$requestId)
    {
        $validator = Validator::make($request->all(), [
            'volunteer_ids' => 'nullable|array',
            'volunteer_ids.*' => 'exists:volunteers,id',
            'user_id' => 'required|exists:users,id',
            'club_id' => 'required|exists:clubs,id',
            'time' => 'required',
            'date' => 'required|date',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }
        $requestTrailMatch = RequestTrailMacth::find($requestId);
        if (!$requestTrailMatch) {
            return $this->sendError(0,'No found Request Trail Match.');
        }
        $userId = $request->user_id;
        if (!$userId) {
            return $this->sendError('Authentication required.', [], 401);
        }
        $trailMatch = TrailMatch::where('request_id',$requestId)->first();
        if ($trailMatch) {
            $trailMatch->update([
                'user_id' => $userId,
                'club_id' => $request->club_id ?? $trailMatch->club_id,
                'date' => $request->date ?? $trailMatch->data,
                'volunteer_id' => json_encode($request->volunteer_ids),
                'time' => $request->time ?? $trailMatch->time,
            ]);
        } else {
            $trailMatch = TrailMatch::create([
                'request_id' => $requestTrailMatch->id,
                'user_id' => $userId,
                'club_id' => $request->club_id,
                'date' => $request->date,
                'volunteer_id' => json_encode($request->volunteer_ids),
                'time' => $request->time,
            ]);
        }
        if($trailMatch){
            $requestTrailMatch->status = 'approved';
            $requestTrailMatch->save();
        }
        $user = User::find($userId);
        if($user){
            $user->notify(new TrailMatchCreatedNotification($trailMatch, $user));
        }
        return $this->sendResponse($trailMatch, 'Trail match set up successfully.');
    }
    public function getSetUpTrailMatch($trailMatchId)
    {
        $trailMatch = TrailMatch::find( $trailMatchId );
        if(!$trailMatch){
            return $this->sendError('Not found Trail Match.');
        }
        $trailMatch = TrailMatch::with(['user', 'club','volunteer'])->findOrFail($trailMatchId);
        if (!$trailMatch) {
            return $this->sendError('Trail match not found.', [], 404);
        }
        $volunteerIds = json_decode($trailMatch->volunteer_id, true) ?? [];
        $volunteers = Volunteer::whereIn('id', $volunteerIds)->get();
        $formattedTrailMatch = [
            'id' => $trailMatch->id,
            'user' => [
                'user_id' => $trailMatch->user->id,
                'full_name' => $trailMatch->user->full_name,
                'image' => $trailMatch->user->image ? url('Profile/' . $trailMatch->user->image) : url('avatar/profile.jpg')
            ],
            'club' => [
                'club_id' => $trailMatch->club->id,
                'club_name' => $trailMatch->club->club_name,
                'image' => $trailMatch->club->image ? url('clubs/' . $trailMatch->club->image) : url('avatar/club.jpg')
            ],
            'volunteers' => $volunteers->map(function ($volunteer) {
                return [
                    'volunteer_id' => $volunteer->id,
                    'name' => $volunteer->name,
                    'image' => $volunteer->profile_image ? url('uploads/volunteers/' . $volunteer->image) : url('avatar/profile.jpg')
                ];
            }),
            'time' => $trailMatch->time,
            'date' => $trailMatch->date,
            'created_at' => $trailMatch->created_at->toDateTimeString(),
        ];

        return $this->sendResponse($formattedTrailMatch, 'Trail match details retrieved successfully.');
    }

}
