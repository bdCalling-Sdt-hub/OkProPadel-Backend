<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Question;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AnswerController extends Controller
{
    public function storeAnswer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|integer|exists:questions,id',
            'answers.*.answer' => 'required|string',
            'answers.*.value' => 'required|integer|min:1|max:5',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }
        try{
            $userId = Auth::id();
            foreach ($request->answers as $answerData) {
                Answer::create([
                    'question_id' => $answerData['question_id'],
                    'user_id' => $userId,
                    'answer' => $answerData['answer'],
                    'value' => $answerData['value'],
                ]);
            }
            $totalQuestions = Question::count();
            $userAnswers = Answer::where('user_id', $userId)->get();
            $totalValue = $userAnswers->sum('value');

            if ($userAnswers->count() === $totalQuestions) {
                if ($totalValue >= 10 && $totalValue <= 20) {
                    $request->user()->update(['level' => '1','level_name'=>'Beginner', 'points' => $totalValue]);
                } elseif ($totalValue >= 21 && $totalValue <= 30) {
                    $request->user()->update(['level' => '2','level_name'=>'Lower-Intermediate', 'points' => $totalValue]);
                } elseif ($totalValue >= 31 && $totalValue <= 35) {
                    $request->user()->update(['level' => '3','level_name'=>'Upper-Intermediate', 'points' => $totalValue]);
                } elseif ($totalValue >= 36 && $totalValue <= 40) {
                    $request->user()->update(['level' => '4','level_name'=>'Advanced', 'points' => $totalValue]);
                } else {
                    return $this->sendError('You are not eligible.');
                }

                return $this->sendResponse([], 'Your initial level is ' . $request->user()->level . ' with ' . $totalValue . ' points out of ' . ($totalQuestions * 4));
            }
            return $this->sendResponse([], "Answer stored successfully.");

        }catch(\Exception $e){
            return $this->sendError(0, $e->getMessage());
        }
    }

}
