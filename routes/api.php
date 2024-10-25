<?php

use App\Http\Controllers\Api\AboutController;
use App\Http\Controllers\Api\AnswerController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClubController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PadelMatchController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\TermAndConditionController;
use App\Http\Controllers\Api\TrailMatchController;
use App\Http\Controllers\Api\TrailMatchQuestionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\VolunteerController;
use App\Models\RequestTrailMacth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['controller' => AuthController::class,], function () {
    Route::post('/register', 'register')->withoutMiddleware('auth:sanctum');
    Route::post('/login', 'login')->withoutMiddleware('auth:sanctum');
    Route::post('/verify-otp', 'verifyOtp')->withoutMiddleware('auth:sanctum');
    Route::post('/forgot-password', 'forgotPassword')->withoutMiddleware('auth:sanctum');
    Route::post('/create-password', 'createPassword')->withoutMiddleware('auth:sanctum');
    Route::post('/resend-otp', 'resendOtp')->withoutMiddleware('auth:sanctum');
    Route::get('/get-user-name', 'getUserName')->withoutMiddleware('auth:sanctum');

    // Route::get('google-login', 'googleLogin')->withoutMiddleware('auth:sanctum');
    // Route::get('google-facebook', 'facebookLogin')->withoutMiddleware('auth:sanctum');

    Route::post('/logout', 'logout')->middleware('auth:sanctum');
    Route::get('/users', 'users')->middleware('auth:sanctum');
    Route::post('/update-password', 'updatePassword')->middleware('auth:sanctum');
    Route::put('/profile-update', 'profileUpdate')->middleware('auth:sanctum');
    Route::get('/profile', 'profile')->middleware('auth:sanctum');
    Route::get('/validate-token',  'validateToken')->middleware(['auth:sanctum']);
});
Route::group(['middleware' => ['auth:sanctum'], 'controller' => ProfileController::class], function () {
    Route::get('my-profile', 'myProfile');
    Route::get('another-user-profile/{id}', 'anotherUserProfile');
    Route::get('upgrade-level-free', 'upgradeLevelFree');
    Route::post('request-to-trail-match', 'requestToTrailMatch');
    Route::get('get-request-to-trail-match', 'getRequestToTrailMatch');
    Route::get('trail-match-details', 'TrailMatchDetails'); /* use admn and user both */
    Route::put('accept-trail-match', 'acceptTrailMatch');
    Route::put('deny-trail-match', 'denyTrailMatch');
    Route::get('trail-match-status', 'TrailMatchStatus');
    Route::post('trail-match-start/{id}', 'StartTrailMatch');

});
Route::group(['middleware' => ['auth:sanctum'], 'controller' => UserController::class], function () {
    Route::put('language', 'updateLanguage'); /* USE ADMIN AND USER */
    Route::put('gender', 'updateGender');
    Route::put('age', 'updateAge');
    Route::put('side-of-court', 'sideOfCourt');
    Route::put('location', 'updateLocation');
});
Route::group(['middleware' => ['auth:sanctum'], 'controller' => AnswerController::class], function () {
    Route::post('answers', 'storeAnswer');
});
Route::group(['middleware' => ['auth:sanctum'], 'controller' => PadelMatchController::class], function () {
    Route::get('level-with-level-name', 'levelWithLevelName');
    Route::get('members', 'members');
    Route::get('search-member', 'searchMember');

    Route::post('padel-match-create','padelMatchCreate');
    Route::get('padel-matches', 'indexPadelMatches');
    // Route::put('padel-matches/{id}', 'updatePadelMatch');
    Route::delete('padel-matches/{id}', 'deletePadelMatch');  // reuse in prfile
});
Route::group(['middleware' => ['auth:sanctum'], 'controller' => MessageController::class], function () {
    Route::get('get-group','getUserGroup');
    Route::put('group/{groupId}',  'updateGroup');
    Route::put('accept-group-member-request/{matchId}',  'acceptGroupMemberRequest'); //from request in homecontroller

    Route::post('group-message-store', 'storeGroupMessage');
    Route::put('group-message/{messageId}',  'updateGroupMessage');
    Route::delete('group-message/{messageId}', 'deleteGroupMessage');
    Route::put('message-is-read/{id}', 'messageIsRead');
    Route::get('group-message/{groupId}', 'getGroupMessages');

    Route::get('search-member', 'searchMember');
    Route::get('members', 'members');
    Route::post('group-invite/{groupId}',  'inviteMembers');
    Route::post('group-invitation-accept/{invitationId}', 'acceptInvitation');
    Route::get('get-group-member/{matchId}', 'getGroupMember');
    Route::post('add-member/{matchId}', 'PadelMatchMemberAdd');
    Route::put('accept-padel-match/{matchId}','acceptPadelMatch');
    Route::get('padel-match-member-status/{matchId}', 'PadelMatchMemberStatus');
    Route::delete('leave-group', 'leaveGroup');
    // Route::delete('remove-group-member', 'removeGroupMember');

    Route::put('start-game','startGame');
    Route::put('end-game','endGame');
    Route::get('game-status/{matchId}', 'gameStatus');

    Route::get('user-private-message-member', 'UserPrivateMessageMember');
    Route::post('member-private-message/{userId}', 'MemberMessage');
    Route::put('update-private-message/{privateMessageId}', 'UpdateMessage');
    Route::get('get-private-message', 'getPrivateMessage');
    Route::post('private-message/read/{messageId}', 'PrivateMessageAsRead');

    Route::post('block-private-message', 'BlockPrivateMessage');
    Route::post('unblock-private-message', 'UnblockPrivateMessage');
});
Route::group(['middleware' => ['auth:sanctum'], 'controller' => HomeController::class], function () {
    Route::get('viewMatch','viewMatch');
    Route::get('search-match','searchMatch');
    Route::post('join-match','joinMatch');

    Route::get('hom-page', 'homePage');
    Route::get('find-match','findMatch');
    Route::get('club-details/{id}',  'clubDetails');
});
Route::group(['middleware' => ['auth:sanctum'], 'controller' => NotificationController::class], function () {
    Route::put('mute-notifications',  'muteNotifications');
    Route::put('unmute-notifications', 'unmuteNotifications');
    Route::get('notifications', 'notifications');
    Route::post('/notifications/read/{id}', 'markAsRead');
});

