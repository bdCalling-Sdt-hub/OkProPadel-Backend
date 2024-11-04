<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\PadelMatch;
use App\Models\PadelMatchMember;
use App\Notifications\MatchJoinedNotification;
use App\Notifications\MatchJoinRequestNotification;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class HomeController extends Controller
{
    public function viewMatch()
    {
        $user = Auth::user();
        if (!$user) {
            return $this->sendError("No user found.", [], 401);
        }
        $matches = PadelMatch::with('creator')
            ->where('level', $user->level)
            ->orderBy('id', 'desc')
            ->get();
        if ($matches->isEmpty()) {
            return $this->sendError("No matches found for your level.");
        }
        $client = new Client();
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        $formattedMatches = $matches->map(function ($match) use ($client, $apiKey) {
            $location = $this->getLocationFromCoordinates($client, $match->latitude, $match->longitude, $apiKey);
            $creator = $match->creator;
            $group = Group::where('match_id', $match->id)->first();
            $playerCount = $group ? $group->members()->count() : 0;
            $canJoin = $playerCount < 8;
            return [
                'id' => $match->id,
                'mind_text' => $match->mind_text,
                'level' => $match->level,
                'player_count' => $playerCount,
                'location' => $location,
                'can_join' => $canJoin,
                'creator' => [
                    'id' => $creator->id,
                    'full_name' => $creator->full_name,
                    'matches_played' => $creator->matches_played,
                    'image' => $creator->image ? url('Profile/', $creator->image) :  url('profile/','profile.jpg') ,
                    'level' => $creator->level,
                ],
                'created_at' => $match->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $match->updated_at->format('Y-m-d H:i:s'),
            ];
        })->filter(function ($match) {
            return $match['can_join'];
        });
        $canJoinCount = $formattedMatches->count();
        return $this->sendResponse([
            'total_matches' => $canJoinCount,
            'matches' => $formattedMatches,
        ], 'Matches retrieved successfully.');
    }

    private function getLocationFromCoordinates(Client $client, $latitude, $longitude, $apiKey)
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
    public function searchMatch(Request $request)
    {
        $request->validate([
            'keyword' => 'nullable|string|max:255',
        ]);
        $query = PadelMatch::query();
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('mind_text', 'like', '%' . $keyword . '%')
                ->orWhere('selected_level', 'like', '%' . $keyword . '%');
            });
        }
        $matches = $query->get();
        $client = new Client();
        $formattedMatches = $matches->map(function ($match) use ($client) {
            $location = $this->getLocationFromCoordinates($client, $match->latitude, $match->longitude, env('GOOGLE_MAPS_API_KEY'));
            $group = Group::where('match_id',$match->id)->first();
            $playerCount = $group->members()->count();
            $join =$playerCount ? $playerCount < 8 : true;

            return [
                'id' => $match->id,
                'mind_text' => $match->mind_text,
                'selected_level' => $match->selected_level,
                'level' => $match->level,
                'level_name' => $match->level_name,
                'location_address' => $location,
                'player_count' => $playerCount,
                'join'=> $join,
                'created_at' => $match->created_at->toDateTimeString(),
                'creator' => [
                    'id' => $match->creator->id,
                    'full_name' => $match->creator->full_name,
                    'matches_played' => $match->creator->matches_played,
                    'image' =>$match->creator->image ? url('Profile/',$match->creator->image) : url('avatar','profile.jpg') ,
                ],
            ];
        });
        return $this->sendResponse($formattedMatches, 'Matches retrieved successfully.');
    }
    public function joinMatch(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'id' => 'required|exists:padel_matches,id',
        ]);
        $pademMatchId =$request->id;
        $padelMatch = PadelMatch::where('level',$user->level)->where('id', $pademMatchId)->first();
        if (!$padelMatch) {
            return $this->sendError('No Match Found.');
        }
        $group = Group::where('match_id',$padelMatch->id)->first();
        $padelMatchMember = GroupMember::where('group_id', $group->id)
                                            ->where('user_id', $user->id)
                                            ->exists();
        if ($padelMatchMember) {
            return $this->sendError([], "You already exist in this community.");
        }
        $memberCount = $group->members()->count();
        if($memberCount < !8) {
            return $this->sendError("The community is full, you cannot join.");
        }
        try {
            GroupMember::create([
                "group_id"=> $group->id,
                "user_id"=> $user->id,
                'status'=>0,
            ]);
            $padelMatch->creator->notify(new MatchJoinRequestNotification($padelMatch, $user));
            return $this->sendResponse([], 'Successfully sent join community request.');
        } catch (\Exception $e) {
            return $this->sendError('An error occurred while trying to join the match: ' . $e->getMessage(), 500);
        }
    }
    public function homePage(Request $request)
    {
        try {
            $user = Auth::user();
            $homePage = [
                'upcomingMatch' => $this->upcommingMatch(),
                'nearbyClubs' => $this->nearByClubs($user),
                // 'notificationCount'=>$this->notificationCount($user)
            ];
            return $this->sendResponse($homePage, "Nearby clubs retrieved successfully.");
        } catch (\Exception $e) {
            return $this->sendError('Errors', $e->getMessage(), 500);
        }
    }
    // private function notificationCount($user)
    // {
    //     $notificationCount = $notifications->count();

    //     return response()->json([
    //         'success' => true,
    //         'count' => $notificationCount,
    //         'data' => $notifications,
    //     ]);
    // }
    private function upcommingMatch()
    {
        $user = Auth::user();
        if (!$user) {
            return $this->sendError("No user found.", [], 401);
        }
        $matches = PadelMatch::with('creator')
            ->orderBy('id', 'desc')
            ->paginate(20);
        if ($matches->isEmpty()) {
            return $this->sendError("No matches found for your level.");
        }
        $client = new Client();
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        $formattedMatches = $matches->map(function ($match) use ($client, $apiKey) {
            $location = $this->getLocationFromCoordinates($client, $match->latitude, $match->longitude, $apiKey);
            $creator = $match->creator;
            $group = Group::where('match_id', $match->id)->first();
            $playerCount = $group ? $group->members()->count() : 0;
            $canJoin = $playerCount < 8;
            return [
                'id' => $match->id,
                'mind_text' => $match->mind_text,
                'level' => $match->level,
                'player_count' => $playerCount,
                'location' => $location,
                'can_join' => $canJoin,
                'creator' => [
                    'id' => $creator->id,
                    'full_name' => $creator->full_name,
                    'matches_played' => $creator->matches_played,
                    'image' => $creator->image ? url('Profile/', $creator->image) :  url('profile/','profile.jpg') ,
                    'level' => $creator->level,
                ],
                'created_at' => $match->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $match->updated_at->format('Y-m-d H:i:s'),
            ];
        })->filter(function ($match) {
            return $match['can_join'];
        });
        $canJoinCount = $formattedMatches->count();
        return $this->sendResponse([
            'total_matches' => $canJoinCount,
            'matches' => $formattedMatches,
        ], 'Matches retrieved successfully.');
    }

    private function nearByClubs($user)
    {
        if (!$user || !$user->latitude || !$user->longitude) {
            return $this->sendError("User location is not available.", [], 400);
        }
        $userLatitude = $user->latitude;
        $userLongitude = $user->longitude;
        $distanceLimit = 10;

        $clubs = Club::selectRaw("
                *,
                (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
            ", [$userLatitude, $userLongitude, $userLatitude])
            ->having('distance', '<=', $distanceLimit)
            ->orderBy('distance')
            ->get();

        if ($clubs->isEmpty()) {
            return $this->sendError("No nearby clubs found.");
        }
        $formattedClubs = $clubs->map(function ($club) {
            return [
                'id' => $club->id,
                'club_name' => $club->club_name,
                'location' => $club->location,
                'distance' => round($club->distance, 2) . ' km',
                'banner' => $club->banner? url($club->banner) :url('avatar','club.jpg'),
                'website' => $club->website,
                'status' => $club->status,
                'created_at' => $club->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $club->updated_at->format('Y-m-d H:i:s'),
            ];
        });
        return $formattedClubs;
    }

    public function findMatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'keyword' => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors());
        }
        $query = PadelMatch::with('creator');
        if ($request->filled('keyword')) {
            $query->where('selected_level', 'like', '%' . $request->keyword . '%');
        }
        if ($request->filled('latitude') && $request->filled('longitude')) {
            $latitude = $request->latitude;
            $longitude = $request->longitude;
            $query->selectRaw(
                "*, (6371 * acos(cos(radians(?)) * cos(radians(latitude))
                * cos(radians(longitude) - radians(?))
                + sin(radians(?)) * sin(radians(latitude)))) AS distance",
                [$latitude, $longitude, $latitude]
            )->having('distance', '<', 10);
        }
        $matches = $query->get();
        $formattedMatches = $matches->map(function ($match) {
            $client = new Client();
            $apiKey = env('GOOGLE_MAPS_API_KEY');
            $location = $this->getLocationFromCoordinates( $client, $match->latitude, $match->longitude, $apiKey);
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
                'player_count' => $playerCount,
                'can_join' => $join,
                'created_at' => $match->created_at->toDateTimeString(),
                'creator' => [
                    'id' => $match->creator->id,
                    'full_name' => $match->creator->full_name,
                    'matches_played' => $match->creator->matches_played,
                    'image' => $match->creator->image ? url('Profile/', $match->creator->image) : url('avatar', 'profile.jpg'),
                ],
            ];
        });
        return $this->sendResponse($formattedMatches, 'Matches retrieved successfully.');
    }

    public function clubDetails(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user || !$user->latitude || !$user->longitude) {
            return $this->sendError("User location is not available.", [], 400);
        }
        $userLatitude = $user->latitude;
        $userLongitude = $user->longitude;
        $distanceLimit = 10;
        $club = Club::selectRaw("
                *,
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )) AS distance
            ", [$userLatitude, $userLongitude, $userLatitude])
            ->having('distance', '<=', $distanceLimit)
            ->where('id', $id)
            ->first();
        if (!$club) {
            return $this->sendError('Club not found or is too far away.', [], 404);
        }
        $banners = collect(json_decode($club->banners, true) ?? [])
            ->map(fn($banner) => url('uploads/banners/' . $banner))
            ->toArray();
        $mapNavigatorLink = "https://www.google.com/maps/dir/?api=1&destination={$club->latitude},{$club->longitude}&travelmode=driving";
        $formattedClub = [
            'id'          => $club->id,
            'club_name'   => $club->club_name,
            'location'    => $club->location,
            'description' => $club->description,
            'distance'    => round($club->distance, 2) . ' km',
            'banners'     => $banners,
            'website'     => $club->website,
            'map_navigator' => $mapNavigatorLink,
            'status'      => $club->status,
            'created_at'  => $club->created_at->format('Y-m-d H:i:s'),
            'updated_at'  => $club->updated_at->format('Y-m-d H:i:s'),
        ];

        return $this->sendResponse($formattedClub, 'Club details retrieved successfully.');
    }
}
