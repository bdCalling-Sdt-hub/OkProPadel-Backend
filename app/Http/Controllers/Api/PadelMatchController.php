<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\PadelMatch;
use App\Models\User;
use App\Notifications\PadelMatchCreatedNotification;
use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PadelMatchController extends Controller
{
    public function levelWithLevelName()
    {
        try{
            $user = Auth::user();
            if (!$user) {
                return $this->sendError("No user found.");
            }
            $level = $user->level.'('.$user->level_name.')';
            return $this->sendResponse([
                'level' => $level
            ], "Level and level name retrieved successfully.");
        }catch (\Exception $e) {
            return $this->sendError('An error occurred: ' . $e->getMessage(), [], 500);
        }
    }
    public function members()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->sendError("No user found.");
            }
            if (is_null($user->latitude) || is_null($user->longitude)) {
                return $this->sendError("User location is not available.");
            }
            $latitude = $user->latitude;
            $longitude = $user->longitude;
            $members = User::selectRaw("
                    id, full_name, level, level_name, image, latitude, longitude,
                    (6371 * acos(cos(radians(?)) * cos(radians(latitude))
                    * cos(radians(longitude) - radians(?))
                    + sin(radians(?)) * sin(radians(latitude)))) AS distance
                ", [$latitude, $longitude, $latitude])
                ->where('status', 'active')
                ->where('role', 'MEMBER')
                ->where('id', '!=', $user->id)
                ->having('distance', '<=', 10)
                ->orderBy('distance', 'ASC')
                ->get();

            if ($members->isEmpty()) {
                return $this->sendError('No members found within 10 km.');
            }
            $formattedMembers = $members->map(function ($member) {
                return [
                    'id' => $member->id,
                    'full_name' => $member->full_name,
                    'level' => $member->level,
                    'level_name' => $member->level_name,
                    'image' => $member->image ? url('Profile/' . $member->image) : url('avatar/profile.jpg'),
                    'distance' => round($member->distance, 2) . ' km',
                ];
            });
            $formattedResponse = [
                'total_members' => $members->count(),
                'members' => $formattedMembers,
            ];
            return $this->sendResponse($formattedResponse, 'Nearby members retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError('An error occurred: ' . $e->getMessage(), [], 500);
        }
    }


    public function searchMember(Request $request)
    {

        try{
            $validator = Validator::make($request->all(), [
                'keyword' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors(), 422);
            }
            $query = User::where('status', 'active')->where('role','MEMBER');
            if ($request->has('keyword')) {
                $query->where('full_name', 'LIKE', '%' . $request->input('keyword') . '%');
            }
            $members = $query->paginate(20);
            if ($members->isEmpty()) {
                return $this->sendError('No members found.');
            }
            return $this->sendResponse([
                'data' => $members->items(),
                'meta' => [
                    'current_page' => $members->currentPage(),
                    'total_pages' => $members->lastPage(),
                    'total_members' => $members->total(),
                    'per_page' => $members->perPage(),
                ],
            ], 'Members retrieved successfully.');

        }catch (\Exception $e) {
            return $this->sendError('An error occurred: ' . $e->getMessage(), [], 500);
        }
    }
    public function padelMatchCreate(Request $request)
    {
        DB::beginTransaction();
        try{
            $validator = Validator::make($request->all(), [
                'latitude'      => 'required|string',
                'longitude'     => 'required|string',
                'mind_text'     => 'required|string|max:120',
                'selected_level' => 'required|string|max:100',
                'members'       => 'array|min:1|max:8',
                'members.*'     => 'exists:users,id',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 422);
            }
            $selectedLevel = $request->selected_level;
            preg_match('/(\d+)\((.*?)\)/', $selectedLevel, $matches);
            $level = isset($matches[1]) ? $matches[1] : null;
            $level_name = isset($matches[2]) ? trim($matches[2]) : null;

            if (strlen($level_name) > 100) {
                return $this->sendError('Level name is too long.', 400);
            }
            $members = $request->input('members');
            $members []= auth()->user()->id;
            $memberCount = count($members);
            if($memberCount < !9){
                return $this->sendError('Member full in this match.', 400);
            }
            $padelMatch = PadelMatch::create([
                'latitude'      => $request->latitude,
                'longitude'     => $request->longitude,
                'mind_text'     => $request->mind_text,
                'selected_level' => $request->selected_level,
                'level'         => $level,
                'level_name'    => $level_name,
                'creator_id'    => auth()->user()->id,
            ]);
            $group = Group::create([
                'name' => "new community",
                'match_id' => $padelMatch->id,
                'creator_id'=> auth()->user()->id,
                'image'=> null
            ]);
            $group->members()->attach($members);
            DB::commit();
            foreach ($members as $memberId) {
                $member = User::find($memberId);
                if ($member) {
                    $member->notify(new PadelMatchCreatedNotification($padelMatch,Auth::user()));
                }
            }
            $admin = User::where('role','ADMIN')->first();
            $admin->notify(new PadelMatchCreatedNotification($padelMatch,Auth::user()));
            return $this->sendResponse($padelMatch, 'Padel match and group created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('An error occurred: ' . $e->getMessage(), [], 500);
        }
    }
    public function deletePadelMatch($id)
    {
        $padelMatch = PadelMatch::find($id);
        if (!$padelMatch) {
            return $this->sendError('Padel match not found.', [], 404);
        }
        $padelMatch->delete();
        return $this->sendResponse([], 'Padel match deleted successfully.');
    }



}