/* Admin Panel Routes */
Route::group(['middleware' => ['auth:sanctum'], 'controller' => DashboardController::class], function () {
    Route::get('dashboard', 'dashboard');
});
Route::group(['middleware' => ['auth:sanctum'], 'controller' => UserManagementController::class], function () {
    Route::get('get-users', 'getUsers');
    Route::put('change-status/{userId}', 'changeRole');
    Route::delete('delete-user/{userId}', 'deleteUser');

    Route::get('user-details/{userId}', 'userDetails');
    Route::get('user-search', 'userSearch');

});
Route::group(['middleware' => ['auth:sanctum'], 'controller' => ClubController::class], function () {
    Route::get('clubs', 'index');
    Route::post('club', 'store');
    Route::put('club/{id}', 'update');
    Route::delete('club/{id}', 'delete');
});
Route::group(['middleware' => ['auth:sanctum'], 'controller' => VolunteerController::class], function () {
    Route::get('volunteers', 'index');
    Route::post('volunteer', 'store');
    Route::put('volunteer/{id}', 'update');
    Route::put('volunter-role-update/{id}', 'updateVolunterRole');
    Route::delete('volunteer/{id}', 'delete');
});
Route::group(['middleware' => ['auth:sanctum'], 'controller' => QuestionController::class], function () {
    Route::get('questions', 'getQuestion');
    Route::post('question', 'question');
    Route::put('question/{id}', 'update');
    Route::delete('question/{id}', 'delete');
    // after match questions user
    Route::get('/get-after-match-questionnaire/{matchId}',  'getAfterMatchQuestion');
    Route::post('/feedback', 'storeFeedback');
    Route::post('/after-match-question/{matchId}', 'afterMatchQuestion'); //user use
    Route::get('/match-member/{matchId}', 'matchMember'); //user use

});
Route::group(['middleware' => ['auth:sanctum'], 'controller' => TrailMatchController::class], function () {
    Route::get('request-match', 'requestMatch');
    Route::post('setup-trail-match', 'setUpTrailMatch');
});
Route::group(['middleware' => ['auth:sanctum'], 'controller' => FeedbackController::class], function () {
    Route::get('normal-match-feedback', 'normalMatchFeedback');
    Route::get('view-normal-match-feedback/{matchId}/{userId}', 'normalMatchView');
    Route::get('trail-match-feedback', 'trailMatchFeedback');
});
Route::group(['middleware' => ['auth:sanctum'], 'controller' => TrailMatchQuestionController::class], function () {
    Route::get('trail-match-questions', 'getTrailMatchQuestion'); //use admin and user
    Route::post('trail-match-question', 'trailMatchQuestion');
    Route::put('trail-match-question-update/{id}', 'updateTrailMatchQuestion');
    Route::delete('trail-match-question-delete/{id}', 'deleteTrailMatchQuesiton');
    Route::post('answer-trail-match-questions', 'answerTrailMatchQuestion');
});
Route::group(['middleware' => ['auth:sanctum'], 'controller' => SettingController::class], function () {
    Route::put('personalInformation', [SettingController::class,'personalInformation']);
    Route::get('getpersonalInformation', [SettingController::class,'getPersonalInformation']);
    Route::apiResource('faqs',FaqController::class);
    Route::apiResource('terms-and-conditions',TermAndConditionController::class);
    Route::apiResource('abouts',AboutController::class);
    // ->only(['index','store','update','destroy']);
    // Route::apiResource('terms-and-conditions',TermAndConditioncontroller::class)
});

