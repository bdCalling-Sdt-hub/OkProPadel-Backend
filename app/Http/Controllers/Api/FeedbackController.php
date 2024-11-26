<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AfterMatchQuestionAnswer;
use App\Models\AnswerTrailMatchQuestion;
use App\Models\Group;
use App\Models\PadelMatch;
use App\Models\PadelMatchMemberHistory;
use App\Models\Questionnaire;
use App\Models\TrailMatch;
use App\Models\TrailMatchQuestion;
use App\Models\User;
use App\Models\Volunteer;
use App\Notifications\AdjustLevelNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class FeedbackController extends Controller
{
    public function normalMatchFeedback()
    {
        $padelMatches = PadelMatch::where('status', 'ended')->with(['creator'])->get();
        $matchIds = $padelMatches->pluck(['id'])->toArray();

        $padelGames = AfterMatchQuestionAnswer::whereIn('match_id', $matchIds)
                        ->with(['match.creator', 'user'])
                        ->get();
        $formattedData = $padelGames->map(function ($member) {
        $group = Group::where('match_id',$member->match_id)->first();
            return [
                'id' => $member->id,
                'match_id' => $member->match_id,
                'group' => $group->name,
                'group_image' => $group->image ? url('uploads/group/',$group->image) : url('avatar/profile.jpg'),
                'creator_name' => $member->match->creator->full_name,
                'user_id' => $member->user->id ?? 'N/A',
                'full_name' => $member->user->full_name ?? 'N/A',
                'email' => $member->user->email ?? 'N/A',
                'image' => /* $member->user->image ? url('Profile/',$member->user->image) : */ url('avatar/profile.jpg'),


                'adjust_status' => $member->user->adjust_level ?? 'N/A',
                'matches_played' => $member->user->matches_played ?? 'N/A',
                'level' => $member->user->level ?? 'N/A',
                'level_name' => $member->user->level_name ?? 'N/A',
                'questionnaire' => $this->getQuestionnaireDetailsWithAnswers(
                    json_decode($member->questionnaire_id, true),
                    json_decode($member->answer, true)
                ),
            ];
        })->toArray();
        return $this->sendResponse($formattedData, 'Successfully retrieved data.');
    }
    private function getQuestionnaireDetailsWithAnswers($questionnaireIds, $answers)
    {
        $questionnaireIds = is_array($questionnaireIds) ? $questionnaireIds : [];
        $answers = is_array($answers) ? $answers : [];
        if (empty($questionnaireIds)) {
            return [];
        }
        $questionnaires = Questionnaire::whereIn('id', $questionnaireIds)->get();
        return $questionnaires->map(function ($questionnaire, $index) use ($answers) {
            return [
                'id' => $questionnaire->id,
                'question' => $questionnaire->question,
                'answer' => $answers[$index] ?? 'N/A',
            ];
        })->toArray();
    }
    public function trailMatchFeedback()
    {
        $answers = AnswerTrailMatchQuestion::with(['trailMatchQuestion', 'user', 'club'])->get();
        $formattedData = $answers->map(function ($answer) {
            return [
                'id' => $answer->id,
                'trail_match_id' => $answer->trail_match_id,
                'user_id' => $answer->user->id,
                'full_name' => $answer->user->full_name ?? 'N/A',
                'adjust_status' => $answer->user->adjust_level ?? 'N/A',
                'email' => $answer->user->email ?? 'N/A',
                'level' => $answer->user->level ?? 'N/A',
                'level_name' => $answer->user->level_name ?? 'N/A',
                'matches_played' => $answer->user->matches_played ?? 'N/A',
                'profile' => $answer->user->image ? url('Profile/' . $answer->user->image) : url('avatar/profile'),
                'trail_match_question_answers' => $this->getTrailMatchQuestionnaireDetailsWithAnswers(
                            json_decode($answer->trail_match_question_id, true),
                            json_decode($answer->answer, true)
                    ),
                'created_at' => $answer->created_at->toDateTimeString(),
                'club' => $this->club($answer->trail_match_id),
                'volunteer' => $this->volunteer($answer->trail_match_id),
            ];
        });
        return $this->sendResponse($formattedData, 'Successfully retrieved trail match feedback.');
    }
    private function getTrailMatchQuestionnaireDetailsWithAnswers($trailMatchQuestionIds,$answers)
    {
        $questionnaireIds = is_array($trailMatchQuestionIds) ? $trailMatchQuestionIds : [];
        $answers = is_array($answers) ? $answers : [];
        if (empty($questionnaireIds)) {
            return [];
        }
        $questionnaires = TrailMatchQuestion::whereIn('id', $questionnaireIds)->get();
        return $questionnaires->map(function ($questionnaire, $index) use ($answers) {
            return [
                'id' => $questionnaire->id,
                'question' => $questionnaire->question,
                'answer' => $answers[$index] ?? 'N/A',
            ];
        })->toArray();
    }
    private function club($trail_match_id)
    {
        $trailMatch = TrailMatch::find($trail_match_id);
        if (!$trailMatch) {
            return null;
        }
        return [
            'id' => $trailMatch->club->id,
            'club_name' => $trailMatch->club->club_name ?? 'N/A',
        ];
    }
    private function volunteer($trail_match_id)
    {
        $trailMatch = TrailMatch::find($trail_match_id);
        if (!$trailMatch) {
            return $this->sendError('Not found trail match.');
        }
        $volunteerIds = json_decode($trailMatch->volunteer_id);
        if (!is_array($volunteerIds)) {
            $volunteerIds = [$volunteerIds];
        }
        $volunteers = Volunteer::whereIn('id', $volunteerIds)->get();
        return $volunteers->map(function ($volunteer) {
            return [
                'id' => $volunteer->id,
                'name' => $volunteer->name ?? 'N/A',
                'email' => $volunteer->email ?? 'N/A',
                'image' => $volunteer->image ? url('uploads/volunteers/'. $volunteer->image) : url('avatar/profile.jpg'),
            ];
        })->toArray();
    }
    public function adjustLevel(Request $request, $userId)
    {

        $validator = Validator::make($request->all(), [
            'action' => 'required|in:up,down',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }
        $user = User::find($userId);
        if (!$user) {
            return $this->sendError('User not found.', [], 404);
        }
        $minLevel = 1;
        $maxLevel = 5;
        $levelNames = [
            1 => 'Beginner',
            2 => 'Lower-Intermediate',
            3 => 'Intermediate',
            4 => 'Advanced',
            5 => 'Professional',
        ];
        $action = $request->action;
        if (($action === 'up' && $user->level >= $maxLevel) || ($action === 'down' && $user->level <= $minLevel)) {
            return $this->sendError('Invalid action or level limits reached.');
        }
        $user->level += ($action === 'up') ? 1 : -1;
        $user->level_name = $levelNames[$user->level] ?? 'Unknown';
        $user->adjust_level = $request->adjust_status;
        $user->save();
        $user->notify(new AdjustLevelNotification($user));
        return $this->sendResponse([
            'level' => $user->level,
            'level_name' => $user->level_name,
            'adjust_status' => $user->adjust_level,
        ], 'User level adjusted successfully.');
    }
}
