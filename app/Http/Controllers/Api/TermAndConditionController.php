<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TermAndCondition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TermAndConditionController extends Controller
{
    public function index(Request $request)
    {
        $termAndCondition = TermAndCondition::where('status',1)->first();
        if (!$termAndCondition) {
            $data = "No term and conditions founds.";
            return $this->sendResponse($data,"No Term and Condition Found.");
        }
        return $this->sendResponse($termAndCondition, 'Term and condition retrieved successfully.');
    }
    public function createOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            'status' => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation error.', $validator->errors(), 400);
        }
        $terms = TermAndCondition::first();
        if ($terms) {
            $terms->update([
                'content' => $request->content,
                'status' => $request->status ?? $terms->status,
            ]);
            return $this->sendResponse($terms, 'Terms and conditions updated successfully.');
        }
        $data = TermAndCondition::create([
            'content' => $request->content,
            'status' => $request->status ?? true,
        ]);
        return $this->sendResponse($data, 'Terms and conditions created successfully.');
    }

}
