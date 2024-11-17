<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\PadelMatch;
use App\Models\PadelMatchMember;
use App\Models\RequestTrailMacth;
use App\Models\TrailMatch;
use App\Models\User;
use App\Models\Volunteer;
use App\Notifications\TrailMatchRequestNotification;
use App\Notifications\TrailMatchStatusNotification;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function StartTrailMatch(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'game_status' => 'required|string|max:255', // Adjust as needed
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }
        $tarilmatch = TrailMatch::find($id);
        if(!$tarilmatch){
            return $this->sendError('Not foun trail match.');
        }
        $tarilmatch->game_status = $request->game_status;
        $tarilmatch->save();

        return $this->sendResponse([], "Trail Match started successfully");
    }
    public function getGameStatusTrailMatch( $id)
    {
        $tarilmatch = TrailMatch::find($id);
        if(!$tarilmatch){
            return $this->sendError('Not foun trail match.');
        }
        return $this->sendResponse($tarilmatch->game_status, "Trail Match started successfully");
    }
    public function TrailMatchStatus($trailMatchId)
    {
        $trailMatch = RequestTrailMacth::find($trailMatchId);
        if (!$trailMatch) {
            return $this->sendError('No trail match found.');
        }
        $status=[
            'status'=> $trailMatch->status,
            'trailMatchId'=> $trailMatch->id,
        ];
        return $this->sendResponse([$status],'Status get successfully.');
    }
    public function acceptTrailMatch(Request $request)
    {
        $trailMatch = TrailMatch::find($request->trail_match_id);
        if(!$trailMatch)
        {
            return $this->sendError("Trail Match not found.");
        }
        if($trailMatch->status == 1)
        {
            return $this->sendResponse([], 'Trail match already accepted.');
        }
        $trailMatch->status = 1;
        $trailMatch->save();
        if($trailMatch->status == 1)
        {
           $requestMatch = RequestTrailMacth::find($trailMatch->request_id);
           $requestMatch->status = 'accepted';
           $requestMatch->save();
        }
        $volunteerIds = json_decode($trailMatch->volunteer_id, true);
        $this->notifyUsers($trailMatch, $volunteerIds, 'Trail Match Accepted', "{$trailMatch->user->full_name} has accepted the trail match.");
        return $this->sendResponse([], 'Trail match accepted successfully.');
    }
    public function denyTrailMatch(Request $request)
    {
        $request->validate([
            'trail_match_id' => 'required|exists:trail_matches,id',
        ]);
        $trailMatch = TrailMatch::find($request->trail_match_id);
        $trailMatch->status = false;
        $trailMatch->save();
        $volunteerIds = json_decode($trailMatch->volunteer_id, true);
        $this->notifyUsers($trailMatch, $volunteerIds, 'Trail Match Denied', "{$trailMatch->user->full_name} has denied the trail match.");
        return $this->sendResponse([], 'Trail match denied successfully.');
    }
    private function notifyUsers($trailMatch, $volunteerIds, $title, $message)
    {
        $user = User::find($trailMatch->user_id);
        $admin = User::where('role','ADMIN')->first();
        if ($admin) {
            $admin->notify(new TrailMatchStatusNotification($title, $message,$user,$trailMatch));
        }
        if (is_array($volunteerIds) && count($volunteerIds) > 0) {
            $volunteers = Volunteer::whereIn('id', $volunteerIds)->get();
            foreach ($volunteers as $volunteer) {
                $volunteer->notify(new TrailMatchStatusNotification($title, $message, $volunteer,$trailMatch));
            }
        }
    }
    public function TrailMatchDetails()
    {
        $user = Auth::user();
        //$trailMatches = TrailMatch::where('user_id', $user->id)->where('status',1)->orderBy('id','desc')->get();
        $trailMatches = TrailMatch::where('user_id', $user->id)->orderBy('id','desc')->get();
        if ($trailMatches->isEmpty()) {
            return response()->json([
                'message' => 'No trail matches found.',
                'data' => [],
            ]);
        }
        $formattedTrailMatches = $trailMatches->map(function ($trailMatch) {
            $volunteerIds = json_decode($trailMatch->volunteer_id);
            $volunteers = Volunteer::whereIn('id', $volunteerIds)->get()->map(function ($volunteer) {
                return [
                    'id' => $volunteer->id,
                    'name' => $volunteer->name,
                    'role' => $volunteer->role,
                    'level' => $volunteer->level,
                    'phone_number' => $volunteer->phone_number,
                    'image' => $volunteer->image
                        ? url('uploads/volunteers/', $volunteer->image)
                        : url('avatar/profile.jpg'),
                ];
            });
            $user = $trailMatch->user;
            $club = $trailMatch->club;
            return [
                'trail_match_id' => $trailMatch->id,
                'full_name' => $user->full_name,
                'image' => $user->image
                    ? url('Profile/', $user->image)
                    : url('avatar/profile.jpg'),
                'level' => $user->level,
                'level_name' => $user->level_name,
                'club_name' => $club ? $club->club_name : 'No club',
                'club_location' => $club ? $club->location : 'No location',
                'time' => $trailMatch->time,
                'date' => $trailMatch->date,
                'volunteers' => $volunteers,
                'created_at' => $trailMatch->created_at->format('Y-m-d H:i:s'),
            ];
        });
        return $this->sendResponse($formattedTrailMatches, 'Trail matches retrieved successfully.');
    }
    public function getRequestToTrailMatch()
    {
        $user = Auth::user();
        $requestTrailMatch = RequestTrailMacth::where("user_id", $user->id)
            ->orderBy('id', 'desc')
            ->first();
            if (!$requestTrailMatch) {
                return response()->json([
                    'message' => 'No request found.',
                    "data"=>[]
                ], 404);
            }
            if($requestTrailMatch->status =='approved'){
                $trailMatch = TrailMatch::where('request_id',$requestTrailMatch->id)->first();
            }
        return $this->sendResponse(
            [
                'requestTrailMatch' => $requestTrailMatch,
                'trailMatch' => $trailMatch->id ?? "N/A",
            ],
            'Request retrieved successfully.');
    }
    public function requestToTrailMatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "request_level"=> "required",
        ]);
        if ($validator->fails())
        {
            return $this->sendError("Validaiton Errors", $validator->errors());
        }
        $user = Auth::user();
        if(!$user)
        {
            return $this->sendError("You are not authenticate.");
        }
       $request= RequestTrailMacth::create([
            "user_id"=> $user->id,
            "request_level"=> $request->request_level,
            "status"=> 'request',
        ]);
        $admin = User::where('role', 'ADMIN')->first();

        $admin->notify(new TrailMatchRequestNotification($user));

        return $this->sendResponse($request,'Request send successfully.');
    }
    public function myProfile()
    {
        $user = Auth::user();
        if(!$user)
        {
            return $this->sendError("User not found.");
        }
        return $this->getFormattedUserDetails($user);
    }
    public function anotherUserProfile($id)
    {
        $user = User::findOrFail($id);
        if(!$user)
        {
            return $this->sendError("User not found");
        }
        return $this->getFormattedUserDetails($user);
    }
    private function getFormattedUserDetails($user)
    {
        $formattedUser = [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'image' => $user->image ? url('Profile/',$user->image) : url('avatar','profile.jpg'),
            'email' => $user->email,
            'level' => $user->level,
            'matches_played'=> $user->matches_played,
            // 'created_matches_count' => $createdMatches->count(),
            // 'joined_matches_count' => $joinedMatches->count(),
            // 'created_matches' => $createdMatches->map(function ($match) use ($client) {
            //     $location = $this->getLocationFromCoordinates($client, $match->latitude, $match->longitude, env('GOOGLE_MAPS_API_KEY'));
            //     $group = Group::where('match_id', $match->id)->first();
            //     $playerCount = $group ? $group->members()->count() : 0;
            //     $join = $playerCount < 8;

            //     return [
            //         'id' => $match->id,
            //         'mind_text' => $match->mind_text,
            //         'selected_level' => $match->selected_level,
            //         'level' => $match->level,
            //         'level_name' => $match->level_name,
            //         'location_address' => $location,
            //         'player_count' => $playerCount,
            //         'join'=> $join,
            //         'created_at' => $match->created_at->toDateTimeString(),

            //     ];
            // }),

            // 'joined_matches' => $joinedMatches->map(function ($member) use ($client) {
            //     $match = $member->padelMatch;
            //     $location = $this->getLocationFromCoordinates($client, $match->latitude, $match->longitude, env('GOOGLE_MAPS_API_KEY'));
            //     $group = Group::where('match_id', $match->id)->first();
            //     $playerCount = $group ? $group->members()->count() : 0;
            //     $join = $playerCount < 8;
            //     return [
            //         'id' => $match->id,
            //         'mind_text' => $match->mind_text,
            //         'selected_level' => $match->selected_level,
            //         'level' => $match->level,
            //         'level_name' => $match->level_name,
            //         'location' => $location,
            //         'player_count' => $playerCount,
            //         'join' => $join,
            //         'created_at' => $match->created_at->toDateTimeString(),

            //     ];
            // }),
        ];
        return response()->json([
            'success' => true,
            'data' => $formattedUser,
            'message' => 'User profile retrieved successfully.'
        ], 200);
    }
    public function createdMatches()
    {
        $user = Auth::user();
        $createdMatches = PadelMatch::where('creator_id', $user->id)->get();
        $formattedMatches = $this->formatMatches($createdMatches);
        return response()->json([
            'success' => true,
            'data' => $formattedMatches,
            'message' => 'Created matches retrieved successfully.'
        ], 200);
    }

    public function joinedMatches()
    {
        $user = Auth::user();
        $joinedMatches = PadelMatchMember::where('user_id', $user->id)
            ->with('padelMatch')
            ->get();
        $formattedMatches = $this->formatMatches($joinedMatches->pluck('padelMatch'));
        return response()->json([
            'success' => true,
            'data' => $formattedMatches,
            'message' => 'Joined matches retrieved successfully.'
        ], 200);
    }
    private function formatMatches($matches)
    {
        $client = new Client();
        return $matches->map(function ($match) use ($client) {
            if (!$match) {
               return response()->json([
                'data'=>[],
                'message'=>'No match found.',
                'satus'=>false
               ]);
            }
            $location = null;
            if ($match->latitude && $match->longitude) {
                $location = $this->getLocationFromCoordinates($client, $match->latitude, $match->longitude, env('GOOGLE_MAPS_API_KEY'));
            }
            $creator = User::find($match->creator_id);
            $creatorName = $creator ? $creator->full_name : 'Unknown';

            $createdMatchesCount = PadelMatch::where('creator_id', $match->creator_id)->count();
            $group = Group::where('match_id', $match->id)->first();
            $playerCount = $group ? $group->members()->count() : 0;
            $join = $playerCount < 8;
            return [
                'id' => $match->id,
                'mind_text' => $match->mind_text,
                'selected_level' => $match->selected_level,
                'level' => $match->level,
                'level_name' => $match->level_name,
                'location' => $location,
                'creator_name' => $creatorName,
                'creator_matches_count' => $createdMatchesCount,
                'player_count' => $playerCount,
                'join' => $join,
                'created_at' => $match->created_at->toDateTimeString(),
            ];
        });
    }
    public function trailMatches()
    {
        $user = Auth::user();
        TrailMatch::where('user_id', $user->id)
                    ->where('status', 1)
                    ->whereDate('date', '<', now())
                    ->update(['status' => 0]);
        $trailMatches = TrailMatch::where('user_id', $user->id)->where('status',1)->orderBy('id','desc')->get();
        if ($trailMatches->isEmpty()) {
            return response()->json([
                'message' => 'No trail matches found.',
                'data' => [],
            ]);
        }
        $formattedTrailMatches = $trailMatches->map(function ($trailMatch) {
            $volunteerIds = json_decode($trailMatch->volunteer_id);
            $volunteers = Volunteer::whereIn('id', $volunteerIds)->get()->map(function ($volunteer) {
                return [
                    'id' => $volunteer->id,
                    'name' => $volunteer->name,
                    'role' => $volunteer->role,
                    'level' => $volunteer->level,
                    'phone_number' => $volunteer->phone_number,
                    'image' => $volunteer->image
                        ? url('uploads/volunteers/', $volunteer->image)
                        : url('avatar/profile.jpg'),
                ];
            });
            $user = $trailMatch->user;
            $club = $trailMatch->club;
            return [
                'trail_match_id' => $trailMatch->id,
                'full_name' => $user->full_name,
                'image' => $user->image
                    ? url('Profile/', $user->image)
                    : url('avatar/profile.jpg'),
                'level' => $user->level,
                'level_name' => $user->level_name,
                'club_name' => $club ? $club->club_name : 'No club',
                'club_location' => $club ? $club->location : 'No location',
                'time' => $trailMatch->time,
                'date' => $trailMatch->date,
                'volunteers' => $volunteers,
                'created_at' => $trailMatch->created_at->format('Y-m-d H:i:s'),
            ];
        });
        return $this->sendResponse($formattedTrailMatches, 'Trail matches retrieved successfully.');
    }
    private function getLocationFromCoordinates($client, $latitude, $longitude, $apiKey)
    {
        try {
            $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$latitude},{$longitude}&key={$apiKey}";
            $response = $client->get($url);
            $data = json_decode($response->getBody(), true);

            if (isset($data['results']) && count($data['results']) > 0) {
                return $data['results'][0]['formatted_address'];
            } else {
                return 'Location not found';
            }
        } catch (RequestException $e) {
            return 'Error retrieving location: ' . $e->getMessage();
        }
    }
    public function upgradeLevelFree()
    {
        $user = Auth::user();
        $currentLevel = $user->level;
        $levelNames = [
            1 => 'Beginner',
            2 => 'Lower-Intermediate',
            3 => 'Upper-Intermediate',
            4 => 'Advanced',
            5 => 'Professional',
        ];
        $beforeLevelArray = [];
        for ($i = 1; $i < $currentLevel; $i++) {
            $beforeLevelArray[] = [
                'level' => $i,
                'level_name' => $levelNames[$i]
            ];
        }
        $currentLevelDetails = [
            'level' => $currentLevel,
            'level_name' => $levelNames[$currentLevel] ?? 'Unknown'
        ];
        $nextLevelDetails = ($currentLevel < 5) ? [
            'level' => $currentLevel + 1,
            'level_name' => $levelNames[$currentLevel + 1]
        ] : null;
        $formattedResponse = [
            'success' => true,
            'data' => [
                'user_id' => $user->id,
                'full_name' => $user->full_name,
                'image' => $user->image ? url('Profile/' . $user->image) : url('avatar','profile.jpg'),
                'current_level' => $currentLevelDetails,
                'before_levels' => ($currentLevel !== 1) ? ($currentLevel - 1) : null,
                'before_level_array' => $beforeLevelArray,
                'after_levels' => ($currentLevel < 5) ? ($currentLevel + 1) : null,
                'after_level_array' => $nextLevelDetails
            ],
            'message' => 'User level upgraded successfully.'
        ];
        if ($currentLevel === 5) {
            unset($formattedResponse['data']['before_levels']);
            unset($formattedResponse['data']['before_level_array']);
        }
        return response()->json($formattedResponse, 200);
    }

}
