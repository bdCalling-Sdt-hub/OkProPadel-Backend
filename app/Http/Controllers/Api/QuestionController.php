<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AfterMatchQuestionAnswer;
use App\Models\Feedback;
use App\Models\PadelMatch;
use App\Models\Question;
use App\Models\Questionnaire;
use Illuminate\Http\Request;
use Validator;

class QuestionController extends Controller
{
    public function getAfterMatchQuestion($matchId)
    {
        try {
            $questions = Questionnaire::whereBetween('id', [1, 8])->get();
            $question9 = Questionnaire::find(9);
            if (!$question9) {
                return $this->sendError('Questionnaire with ID 9 not found.', [], 404);
            }
            $feedbacks = Feedback::where('questionnaire_id', 9)
                ->where('match_id', $matchId)
                ->orderBy('id', 'desc')
                ->take(4)
                ->where('user_id','!=',auth()->user()->id)
                ->with('user')
                ->get();
            $formattedQuestions = $questions->map(function ($question) {
                return [
                    'id' => $question->id,
                    'question' => $question->question,
                    'type' => $question->type,
                    'options' => json_decode($question->options, true),
                ];
            });
            $question9WithFeedback = [
                'id' => $question9->id,
                'question' => $question9->question,
                'type' => $question9->type,
                'options' => $feedbacks->map(function ($feedback) {
                    return [
                        'id' => $feedback->id,
                        'user' => [
                            'id' => $feedback->user->id,
                            'full_name' => $feedback->user->full_name,
                            'email' => $feedback->user->email,
                            'level' => $feedback->user->level,
                            'level_name' => $feedback->user->level_name,
                            'image' => $feedback->user->image ? url('Profile/'. $feedback->user->image) : url('avatar/profile.jpg'),
                        ],
                        'response_options' => [
                            'ok'=> 'ok',
                            'high'=>'high',
                            'low'=>'low',
                        ],
                        'created_at' => $feedback->created_at->toDateTimeString(),
                    ];
                }),
            ];
            // Concatenate questions (1 to 8) with question 9
            $allQuestions = $formattedQuestions->push($question9WithFeedback);

            return $this->sendResponse($allQuestions, 'Questions and feedback retrieved successfully!');
        } catch (\Exception $e) {
            return $this->sendError('An error occurred while fetching data.', [$e->getMessage()], 500);
        }
    }
    public function afterMatchQuestion(Request $request, $matchId)
    {
        $match = PadelMatch::find($matchId);
        if (!$match) {
            return $this->sendError('Match not found.');
        }
        $validatedData = $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|integer|exists:questionnaires,id',
            'answers.*.answer' => 'required',
        ]);
        $userId = $request->user()->id;
        $updates = AfterMatchQuestionAnswer::where('match_id', $match->id)
            ->where('answer', null)
            ->where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->take(4)
            ->get();
        if($updates){
            $answers = array_column($validatedData['answers'], 'answer');
            foreach ($updates as $update) {
                $update->update([
                    'answer' => json_encode($answers),
                ]);
            }
            return $this->sendResponse('Successfully stored.', []);
        }else{
            return $this->sendError('Yor are not eligible for answer.');
        }
    }
    public function getQuestion()
    {
        $questions = Question::orderBy("id", "desc")->where('status',1)->get();
        if ($questions->isEmpty()) {
            return response()->json([
                "status"=> "error",
                "message"=> "No Question Found."
                ],404);
        }
        $formattedQuestions = $questions->map(function ($question) {
            return [
                'id' => $question->id,
                'question' => $question->question,
                'options' => json_decode($question->options, true),
                'question_2' => $question->question_2,
                'options_2' => json_decode($question->options_2, true),
                'question_es'=> $question->question_es,
                'status'=> $question->status,
                'created_at' => $question->created_at->toDateTimeString(),
                'updated_at' => $question->updated_at->toDateTimeString(),
            ];
        });

        $response = [
            'data' => $formattedQuestions,
        ];

        return $this->sendResponse($response, "Questions retrieved successfully.");
    }
    public function question(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "question" => "required|string|max:255",
            "A" => "required|string|max:255",
            "B" => "required|string|max:255",
            "C" => "required|string|max:255",
            "D" => "required|string|max:255",

            "question_2" => "required|string|max:255",
            "A_2" => "required|string|max:255",
            "B_2" => "required|string|max:255",
            "C_2" => "required|string|max:255",
            "D_2" => "required|string|max:255",
        ]);
        if ($validator->fails()) {
            return $this->sendError("Validation Error", $validator->errors());
        }
        $question = new Question();
        $question->question = $request->question;
        $question->question_2 = $request->question_2;
        $options = [
            'A' => [
                'value' => 1,
                'option' => $request->A,
            ],
            'B' => [
                'value' => 2,
                'option' => $request->B,
            ],
            'C' => [
                'value' => 3,
                'option' => $request->C,
            ],
            'D' => [
                'value' => 4,
                'option' => $request->D,
            ],
        ];
        $options_2 = [
            'A_2' => [
                'value' => 1,
                'option' => $request->A_2,
            ],
            'B_2' => [
                'value' => 2,
                'option' => $request->B_2,
            ],
            'C_2' => [
                'value' => 3,
                'option' => $request->C_2,
            ],
            'D_2' => [
                'value' => 4,
                'option' => $request->D_2,
            ],
        ];
        $question->options = json_encode($options);
        $question->options_2 = json_encode($options_2);
        $question->status= true;
        $question->question_es = $request->question_es;
        $question->save();
        return $this->sendResponse([
            'id' => $question->id,
            'question' => $question->question,
            'options' => json_decode($question->options),
            'question_2' => $question->question_2,
            'options_2' => json_decode($question->options_2),
            'question_es' => $question->question_es,
            'status' => $question->status,
            'created_at' => $question->created_at,
            'updated_at' => $question->updated_at,
        ], "Question created successfully.");
    }
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            "question" => "required|string|max:255",
            "A" => "required|string|max:255",
            "B" => "required|string|max:255",
            "C" => "required|string|max:255",
            "D" => "required|string|max:255",

            "question_2" => "required|string|max:255",
            "A_2" => "required|string|max:255",
            "B_2" => "required|string|max:255",
            "C_2" => "required|string|max:255",
            "D_2" => "required|string|max:255",

            'status'=>'boolean'
        ]);
        if ($validator->fails()) {
            return $this->sendError("Validation Error", $validator->errors());
        }
        $question = Question::find($id);
        if (!$question) {
            return $this->sendError("Question not found", [], 404);
        }
        if ($request->has('A') || $request->has('B') || $request->has('C') || $request->has('D')) {
            $options = json_decode($question->options, true);

            if ($request->has('A')) {
                $options['A']['option'] = $request->A;
            }
            if ($request->has('B')) {
                $options['B']['option'] = $request->B;
            }
            if ($request->has('C')) {
                $options['C']['option'] = $request->C;
            }
            if ($request->has('D')) {
                $options['D']['option'] = $request->D;
            }
            $question->options = json_encode($options);
        }
        if ($request->has('A_2') || $request->has('B_2') || $request->has('C_2') || $request->has('D_2')) {
            $options_2 = json_decode($question->options_2, true);

            if ($request->has('A_2')) {
                $options['A_2']['option'] = $request->A_2;
            }
            if ($request->has('B_2')) {
                $options['B_2']['option'] = $request->B_2;
            }
            if ($request->has('C_2')) {
                $options['C_2']['option'] = $request->C_2;
            }
            if ($request->has('D_2')) {
                $options['D_2']['option'] = $request->D_2;
            }
            $question->options_2 = json_encode($options_2);
        }
        $question->question = $request->question;
        $question->question_2 = $request->question_2;
        $question->status = $request->status;
        $question->question_es = $request->question_es;
        $question->save();

        return $this->sendResponse([
            'id' => $question->id,
            'question' => $question->question,
            'options' => json_decode($question->options),
            'question_2' => $question->question_2,
            'options_2' => json_decode($question->options_2),
            'status' => $question->status,
            'question_es' => $question->question_es,
            'created_at' => $question->created_at,
            'updated_at' => $question->updated_at,
        ], "Question updated successfully.");
    }
    public function delete($id)
    {
        $question = Question::find($id);
        if (!$question) {
            return $this->sendError("Question not found", [], 404);
        }
        $question->delete();

        return $this->sendResponse([], "Question deleted successfully.");
    }


}
