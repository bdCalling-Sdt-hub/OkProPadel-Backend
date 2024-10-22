<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\About;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AboutController extends Controller
{
    public function index(Request $request)
    {
        $termAndCondition = About::where('status',1)->first();
        if (!$termAndCondition) {
            return $this->sendError("No Term and Condition Found.");
        }
        return $this->sendResponse($termAndCondition, 'Term and condition retrieved successfully.');
    }

    public function update(Request $request, $id)
    {
        $terms = About::find($id);
        if (!$terms) {
            $validator = Validator::make($request->all(), [
                'about' => 'required|string',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation error.', $validator->errors(), 400);
            }
            About::create([
                'about' => $request->about,
            ]);
            return $this->sendResponse($terms, 'Terms and conditions created successfully.');
        }
        $validator = Validator::make($request->all(), [
            'about' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation error.', $validator->errors(), 400);
        }
        $terms->update([
            'about' => $request->about,
        ]);
        return $this->sendResponse($terms, 'Terms and conditions updated successfully.');
    }
}
