<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AfterMatchQuestionAnswer;
use App\Models\AnswerTrailMatchQuestion;
use App\Models\PadelMatch;
use App\Models\PadelMatchMemberHistory;
use App\Models\Questionnaire;
use App\Models\TrailMatch;
use App\Models\User;
use App\Models\Volunteer;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function normalMatchFeedback()
    {
        $padelMatches = PadelMatch::where('status', 'ended')->with('creator')->get();
        $matchIds = $padelMatches->pluck('id')->toArray();

        $padelGames = AfterMatchQuestionAnswer::whereIn('match_id', $matchIds)
                        ->with(['match.creator', 'user'])
                        ->get();

        $chunkedGames = $padelGames->chunk(4);
        $formattedData = $chunkedGames->map(function ($chunk) {
            return $chunk->map(function ($member) {
                $isCreator = $member->match->creator->id === $member->user->id;

                return [
                    'id' => $member->id,
                    'match_id' => $member->match_id,
                    'user_id' => $member->user->id ?? 'N/A',
                    'full_name' => $member->user->full_name ?? 'N/A',
                    'user_name' => strtolower($member->user->user_name ?? 'N/A'),
                    'email' => $member->user->email ?? 'N/A',
                    'matches_played' => $member->user->matches_played ?? 'N/A',
                    'level' => $member->user->level ?? 'N/A',
                    'level_name' => $member->user->level_name ?? 'N/A',
                    'image' => $member->user->image ? url('Profile/' . $member->user->image) : null,
                    'creator_name' => $isCreator ? $member->match->creator->full_name : false,
                    'questionnaire' => $this->getQuestionnaireDetailsWithAnswers(
                        json_decode($member->questionnaire_id, true),
                        json_decode($member->answer, true)
                    ),
                ];
            });
        });
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
        // Retrieve answers with related trail match questions, users, and clubs
        $answers = AnswerTrailMatchQuestion::with(['trailMatchQuestion', 'user', 'club'])->get();

        // Format the data
        $formattedData = $answers->map(function ($answer) {
            return [
                'id' => $answer->id,
                'trail_match_id' => $answer->trail_match_id,
                'user' => [
                    'id' => $answer->user->id,
                    'full_name' => $answer->user->full_name ?? 'N/A',
                    'email' => $answer->user->email ?? 'N/A',
                    'level' => $answer->user->level ?? 'N/A',
                    'matches_played' => $answer->user->matches_played ?? 'N/A',
                    'profile' => $answer->user->image ? url('Profile/' . $answer->user->image) : null,
                ],
                'question' => $answer->trailMatchQuestion->question ?? 'N/A',
                'answer' => $answer->answer,
                'created_at' => $answer->created_at->toDateTimeString(),
                'club' => $this->club($answer->trail_match_id),
                'volunteer' => $this->volunteer($answer->trail_match_id),
            ];
        });

        return $this->sendResponse($formattedData, 'Successfully retrieved trail match feedback.');
    }

    private function club($trail_match_id)
    {
        $trailMatch = TrailMatch::find($trail_match_id);
        if (!$trailMatch) {
            return null; // Return null instead of an error for a missing trail match
        }

        return [
            'id' => $trailMatch->club->id, // Assuming club relation is defined
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
                'image' => $volunteer->image ? url('uploads/volunteers/'. $volunteer->image) :null,
            ];
        })->toArray();
    }
}
