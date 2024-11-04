<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Auth;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    public function getPersonalInformation()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        $profileData = [
            'full_name' => $user->full_name,
            'email' => $user->email,
            'image' => $user->image
                ? url('Profile/' . $user->image)
                : url('avatar/profile.jpg'),
        ];
        return response()->json([
            'status' => true,
            'message' => 'Personal Information fetched successfully.',
            'data' => $profileData,
        ], 200);
    }

    public function personalInformation(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return $this->sendError('User not found!', [], 404);
        }
        $validator = Validator::make($request->all(), [
            'full_name' => 'string|max:255',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:8',
            'confirm_password' => 'required|string|min:8|same:new_password',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 400);
        }
        if ($request->has('full_name')) {
            $user->full_name = $request->full_name;
        }
        if ($request->hasFile('image')) {
            if ($user->image) {
                $oldImagePath = public_path('Profile/' . $user->image);

                if (is_file($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            $fileName = time() . '.' . $request->image->getClientOriginalExtension();
            $request->image->move(public_path('Profile'), $fileName);
            $user->image = $fileName;
        }
        if ($request->filled('old_password') && $request->filled('new_password')) {
            if (!Hash::check($request->old_password, $user->password)) {
                return $this->sendError('Old password is incorrect.', [], 400);
            }
            $user->password = Hash::make($request->new_password);
        }
        $user->save();
        $user->makeHidden(['password', 'remember_token']);
        
        return $this->sendResponse($user, "Personal Information successfully updated.");
    }
}
