<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function sideOfCourt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'side_of_court' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        try {
            $user = Auth::user();
            $user->side_of_the_court = $request->side_of_court ?? $user->side_of_the_court;
            $user->save();
            return $this->sendResponse($user, 'Side of the court updated successfully.');
        } catch (Exception $e) {
            return $this->sendError('Error updating side of court', ['error' => $e->getMessage()]);
        }
    }
    public function updateLanguage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'language' => 'required|string|max:50',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        try {
            $user = Auth::user();
            $user->language = $request->language ?? $user->language;
            $user->save();
            return $this->sendResponse($user, 'Language updated successfully.');
        } catch (Exception $e) {
            return $this->sendError('Error updating language', ['error' => $e->getMessage()]);
        }
    }
    public function updateGender(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gender' => 'required|string|in:male,female,other',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        try {
            $user = Auth::user();
            $user->gender = $request->gender ?? $user->gender;
            $user->save();
            return $this->sendResponse($user, 'Gender updated successfully.');
        } catch (Exception $e) {
            return $this->sendError('Error updating gender', ['error' => $e->getMessage()]);
        }
    }

    public function updateAge(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'age' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        try {
            $user = Auth::user();
            $user->age = $request->age ?? $user->age;
            $user->save();
            return $this->sendResponse($user, 'Age updated successfully.');
        } catch (Exception $e) {
            return $this->sendError('Error updating age', ['error' => $e->getMessage()]);
        }
    }
    public function updateLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|string|max:255',
            'longitude' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $client = new Client();
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        $location = $this->getLocationFromCoordinates($client, $request->latitude, $request->longitude, $apiKey);
        try {
            $user = Auth::user();
            $user->latitude = $request->latitude ?? $user->latitude;
            $user->longitude = $request->longitude ?? $user->longitude;
            $user->location = $location ?? $user->location;
            $user->save();
            return $this->sendResponse($user, 'Location updated successfully.');
        } catch (Exception $e) {
            return $this->sendError('Error updating location', ['error' => $e->getMessage()]);
        }
    }
    private function getLocationFromCoordinates($client, $latitude, $longitude, $apiKey)
    {
        try {
            $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$latitude},{$longitude}&key={$apiKey}";
            $response = $client->get($url);
            $data = json_decode($response->getBody(), true);
            if (isset($data['results']) && count($data['results']) > 0) {
                return $data['results'][0]['formatted_address'];
            } else {
                return 'Location not found';
            }
        } catch (RequestException $e) {
            return 'Error retrieving location: ' . $e->getMessage();
        }
    }


}
