<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnswerTrailMatchQuestion;
use App\Models\TrailMatchQuestion;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TrailMatchQuestionController extends Controller
{
    public function getTrailMatchQuestion()
    {
        $questions = TrailMatchQuestion::orderBy("id", "desc")->where('status',1)->get();
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
                'question_es' =>$question->question_es,
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


    public function trailMatchQuestion(Request $request)
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

        $question = new TrailMatchQuestion();
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
        $question->question_es = $request->question_es;
        $question->status= true;
        $question->save();
        return $this->sendResponse([
            'id' => $question->id,
            'question' => $question->question,
            'options' => json_decode($question->options),
            'question_2' => $question->question_2,
            'options_2' => json_decode($question->options_2),
            'question_es' =>$question->question_es,
            'status' => $question->status,
            'created_at' => $question->created_at,
            'updated_at' => $question->updated_at,
        ], "Question created successfully.");
    }

    public function updateTrailMatchQuestion(Request $request, $id)
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
            "status"=> "boolean",
        ]);

        if ($validator->fails()) {
            return $this->sendError("Validation Error", $validator->errors());
        }

        $question = TrailMatchQuestion::find($id);
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
        $question->question_es = $request->question_es;
        $question->status = $request->status;
        $question->save();

        return $this->sendResponse([
            'id' => $question->id,
            'question' => $question->question,
            'question_2' => $question->question_2,
            'options' => json_decode($question->options),
            'options_2' => json_decode($question->options_2),
            'question_es'=>$question->question_es,
            'status' => $question->status,
            'created_at' => $question->created_at,
            'updated_at' => $question->updated_at,
        ], "Question updated successfully.");
    }
    public function deleteTrailMatchQuesiton($id)
    {
        $question = TrailMatchQuestion::find($id);
        if (!$question) {
            return $this->sendError("Question not found", [], 404);
        }
        $question->delete();

        return $this->sendResponse([], "Question deleted successfully.");
    }
    public function answerTrailMatchQuestion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trail_match_id' => 'required|exists:trail_matches,id',
            'answers' => 'required|array',
            'answers.*.trail_match_question_id' => 'required|exists:trail_match_questions,id',
            'answers.*.answer' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => "Validation Error",
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $trailMatchQuestionIds = [];
            $answers = [];
            if (is_array($request->answers)) {
                foreach ($request->answers as $answerData) {
                    if (isset($answerData['trail_match_question_id']) && isset($answerData['answer'])) {
                        $trailMatchQuestionIds[] = $answerData['trail_match_question_id'];
                        $answers[] = $answerData['answer'];
                    }
                }
                $answer = AnswerTrailMatchQuestion::create([
                    'trail_match_id' => $request->trail_match_id,
                    'user_id' => auth()->id(),
                    'trail_match_question_id' => json_encode($trailMatchQuestionIds),
                    'answer' => json_encode($answers)
                ]);
                return response()->json([
                    'success' => true,
                    'message' => "Answers stored successfully",
                    'data' => $answer
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Invalid answers format"
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "An error occurred while storing answers. Please try again later.",
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
