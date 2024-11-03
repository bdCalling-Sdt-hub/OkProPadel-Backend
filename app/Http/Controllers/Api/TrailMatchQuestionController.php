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
                'status'=> $question->status,
                'options' => json_decode($question->options, true),
                'created_at' => $question->created_at->toDateTimeString(),
                'updated_at' => $question->updated_at->toDateTimeString(),
            ];
        });

        $response = [
            // 'tittle' => '#Question ' . $questions->currentPage().' of '.$questions->total() ,
            // 'current_page' => $questions->currentPage(),
            // 'total_pages' => $questions->lastPage(),
            // 'total_questions' => $questions->total(),
            // 'per_page' => $questions->perPage(),
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
        ]);
        if ($validator->fails()) {
            return $this->sendError("Validation Error", $validator->errors());
        }

        $question = new TrailMatchQuestion();
        $question->question = $request->question;

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

        $question->options = json_encode($options);
        $question->status= true;
        $question->save();
        return $this->sendResponse([
            'id' => $question->id,
            'question' => $question->question,
            'status' => $question->status,
            'options' => json_decode($question->options),
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
        $question->question = $request->question;
        $question->status = $request->status;
        $question->save();

        return $this->sendResponse([
            'id' => $question->id,
            'question' => $question->question,
            'status' => $question->status,
            'options' => json_decode($question->options),
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
