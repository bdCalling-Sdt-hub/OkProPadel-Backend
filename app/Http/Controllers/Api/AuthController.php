<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OtpVerificationMail;
use App\Models\User;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
class AuthController extends Controller
{
    public function socialLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'google_id' => 'string|nullable',
            'facebook_id' => 'string|nullable',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'image'=>'nullable|url'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $existingUser = User::where('email', $request->email)->first();
        if ($existingUser) {
            $socialMatch = ($request->has('google_id') && $existingUser->google_id === $request->google_id) ||
                           ($request->has('facebook_id') && $existingUser->facebook_id === $request->facebook_id);
            if ($socialMatch) {
                Auth::login($existingUser);
                $token = $existingUser->createToken('authToken')->plainTextToken;
                return $this->responseWithToken($token);
            } elseif (is_null($existingUser->google_id) && is_null($existingUser->facebook_id)) {
                return response()->json(['message' => 'User already exists. Sign in manually.'], 422);
            } else {
                $existingUser->update([
                    'google_id' => $request->google_id ?? $existingUser->google_id,
                    'facebook_id' => $request->facebook_id ?? $existingUser->facebook_id,
                ]);
                Auth::login($existingUser);
                $token = $existingUser->createToken('authToken')->plainTextToken;
                return $this->responseWithToken($token);
            }
        }
        $image = null;
            if ($request->has('image')) {
                $imageUrl = $request->image;
                $imageContent = Http::get($imageUrl);
                $imageName = time() . '.jpg';
                $imagePath = public_path('Profile/' . $imageName);
                file_put_contents($imagePath, $imageContent->body());
                $image = $imageName;
            }
        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'password' => Hash::make(Str::random(16)),
            'image'=>$image,
            'role' => 'MEMBER',
            'google_id' => $request->google_id ?? null,
            'facebook_id' => $request->facebook_id ?? null,
            'latitude' => $request->latitude ?? null,
            'longitude' => $request->longitude ?? null,
            'status' => 'active',
        ]);
        Auth::login($user);
        $token = $user->createToken('authToken')->plainTextToken;
        return $this->responseWithToken($token);
    }
    protected function responseWithToken($token)
    {
        return $this->sendResponse($token, 'User logged in successfully.');
    }
    public function validateToken(Request $request)
    {
        if (Auth::check()) {
            return response()->json([
                'token_status' => true,
                'message' => 'Token is valid.',
            ]);
        }
        $token = $request->bearerToken();
        if ($token) {
            $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if ($user) {
                return response()->json([
                    'token_status' => false,
                    'message' => 'Token is valid but user is not authenticated.',
                ]);
            }
            return response()->json([
                'token_status' => 'invalid',
                'error' => 'Invalid token.',
            ], 401);
        }

        return response()->json([
            'token_status' => false,
            'error' => 'No token provided.',
        ], 401);
    }
    public function register(Request $request)
    {
        $user = User::where('email', $request->email)->where('verify_email', 0)->first();
        if ($user) {
            $this->sendOtpEmail($user);
            return response()->json([
                'status' => 200,
                'message' => 'Please check your email to validate your account.'
            ], 200);
        }
        $validator = Validator::make($request->all(), [
            'full_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|min:8|max:60',
            'role'       => ['required', Rule::in(['ADMIN', 'MEMBER'])],
        ]);
        if ($validator->fails()) {
            return $this->sendError(['error' => 'Validation Error.', 'messages' => $validator->errors()], 422);
        }
        $user = $this->createUser($request);
        $this->sendOtpEmail($user);
        return response()->json(['success' => 'User registered successfully. OTP sent to your email.'], 201);
    }

    private function createUser($request)
    {
        try {
            $otp = rand(100000, 999999);
            $input = $request->except('c_password');
            $input['password'] = Hash::make($input['password']);
            $input['otp'] = $otp;
            $input['otp_expires_at'] = now()->addMinutes(10);
            $input['verify_email'] = 0;

            return User::create($input);

        } catch (\Exception $e) {
            return $this->sendError(['error' => 'User Create Error', 'messages' => $e->getMessage()], 500);
        }
    }
    private function sendOtpEmail($user)
    {
        $otp = rand(100000, 999999);
        $user->update([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(1),
            'verify_email' => 0
        ]);

        $emailData = [
            'name' => $user->full_name,
            'otp' => $otp,
        ];
        try {
            Mail::to($user->email)->queue(new OtpVerificationMail($emailData));
        } catch (\Exception $e) {
            return $this->sendError(['error' => 'Failed to send OTP email. Please try again.', 'messages' => $e->getMessage()], 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->sendError(['error' => 'Validation Error.', 'messages' => $validator->errors()], 422);
        }
        $user = User::where('otp', $request->otp)
                    ->where('verify_email', 0)
                    ->first();

        if (!$user) {
            return $this->sendError(['error' => 'Invalid OTP or the email is already verified.'], 401);
        }
        if (now()->greaterThan($user->otp_expires_at)) {
            return $this->sendError(['error' => 'OTP has expired. Please request a new one.'], 401);
        }
        $user->update([
            'verify_email' => 1,
            'otp' => null,
            'otp_expires_at' => null,
            'status' => 'active'
        ]);

        $token = $user->createToken('authToken')->plainTextToken;

        return $this->sendResponse("Email verified successfully",$token);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation errors', $validator->errors(), 422);
        }
        $credentials = $request->only('email', 'password');
        if (!Auth::attempt($credentials)) {
            return $this->sendError('Unauthorized', ['error' => 'Unauthorized']);
        }
        $user = Auth::user();
        $token = $user->createToken('authToken')->plainTextToken;

        return $this->sendResponse(['token' => $token], 'User logged in successfully.');
    }
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'status'=>400,
                'message' => 'Your are not user.',
            ], 400);
        }else if ($user->google_id != null || $user->facebook_id != null) {
            return response()->json([
                'status'=>400,
                'message' => 'Your are social user, You do not need to forget password.',
            ], 400);
        }
        $this->sendOtpEmail($user);

        return $this->sendResponse([], 'OTP sent to your email for forgot password.');
    }
    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();
        $this->sendOtpEmail($user);
        return $this->sendResponse([], 'New OTP sent to your email.');
    }

    public function createPassword(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                "message" => "Your email is not exists"
            ], 401);
        }
        if (!$user->verify_email == 1) {
            return response()->json([
                "message" => "Your email is not verified"
            ], 401);
        }
        $validator = Validator::make($request->all(), [
            'new_password'   => 'required|min:8|max:60',
            'confirm_password' => 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        } else {
            $user->update(['password' => Hash::make($request->new_password)]);
            return response()->json(['status'=>200,'message' => 'Password created successfully','data'=> $user], 200);
        }
    }

    public function users()
    {
        $users = User::where('status','active')->where('role', 'MEMBER')->whereNull('otp')->paginate(10);
        if ($users) {
            return $this->sendResponse($users, 'User retrieved successfully.');
        }
        return $this->sendError('User not found.');
    }
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|min:8|max:60|different:current_password',
            'c_password' => 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }
        $user = $request->user();
        if (!Hash::check($request->current_password, $user->password)) {
            return $this->sendError('Current password is incorrect.', [], 401);
        }
        $user->password = Hash::make($request->new_password);
        $user->save();

        return $this->sendResponse([], 'Password updated successfully.');
    }
    public function profileUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }
        $user = auth()->user();
        $image=null;
        if ($request->hasFile('image')) {
            $oldImagePath = public_path( $user->image);
            if ($user->image && file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
            $imagePath = time() . '.' . $request->image->getClientOriginalExtension();
            $request->image->move(public_path('Profile'), $imagePath);
            $image =$imagePath;
        }
        $user->full_name = $request->full_name ?? $user->full_name;
        $user->image = $image ?? $user->image;
        $user->save();

        return $this->sendResponse($user, 'Profile updated successfully.');
    }
    public function profile(Request $request)
    {
        $user = Auth::user();
        $user->image = $user->image ? url('Profile/' . $user->image) : url('avatar','profile.jpg');
        return $this->sendResponse($user, 'User profile retrieved successfully.');
    }
    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->delete();
            return $this->sendResponse(['message' => 'User successfully logged out.'], []);
        } catch (\Exception $e) {
            return $this->sendError('Logout Error', ['error' => 'Failed to log out. Please try again.'], 500);
        }
    }
    public function userProfileImageUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        $user = auth()->user();
        $oldImagePath = $user->image;

        if ($request->hasFile('image')) {
            if ($oldImagePath && file_exists(public_path('Profile/' . $oldImagePath))) {
                unlink(public_path('Profile/' . $oldImagePath));
            }
            $imagePath = time() . '.' . $request->image->getClientOriginalExtension();
            $request->image->move(public_path('Profile'), $imagePath);
            $user->image = $imagePath;
        }

        $user->save();

        return $this->sendResponse($user, 'Profile updated successfully.');
    }

}
