<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AfterMatchQuestionAnswer;
use App\Models\Feedback;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupMessage;
use App\Models\GroupMessageUser;
use App\Models\Invitation;
use App\Models\PadelMatch;
use App\Models\PadelMatchMember;
use App\Models\PadelMatchMemberHistory;
use App\Models\PrivateMessage;
use App\Models\TrailMatch;
use App\Models\User;
use App\Notifications\AcceptInvitationNotification;
use App\Notifications\EndMatchNotification;
use App\Notifications\GroupInvitationNotification;
use App\Notifications\JoinRequestAcceptedNotification;
use App\Notifications\MatchAcceptedNotification;
use App\Notifications\PadelMatchMemberAdded;
use App\Notifications\StartCommunityMatchNotificication;
use Auth;
use DB;
use File;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    protected $padelMatchController;

    public function __construct(PadelMatchController $padelMatchController)
    {
        $this->padelMatchController = $padelMatchController;
    }

    public function searchMember(Request $request)
    {
        return $this->padelMatchController->searchMember($request);
    }
    public function getInviteMembers($groupId)
    {
        try {
            $group = Group::find($groupId);
            if (!$group) {
                return $this->sendError('Group not found.');
            }
            $userIds = $group->members()->get()->pluck('id')->toArray();
            $user = Auth::user();
            if (!$user) {
                return $this->sendError("No user found.");
            }
            if (is_null($user->latitude) || is_null($user->longitude)) {
                return $this->sendError("User location is not available.");
            }
            $latitude = $user->latitude;
            $longitude = $user->longitude;
            $members = User::whereNotIn('id', $userIds)
                ->selectRaw("
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
    public function activeGroup($matchId)
    {
        $group = Group::with('members', 'messages.sender')->find($matchId);
        if (!$group) {
            return $this->sendError("Group not found.");
        }
        $isMember = $group->members->contains('id', auth()->id());
        if (!$isMember) {
            return $this->sendError("Access denied. You are not a member of this group.");
        }
        return $this->sendResponse($group->messages, "Group messages retrieved successfully.");
    }

    public function blockPrivateMessage(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|integer|exists:users,id',
        ]);
       $privateMessage = PrivateMessage::create([
            'sender_id' => Auth::user()->id,
            'recipient_id'=> $request->recipient_id,
            'block'=> true,
        ]);
        return response()->json([
            'message' => 'User blocked successfully.',
            'status'=> $privateMessage->block,
            'recipient_id'=> $privateMessage->recipient_id,
        ], 200);
    }
    public function unblockPrivateMessage(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|integer|exists:users,id',
        ]);
        $message = PrivateMessage::where('sender_id', Auth::user()->id)
                                 ->where('recipient_id', $request->recipient_id)
                                 ->where('block', true)
                                 ->first();
        if ($message) {
            $message->block = false;
            $message->save();
            return response()->json(['message' => 'User unblocked successfully.'], 200);
        }
        return response()->json(['message' => 'No block record found for the user.'], 404);
    }
    public function blockStatus(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|integer|exists:users,id',
        ]);
        $authId = Auth::id();
        $recipientId = $request->recipient_id;
        $authBlockedRecipient = PrivateMessage::where('sender_id', $authId)
                                                ->where('recipient_id', $recipientId)
                                                ->orderBy('id', 'desc')
                                                ->first();
        $recipientBlockedAuth = PrivateMessage::where('sender_id', $recipientId)
                                                ->where('recipient_id', $authId)
                                                ->orderBy('id', 'desc')
                                                ->first();
        $authUser = User::find($authId);
        $recipientUser = User::find($recipientId);
        $formattedAuthUser = $this->formatUserData($authUser);
        $formattedRecipientUser = $this->formatUserData($recipientUser);
        return response()->json([
            'message' => 'Block status retrieved successfully.',
            'data' => [
                'auth_blocked_recipient' => $authBlockedRecipient ? $authBlockedRecipient->block : false,
                'recipient_blocked_auth' => $recipientBlockedAuth ? $recipientBlockedAuth->block : false,
                'recipient_id' => $recipientId,
                'auth_id' => $authId,
                'auth_user' => $formattedAuthUser,
                'recipient_user' => $formattedRecipientUser,
            ],
        ], 200);
    }
    private function formatUserData($user)
    {
        if (!$user) {
            return null;
        }
        return [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'level' => $user->level,
            'matches_played' => $user->matches_played,
            'email' => $user->email,
            'image' => $user->image ? url('Profile/' . $user->image) : url('avatar/profile.jpg'),
        ];
    }
    public function startGame(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'match_id' => 'required|exists:padel_matches,id',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }
        $padelMatch = PadelMatch::find($request->match_id);
        if (!$padelMatch) {
            return $this->sendError('Match not found.', [], 404);
        }
        $group= Group::find($request->match_id);
        if(!$group){

        }
        $notApproved = PadelMatchMember::where('padel_match_id', $padelMatch->id)
            ->where('isApproved', false)
            ->exists();
        if ($notApproved) {
        return $this->sendError('Not all members are approved.', [], 400);
        }
        if ($padelMatch->status === 'started') {
            return $this->sendError('Match has already started.', [], 400);
        }
        $padelMatch->status = 'started';
        $padelMatch->save();
        if ($padelMatch->status === 'started') {
            $updates = AfterMatchQuestionAnswer::where('match_id', $padelMatch->id)
                ->where('answer', null)
                ->get();
            $staticAnswer = 'lowser person';
            foreach ($updates as $update) {
                $update->questionnaire_id = json_encode([1, 2, 3, 4, 5, 6, 7, 8, 9]);
                $update->answer = json_encode($staticAnswer);
                $update->save();
            }
        }
        $approvedMembers = PadelMatchMember::where('padel_match_id', $padelMatch->id)
        ->where('isApproved', true)
        ->get();

        foreach ($approvedMembers as $member) {
            $member->user->notify(new StartCommunityMatchNotificication($padelMatch));
        }
        return $this->sendResponse([], 'Game started successfully.');
    }
    public function endGame(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'match_id' => 'required|exists:padel_matches,id',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }
        $padelMatch = PadelMatch::find($request->match_id);
        if (!$padelMatch) {
            return $this->sendError('Match not found.', [], 404);
        }
        if ($padelMatch->status !== 'started') {
            return $this->sendError('Match is not currently in progress.', [], 400);
        }
        $members = PadelMatchMember::where('padel_match_id', $padelMatch->id)->get();
        if ($members->isEmpty()) {
            return $this->sendError('No members found for this match.', [], 404);
        }
        $padelMatch->status = 'ended';
        $padelMatch->save();
        foreach ($members as $member) {
            $user = User::find($member->user_id);
            if ($user) {
                $user->increment('matches_played');
                PadelMatchMemberHistory::create([
                    'padel_match_id' => $padelMatch->id,
                    'user_id' => $user->id,
                ]);
                Feedback::create([
                    'questionnaire_id' =>9,
                    'user_id' => $user->id,
                    'match_id' => $padelMatch->id,
                ]);
                AfterMatchQuestionAnswer::create([
                    'match_id' => $padelMatch->id,
                    'user_id'=> $user->id,
                    'questionnaire_id'=> json_encode([1,2,3,4,5,6,7,8,9]),
                    'answer'=> null,
                ]);
                $user->notify( new EndMatchNotification($padelMatch));
            }
        }
        return $this->sendResponse([], 'Game ended successfully.');
    }
    public function gameStatus($MatchId)
    {
        $padelMatch = TrailMatch::find($MatchId);
        if (!$padelMatch) {
            return $this->sendError('Match not found.', [], 404);
        }
        $status = [
            'match_id' => $padelMatch->id,
            'status' => $padelMatch->game_status ?? "wait for game start",
        ];
        return $this->sendResponse($status, 'Game status retrieved successfully.');
    }
    public function NormalgameStatus($MatchId)
    {
        $padelMatch = PadelMatch::find($MatchId);
        if (!$padelMatch) {
            return $this->sendError('Match not found.', [], 404);
        }
        $status = [
            'match_id' => $padelMatch->id,
            'status' => $padelMatch->status ?? "wait for game start",
        ];
        return $this->sendResponse($status, 'Game status retrieved successfully.');
    }
    public function removeGroupMember(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'group_id' => 'required|exists:groups,id',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }
        $group = Group::find($request->group_id);
        if (!$group) {
            return $this->sendError('Group not found.', [], 404);
        }
        $removedCount = 0;
        foreach ($request->user_ids as $userId) {
            $member = $group->members()->where('user_id', $userId)->first();
            if ($member) {
                $member->delete();
                $removedCount++;
            }
        }
        if ($removedCount === 0) {
            return $this->sendError('No members removed. Please check the user IDs.', [], 404);
        }
        return $this->sendResponse([], "$removedCount member(s) removed from the group successfully.");
    }
    public function PadelMatchMemberStatus($matchId)
    {
        $padelMatch = PadelMatch::find($matchId);
        if (!$padelMatch) {
            return $this->sendError('Match not found.', [], 404);
        }
        $membersStatus = PadelMatchMember::where('padel_match_id', $matchId)
            ->where('user_id', '!=', auth()->user()->id)
            ->orderBy('id', 'desc')
            ->take(4)
            ->get()
            ->map(function ($member) {
                return [
                    'id'            => $member->id,
                    'user_id'       => $member->user->id,
                    'full_name'     => $member->user->full_name,
                    'email'         => $member->user->email,
                    'level'         => $member->user->level,
                    'level_name'    => $member->user->level_name,
                    'matches_played'=> $member->user->matches_played,
                    'image'         => $member->user->image ? url('Profile/' . $member->user->image) : url('avatar/profile.jpg'),
                    'is_approved'   => $member->isApproved ? true : false,
                ];
            });
        if ($membersStatus->isEmpty()) {
            return $this->sendError('No members found for this match.', [], 404);
        }
        return $this->sendResponse($membersStatus, 'Members status retrieved successfully.');
    }
    public function userPrivateMessageMember()
    {
        $userId = Auth::id();
        $members = PrivateMessage::where('sender_id', $userId)
            ->orWhere('recipient_id', $userId)
            ->select('sender_id', 'recipient_id')
            ->distinct()
            ->get()
            ->flatMap(function ($message) use ($userId) {
                return [$message->sender_id === $userId ? $message->recipient_id : $message->sender_id];
            })
            ->unique();
        $userMembers = User::whereIn('id', $members)->get();
        if ($userMembers->isEmpty()) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'User not found.'
            ]);
        }
        $formattedMembers = $userMembers->map(function ($user) use ($userId) {
            $lastMessage = PrivateMessage::where(function ($query) use ($userId, $user) {
                $query->whereIn('sender_id', [$userId, $user->id])
                      ->whereIn('recipient_id', [$userId, $user->id]);
            })
            ->orderBy('created_at', 'desc')
            ->first();

            $unreadCount = PrivateMessage::where(function ($query) use ($userId, $user) {
                    $query->where('sender_id', $user->id)->where('recipient_id', $userId);
                })
                ->where('is_read', false)
                ->count();
            return [
                'user_id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'level' => $user->level,
                'matches_played' => $user->matches_played,
                'image' => $user->image ? url('Profile/', $user->image) : url('avatar/', 'profile.jpg'),
                'last_message' => $lastMessage ? $lastMessage->message : null,
                'last_message_time' => $lastMessage ? $lastMessage->created_at : null,
                'unread_count' => $unreadCount,
                'auth_user_id' => $userId,
            ];
        });
        return $this->sendResponse($formattedMembers, 'Private message members retrieved successfully.');
    }
    public function privateMessagesAsRead(Request $request)
    {
        $userId = $request->user_id;
        $authUserId = auth()->id();
        PrivateMessage::where(function ($query) use ($userId, $authUserId) {
                $query->where('sender_id', $userId)
                      ->orWhere('recipient_id', $userId)
                      ->orWhere('sender_id', $authUserId)
                      ->orWhere('recipient_id', $authUserId);
            })
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return $this->sendResponse([], 'All unread messages for the specified user and authenticated user have been marked as read.');
    }
    public function getPrivateMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipient_id' => 'required|exists:users,id',
            'per_page' => 'nullable|integer|min:1',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }
        $authUserId = Auth::id();
        $recipientId = $request->recipient_id;
        $receipent = User::find($recipientId);

        $perPage = $request->per_page ?? 15;
        $messages = PrivateMessage::with(['sender', 'recipient'])
            ->where(function ($query) use ($authUserId, $recipientId) {
                $query->whereIn('sender_id', [$authUserId, $recipientId])
                    ->whereIn('recipient_id', [$authUserId, $recipientId]);
            })
            ->where(function ($query) {
                $query->whereNotNull('message')
                    ->orWhereNotNull('images');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
        if ($messages->isEmpty()) {
            return $this->sendError([], 'No messages found.');
        }
        $generateImageUrl = function ($imagePath) {
            return $imagePath ? url("uploads/private_messages/", $imagePath) : null;
        };
        $formattedMessages = $messages->map(function ($message) use ($generateImageUrl) {
            $imageCollection = collect(json_decode($message->images, true) ?? []);
            return [
                'id' => $message->id,
                'message' => $message->message,
                'images' => $imageCollection->map(fn($image) => $generateImageUrl($image)),
                'is_read' => $message->is_read,
                'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                'sender' => [
                    'id' => $message->sender->id,
                    'full_name' => $message->sender->full_name,
                    'email' => $message->sender->email,
                    'matches_played' => $message->sender->matches_played,
                    'image' =>  $generateImageUrl($message->sender->image)
                                ? $generateImageUrl($message->sender->image)
                                : url('avatar/profile.jpg'),
                ],
            ];
        });
        return $this->sendResponse([
            'recipient_id' => $receipent->id,
            'recipient'=> [
                'id' => $receipent->id,
                'full_name' => $receipent->full_name,
                'email' =>  $receipent->email,
                'matches_played' =>  $receipent->matches_played,
                'level' =>  $receipent->level,
                'image' => $receipent->image
                        ? url('Profile/', $receipent->image)
                        : url('avatar/profile.jpg')
            ],
            'sender' => [
                'id' => $messages->first()->sender->id,
                'full_name' => $messages->first()->sender->full_name,
                'email' => $messages->first()->sender->email,
                'matches_played' => $messages->first()->sender->matches_played,
                'image' => $generateImageUrl($messages->first()->sender->image)
                            ? $generateImageUrl($messages->first()->sender->image)
                            : url('avatar/profile.jpg'),
            ],
            'messages' => $formattedMessages,
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'total_pages' => $messages->lastPage(),
                'total_items' => $messages->total(),
                'per_page' => $messages->perPage(),
            ]
        ], 'Private messages retrieved successfully.');
    }


    public function updateMessage(Request $request, $messageId)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'sometimes|string|max:500',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }
        $message = PrivateMessage::where('id', $messageId)
                    ->where('sender_id', Auth::id())
                    ->first();

        if (!$message) {
            return $this->sendError('Message not found or you are not authorized to update it.', [], 404);
        }
        $newImagePaths = [];
        if ($request->hasFile('images')) {
            $oldImages = json_decode($message->images, true) ?? [];
            foreach ($oldImages as $oldImage) {
                $oldImagePath = public_path($oldImage);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            foreach ($request->file('images') as $image) {
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/private_messages'), $imageName);
                $newImagePaths[] =  $imageName;
            }
        }
        $message->update([
            'message' => $request->message ?? $message->message,
            'images' => !empty($newImagePaths) ? json_encode($newImagePaths) : $message->images,
        ]);
        return $this->sendResponse($message, 'Message updated successfully.');
    }
    public function MemberMessage(Request $request, $userId)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'nullable|string|max:500',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }
        $recipient = User::find($userId);
        if (!$recipient) {
            return $this->sendError('Recipient user not found.', [], 404);
        }
        $blockCheck = PrivateMessage::where(function ($query) use ($userId) {
            $query->where('sender_id', Auth::id())
                  ->where('recipient_id', $userId)
                  ->where('block', true);
        })
        ->orWhere(function ($query) use ($userId) {
            $query->where('sender_id', $userId)
                  ->where('recipient_id', Auth::id())
                  ->where('block', true);
        })
        ->exists();
        if ($blockCheck) {
            return $this->sendResponse('You cannot send messages to this user. User is blocked.', []);
        }
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/private_messages/'), $imageName);
                $imagePaths[] = $imageName;
            }
        }
        $message = PrivateMessage::create([
            'sender_id' => Auth::id(),
            'recipient_id' => $userId,
            'message' => $request->message,
            'images' => json_encode($imagePaths),
            'is_read' => false,
        ]);
        if (!$message) {
            return $this->sendError("Message could not be sent. Please try again.", [], 500);
        }
        return $this->sendResponse($message, 'Message sent successfully.');
    }

    public function leaveGroup(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'group_id' => 'required|exists:groups,id',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 400);
        }
        $groupId = $request->group_id;
        $isMember = GroupMember::where('group_id', $groupId)
            ->where('user_id', $user->id)
            ->exists();
        if (!$isMember) {
            return $this->sendError('You are not a member of this group.', 404);
        }
        GroupMember::where('group_id', $groupId)
            ->where('user_id', $user->id)
            ->delete();
        return $this->sendResponse([], 'Successfully left the group.');
    }
    public function PadelMatchMemberAdd(Request $request, $matchId)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        // Check if match ID is provided
        if (!$matchId) {
            return $this->sendError('Match ID not found.', [], 404);
        }

        // Find the specified padel match
        $padelMatch = PadelMatch::find($matchId);
        if (!$padelMatch) {
            return $this->sendError('Match not found.', [], 404);
        }

        $members = $request->user_ids;
        $authId = auth()->user()->id;

        // Ensure the authenticated user is included in the members
        if (!in_array($authId, $members)) {
            $members[] = $authId;
        }

        // Check that exactly 4 unique members are selected
        if (count(array_unique($members)) !== 4) {
            return $this->sendError('You must select exactly 3 members for the match.', [], 400);
        }

        // Clear previous members for the match
        PadelMatchMember::where('padel_match_id', $padelMatch->id)->delete();

        // Add the new members
        foreach ($members as $userId) {
            $padelMatchMember = PadelMatchMember::create([
                'padel_match_id' => $padelMatch->id,
                'user_id' => $userId,
                'isApproved' => $userId === $authId,
            ]);

            $user = User::find($userId);
            $group = Group::where('match_id', $matchId)->first();
            if ($group) {
                $user->notify(new PadelMatchMemberAdded($group->name, $padelMatch, Auth::user(),$padelMatchMember));
            }
        }
        $padelMatch->status = null;
        $padelMatch->save();
        return $this->sendResponse([], 'Members added to the match successfully.');
    }

    public function acceptPadelMatch(Request $request, $matchId)
    {
        $padelMatch = PadelMatch::find($matchId);
        if (!$padelMatch) {
            return $this->sendError('Match not found.', [], 404);
        }
        if ($padelMatch->status === 'started') {
            return $this->sendError('Match has already started.', [], 400);
        }
        if ($padelMatch->status === 'ended') {
            return $this->sendError('Match has already ended.', [], 400);
        }
        $member = PadelMatchMember::where('padel_match_id', $padelMatch->id)
            ->where('user_id', auth()->user()->id)
            ->first();
        if (!$member) {
            return $this->sendError('User is not a member of this match.', [], 404);
        }
        $member->isApproved = true;
        $member->save();
        $creator = $padelMatch->creator;
        $creator->notify(new MatchAcceptedNotification($padelMatch,Auth::user()));

        $notifyId = $request->notify_id;
        if ($notifyId) {
            $user = Auth::user();
            $notification = $user->notifications()->find($notifyId);
            if ($notification) {
                $data = $notification->data;
                $data['status'] = true;
                $notification->data = $data;
                $notification->save();
            }
        }

        return $this->sendResponse([], 'Padel match accepted successfully.');
    }
    public function getGroupMember($matchId)
    {
        $user= Auth::user();
        $padelMatch = PadelMatch::find($matchId);
        if (!$padelMatch) {
            return $this->sendError('Match not found.', [], 404);
        }
        $group = Group::where('match_id', $padelMatch->id)->first();
        if (!$group) {
            return $this->sendError('Group not found.', [], 404);
        }
        $groupMembers = $group->members()->get();
        if ($groupMembers->isEmpty()) {
            return $this->sendError('No members found for this match.', [], 404);
        }
        $formattedMembers = $groupMembers->where('id','!=',$user->id)->map(function ($member) {
            return [
                'user_id'    => $member->id,
                'full_name'  => $member->full_name,
                'email'      => $member->email,
                'level'      => $member->level,
                'is_approved'=> $member->isApproved ?? false,
                'level_name' => $member->level_name,
                'image'      => $member->image ? url('Profile/' . $member->image) : url('avatar/profile.jpg'),
            ];
        });
        return $this->sendResponse($formattedMembers, 'Group members retrieved successfully.');
    }
    public function getUserGroup()
    {
        $user = Auth::user();

        // Get group IDs where the user is a member
        $groupIds = GroupMember::where('user_id', $user->id)->pluck('group_id');

        // Retrieve groups with creator and messages
        $groups = Group::whereIn('id', $groupIds)
            ->with('creator', 'messages')
            ->orderBy('id', 'desc')
            ->get();

        if ($groups->isEmpty()) {
            return $this->sendError('No groups found for this user.', [], 404);
        }

        $formattedGroups = $groups->map(function ($group) use ($user) {
            $lastMessage = $group->messages()->latest()->first();
            $unreadCount = GroupMessage::where('group_id', $group->id)
                ->where('is_read', true)
                ->whereRaw(' NOT JSON_CONTAINS(read_by, ?)', [json_encode(auth()->id())])
                ->count();

            return [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'match_id' => $group->match_id,
                'group_image' => $group->image ? url('uploads/group/', $group->image) : url('avatar', 'community.jpg'),
                'creator' => [
                    'id' => $group->creator->id,
                    'full_name' => $group->creator->full_name,
                ],
                'last_message' => $lastMessage ? [
                    'message' => $lastMessage->message,
                    'sent_at' => $lastMessage->created_at->toDateTimeString(),
                ] : null,
                'unread_count' => $unreadCount,
                'created_at' => $group->created_at->toDateTimeString(),
            ];
        });

        // Return formatted response
        return $this->sendResponse(
            [
                'data' => $formattedGroups,
                'user_id' => $user->id,
            ],
            'User groups retrieved successfully.'
        );
    }

    public function updateGroup(Request $request, $groupId)
    {
        // $validator = Validator::make($request->all(), [
        //    'name' => 'required|string|max:255',
        //     'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        // ]);
        // if ($validator->fails()) {
        //     return $this->sendError('Validation Error', $validator->errors(), 422);
        // }
        $group = Group::where('id', $groupId)
                      ->where('creator_id', Auth::id())
                      ->first();
        if (!$group) {
            return $this->sendError('Group not found or you are not the creator.', [], 404);
        }
        if ($request->hasFile('image')) {
            $newImage = $request->file('image');
            $newImageName = time() . '_' . $newImage->getClientOriginalName();
            $newImage->move(public_path('uploads/group/'), $newImageName);
        }
        $group->name = $request->name ?? $group->name;
        $group->image = $newImageName ?? $group->image;
        $group->save();
        return $this->sendResponse($group, 'Group updated successfully.');
    }
    public function storeGroupMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_id'=> 'required|exists:groups,id',
            'message' => 'nullable|required|string|max:1000',
            'images' => 'array',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:10240',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }
        $group = Group::find($request->group_id);
        if (!$group) {
            return $this->sendError('Group not found.');
        }
        try {
            $imagePaths = [];
            if ($request->has('images')) {
                foreach ($request->file('images') as $image) {
                    $imageName = time() . '_' . $image->getClientOriginalName();
                    $image->move(public_path('uploads/group_messages'), $imageName);
                    $imagePaths[] = $imageName;
                }
            }
            $user = Auth::user();
            $readBy = [$user->id];
            $message = $group->messages()->create([
                'group_id' => $group->id,
                'user_id' => $user->id,
                'message' => $request->message,
                'images' => json_encode($imagePaths),
                'is_read' => true,
                'read_by' => json_encode($readBy),

            ]);

            return $this->sendResponse($message, 'Message sent successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to send message.', ['error' => $e->getMessage()], 500);
        }
    }
    public function updateGroupMessage(Request $request, $messageId)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'nullable|string|max:255',
            'images' => 'nullable|array|max:4',
            'images.*'=> 'image|mimes:jpg,jpeg,png,bmp|max:10240',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }
        $groupMessage = GroupMessage::find($messageId);
        if (!$groupMessage) {
            return $this->sendError('Group message not found.', [], 404);
        }
        $existingImages = json_decode($groupMessage->images, true) ?? [];
        $uploadedImages = [];
        if ($request->has('images')) {
            foreach ($request->file('images') as $image) {
                $filename = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/group_messages'), $filename);
                $uploadedImages[] = $filename;
            }
            foreach ($existingImages as $oldImage) {
                $oldImagePath = public_path($oldImage);
                if (File::exists($oldImagePath)) {
                    File::delete($oldImagePath);
                }
            }
        }
        $finalImages = !empty($uploadedImages) ? $uploadedImages : $existingImages;
        $groupMessage->update([
            'message' => $request->input('message', $groupMessage->message),
            'images' => json_encode($finalImages),
        ]);
        $formattedMessage = [
            'id' => $groupMessage->id,
            'message' => $groupMessage->message,
            'images' => collect($finalImages)->map(function ($image) {
                return url($image);
            }),
            'is_read' => $groupMessage->is_read,
            'updated_at' => $groupMessage->updated_at->toDateTimeString(),
        ];
        return $this->sendResponse($formattedMessage, 'Group message updated successfully.');
    }
    public function deleteGroupMessage($messageId)
    {
        $groupMessage = GroupMessage::find($messageId);
        if (!$groupMessage) {
            return $this->sendError('Group message not found.', [], 404);
        }
        $images = json_decode($groupMessage->images, true) ?? [];
        foreach ($images as $image) {
            $imagePath = public_path($image);
            if (File::exists($imagePath)) {
                File::delete($imagePath);
            }
        }
        $groupMessage->delete();
        return $this->sendResponse([], 'Group message deleted successfully.');
    }
    public function messageIsRead(Request $request,$groupId)
    {
        $userId = auth()->user()->id;
        $group = Group::find($groupId);
        if(!$group)
        {
            return $this->sendError('Group not found.');
        }
        $unreadMessages = GroupMessage::where('group_id', $group->id)
            ->whereJsonDoesntContain('read_by', $userId)
            ->get();
        if ($unreadMessages->isEmpty()) {
            return $this->sendResponse([], 'No unread messages for this group.');
        }
        foreach ($unreadMessages as $message) {
            $readBy = json_decode($message->read_by, true) ?? [];
            if (!in_array($userId, $readBy)) {
                $readBy[] = $userId;
            }
            $message->read_by = json_encode(array_unique($readBy));
            $message->is_read = true;
            $message->save();
        }
    }


    public function getGroupMessages($groupId,Request $request)
    {
        $user = Auth::user();
        $group = Group::with(['messages.sender'])->find($groupId);
        if (!$group) {
            return $this->sendError('Group not found.', [], 404);
        }
        $padelMatch = PadelMatch::where('id', $group->match_id)->first();
        $groupMemberCount = $group->members()->count();

        $members = $group->members()->get()
        ->where('id','!=',$user->id)
        ->map(function ($member) use ($padelMatch) {
            return [
                'id' => $member->id,
                'full_name' => $member->full_name,
                'email' => $member->email,
                'level' => $member->level,
                'level_name' => $member->level_name,
                'matches_played' => $member->matches_played,
                'image' => $member->image ? url('Profile/' . $member->image) : url('avatar/profile.jpg'),
            ];
        });

        $groupName = $group->name;
        $groupImage = $group->image ? url('uploads/group/' . $group->image) : url('avatar/community.jpg');
        $formattedMessages = $group->messages()
            ->orderBy('id', 'desc')
            ->paginate($request->per_page)
            ->through(function ($message) use ($groupMemberCount, $groupName, $groupImage) {
                $imageCollection = collect(json_decode($message->images, true) ?? []);
                return [
                    'message_id' => $message->id,
                    'sender_id' => $message->sender->id,
                    'sender_name' => $message->sender->full_name,
                    'image' => $message->sender->image
                        ? url('Profile/' . $message->sender->image)
                        : url('avatar/profile.jpg'),
                    'message' => $message->message,
                    'images' => $imageCollection->map(function ($image) {
                        return $image ? url("uploads/group_messages/" . $image) : null;
                    })->filter(),
                    'is_read' => $message->is_read,
                    'created_at' => $message->created_at->toDateTimeString(),
                ];
            });
        $groupMessages = [
            'messages' => $formattedMessages,
            'group_members' => $groupMemberCount,
            'group_name' => $groupName,
            'padel_match' => $padelMatch,
            'group_image' => $groupImage,
            'members' => $members,
        ];
        return $this->sendResponse($groupMessages, 'Group messages retrieved successfully.');
    }
    public function inviteMembers(Request $request, $groupId)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }
        $group = Group::where('id', $groupId)
                      ->where('creator_id', Auth::id())
                      ->first();
        if (!$group) {
            return $this->sendError('Group not found or you are not the creator.', [], 404);
        }
        $invitations = [];
        $errors = [];
        foreach ($request->user_ids as $userId) {
            $existingMember = $group->members()->where('user_id', $userId)->exists();
            if ($existingMember) {
                $errors[] = "User already member of the group.";
                continue;
            }
            $invitation = Invitation::create([
                'group_id' => $groupId,
                'invited_user_id' => $userId,
                'is_accepted' => 0
            ]);
            $invitedUser = User::find($userId);
            if ($invitedUser) {
                $invitedUser->notify(new GroupInvitationNotification($invitation));
                $invitations[] = $invitation;
            } else {
                $errors[] = "User not found.";
            }
        }
        if (!empty($errors)) {
            return $this->sendError('Some invitations were not sent.', $errors);
        }
        return $this->sendResponse($invitations, 'Invitations sent successfully.');
    }
    public function acceptInvitation(Request $request, $invitationId)
    {
        $validator = Validator::make($request->all(), [
            'notify_id' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }
        $invitation = Invitation::where('id', $invitationId)
                                ->where('invited_user_id', Auth::id())
                                ->orderBy('id','desc')
                                ->first();
        if (!$invitation) {
            return $this->sendError('Invitation not found or you are not invited.', [], 404);
        }
        $group = Group::find($invitation->group_id);
        if ($group->members()->where('user_id', Auth::id())->exists()) {
            return $this->sendError('You are already a member of this group.');
        }
        $group->members()->attach(Auth::id());

        $invitation->is_accepted= 1;
        $invitation->save();
        $notifyId = $request->notify_id;
        if ($notifyId) {
            $user = Auth::user();
            $notification = $user->notifications()->find($notifyId);
            if ($notification) {
                $data = $notification->data;
                $data['invitation_status'] = 1;
                $notification->data = $data;
                $notification->save();
            }
        }
        $group->creator->notify(new AcceptInvitationNotification(Auth::user()));
        return $this->sendResponse([], 'Invitation accepted successfully.');
    }
    public function denyRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notify_id' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }
        $notifyId = $request->notify_id;
        $user = Auth::user();
        if ($notifyId && $user) {
            $notification = $user->notifications()->find($notifyId);
            if ($notification) {
                $notification->delete();
                return $this->sendResponse([], 'Successfully denied or rejeted.');
            }
            return $this->sendError('Notification not found.', [], 404);
        }
        return $this->sendError('User or notification ID not found.', [], 404);
    }

    public function acceptGroupMemberRequest(Request $request, $matchId)
    {
        $request->validate([
            'user_id'  => 'required|exists:users,id',
            'notify_id' => 'required|exists:notifications,id', // Ensure notify_id is provided
        ]);

        $match = PadelMatch::find($matchId);
        if (!$match) {
            return $this->sendError('Not found match', [], 404);
        }

        $group = Group::where('match_id', $match->id)->first();
        if (!$group) {
            return $this->sendError('Group not found.', [], 404);
        }

        $membershipRequest = GroupMember::where('group_id', $group->id)
            ->where('user_id', $request->user_id)
            ->where('status', false)
            ->first();
        if (!$membershipRequest) {
            return $this->sendError('Member already approved.', [], 404);
        }

        $membershipRequest->status = true;
        $membershipRequest->save();

        // Query the notifications table directly
        $notification = DB::table('notifications')->where('id', $request->notify_id)->first();
        if ($notification) {
            DB::table('notifications')->where('id', $request->notify_id)->update(['read_at' => now()]);
        } else {
            return $this->sendError('Notification not found.', [], 404);
        }

        $user = User::find($request->user_id);
        $user->notify(new JoinRequestAcceptedNotification($group,$match));

        return $this->sendResponse([], 'Community request accepted successfully.');
    }

}
