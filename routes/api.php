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
use Illuminate\Support\Facades\Route;

Route::group(['controller' => AuthController::class,], function () {
    Route::post('/register', 'register')->withoutMiddleware('auth:sanctum');
    Route::post('/login', 'login')->withoutMiddleware('auth:sanctum');
    Route::post('/verify-otp', 'verifyOtp')->withoutMiddleware('auth:sanctum');
    Route::post('/forgot-password', 'forgotPassword')->withoutMiddleware('auth:sanctum');
    Route::post('/create-password', 'createPassword')->withoutMiddleware('auth:sanctum');
    Route::post('/resend-otp', 'resendOtp')->withoutMiddleware('auth:sanctum');
    Route::get('/get-user-name', 'getUserName')->withoutMiddleware('auth:sanctum');
    Route::post('social-login', 'socialLogin')->withoutMiddleware(['auth:sanctum','member']);
    Route::post('/logout', 'logout')->middleware(['auth:sanctum','member']);
    Route::get('/users', 'users')->middleware(['auth:sanctum','member']);
    Route::post('/update-password', 'updatePassword')->middleware(['auth:sanctum','member']);
    Route::put('/profile-update', 'profileUpdate')->middleware(['auth:sanctum','member']);
    Route::get('/profile', 'profile')->middleware(['auth:sanctum','member']);
    Route::get('/validate-token',  'validateToken')->middleware(['auth:sanctum','member']);
    Route::put('/profile-update-image',  'userProfileImageUpdate')->middleware(['auth:sanctum','member']);
});
Route::group(['middleware' => ['auth:sanctum','member'], 'controller' => ProfileController::class], function () {
    Route::get('my-profile', 'myProfile');
    Route::get('another-user-profile/{id}', 'anotherUserProfile');
    Route::get('joinedMatches', 'joinedMatches');
    Route::get('createdMatches', 'createdMatches');
    Route::get('trailMatches', 'trailMatches');
    Route::get('upgrade-level-free', 'upgradeLevelFree');
    Route::post('request-to-trail-match', 'requestToTrailMatch');
    Route::get('get-request-to-trail-match', 'getRequestToTrailMatch');
    Route::get('trail-match-details', 'TrailMatchDetails');
    Route::put('accept-trail-match', 'acceptTrailMatch');
    Route::put('deny-trail-match', 'denyTrailMatch');
    Route::get('trail-match-status/{trailMatchId}', 'TrailMatchStatus');
    Route::post('trail-match-start/{id}', 'StartTrailMatch');
});
Route::group(['middleware' => ['auth:sanctum','member'], 'controller' => UserController::class], function () {
    Route::put('language', 'updateLanguage');
    Route::put('gender', 'updateGender');
    Route::put('age', 'updateAge');
    Route::put('side-of-court', 'sideOfCourt');
    Route::put('location', 'updateLocation');
});
Route::group(['middleware' => ['auth:sanctum','member'], 'controller' => AnswerController::class], function () {
    Route::post('answers', 'storeAnswer');
});
Route::group(['middleware' => ['auth:sanctum','member'], 'controller' => PadelMatchController::class], function () {
    Route::get('level-with-level-name', 'levelWithLevelName');
    Route::get('members', 'members');
    Route::get('search-member', 'searchMember');
    Route::post('padel-match-create','padelMatchCreate');
    Route::get('padel-matches', 'indexPadelMatches');
    Route::delete('padel-matches/{id}', 'deletePadelMatch');  // reuse in prfile
});
Route::group(['middleware' => ['auth:sanctum','member'], 'controller' => MessageController::class], function () {
    Route::get('active-group/{matchId}','activeGroup');
    Route::get('get-group','getUserGroup');
    Route::put('group/{groupId}',  'updateGroup');
    Route::put('accept-group-member-request/{matchId}',  'acceptGroupMemberRequest'); //from request in homecontroller
    Route::post('group-message-store', 'storeGroupMessage');
    Route::put('group-message/{messageId}',  'updateGroupMessage');
    Route::delete('group-message/{messageId}', 'deleteGroupMessage');
    Route::put('message-is-read/{id}', 'messageIsRead');
    Route::get('group-message/{groupId}', 'getGroupMessages');
    Route::get('search-member', 'searchMember');
    Route::get('get-inivite-members/{groupId}', 'getInviteMembers');
    Route::post('group-invite/{groupId}',  'inviteMembers');
    Route::post('group-invitation-accept/{invitationId}','acceptInvitation');
    Route::post('deny-request','denyRequest');
    Route::get('get-group-member/{matchId}', 'getGroupMember');
    Route::post('add-member/{matchId}', 'PadelMatchMemberAdd');
    Route::put('accept-padel-match/{matchId}','acceptPadelMatch');
    Route::get('padel-match-member-status/{matchId}', 'PadelMatchMemberStatus');
    Route::delete('leave-group', 'leaveGroup');
    Route::put('start-game','startGame');
    Route::put('end-game','endGame');
    Route::get('game-status/{MatchId}', 'gameStatus');
    Route::get('normal-game-status/{MatchId}', 'NormalgameStatus');
    Route::get('user-private-message-member', 'UserPrivateMessageMember');
    Route::post('member-private-message/{userId}', 'MemberMessage');
    Route::put('update-private-message/{privateMessageId}', 'UpdateMessage');
    Route::get('get-private-message', 'getPrivateMessage');
    Route::post('private-message-read', 'privateMessagesAsRead');
    Route::post('block-private-message-member', 'BlockPrivateMessage');
    Route::post('unblock-private-message', 'UnblockPrivateMessage');
    Route::get('block-status', 'blockStatus');
});
Route::group(['middleware' => ['auth:sanctum','member'], 'controller' => HomeController::class], function () {
    Route::get('viewMatch','viewMatch');
    Route::get('search-match','searchMatch');
    Route::post('join-match','joinMatch');
    Route::get('home-page', 'homePage');
    Route::get('find-match','findMatch');
    Route::get('club-details/{id}',  'clubDetails');
});
Route::group(['middleware' => ['auth:sanctum','member'], 'controller' => NotificationController::class], function () {
    Route::put('mute-notifications',  'muteNotifications');
    Route::put('unmute-notifications', 'unmuteNotifications');
    Route::get('notifications', 'notifications');
    Route::post('/notifications/read/{id}', 'markAsRead');
    Route::post('notifications-read-all','notificationReadAll');
});
/* Admin Panel Routes */
Route::group(['middleware' => ['auth:sanctum','admin'], 'controller' => DashboardController::class], function () {
    Route::get('dashboard', 'dashboard');
    Route::get('dashboard-graph-data','dashboardGraphData');
});
Route::group(['middleware' => ['auth:sanctum','admin'], 'controller' => UserManagementController::class], function () {
    Route::get('get-users', 'getUsers');
    Route::put('change-status/{userId}', 'changeRole');
    Route::delete('delete-user/{userId}', 'deleteUser');
    Route::get('user-details/{userId}', 'userDetails');
    Route::get('user-search', 'userSearch');
});
Route::group(['middleware' => ['auth:sanctum','admin'], 'controller' => ClubController::class], function () {
    Route::get('clubs', 'index');
    Route::post('club', 'store');
    Route::put('club/{id}', 'update');
    Route::delete('club/{id}', 'delete');
});
Route::group(['middleware' => ['auth:sanctum','admin'], 'controller' => VolunteerController::class], function () {
    Route::get('volunteers', 'index');
    Route::post('volunteer', 'store');
    Route::put('volunteer/{id}', 'update');
    Route::put('volunter-role-update/{id}', 'updateVolunterRole');
    Route::delete('volunteer/{id}', 'delete');
});
Route::group(['middleware' => ['auth:sanctum','member'], 'controller' => QuestionController::class], function () {
    Route::get('questions', 'getQuestion');
    Route::post('question', 'question')->middleware(['auth:sanctum','admin']);
    Route::put('question/{id}', 'update')->middleware(['auth:sanctum','admin']);
    Route::delete('question/{id}', 'delete')->middleware(['auth:sanctum','admin']);
    Route::get('/get-after-match-questionnaire/{matchId}',  'getAfterMatchQuestion');
    Route::post('/feedback', 'storeFeedback');
    Route::post('/after-match-question/{matchId}', 'afterMatchQuestion'); //user use
    Route::get('/match-member/{matchId}', 'matchMember'); //user use
});
Route::group(['middleware' => ['auth:sanctum','admin'], 'controller' => TrailMatchController::class], function () {
    Route::get('request-match', 'requestMatch');
    Route::post('setup-trail-match/{requestId}', 'setUpTrailMatch');
    Route::get('get-setup-trail-match/{trailMatchId}', 'getSetUpTrailMatch');
});
Route::group(['middleware' => ['auth:sanctum','admin'], 'controller' => FeedbackController::class], function () {
    Route::get('normal-match-feedback', 'normalMatchFeedback');
    Route::get('view-normal-match-feedback/{matchId}/{userId}', 'normalMatchView');
    Route::get('trail-match-feedback', 'trailMatchFeedback');
    Route::put('adjust-level/{userId}','adjustLevel');
});
Route::group(['middleware' => ['auth:sanctum'], 'controller' => TrailMatchQuestionController::class], function () {
    Route::get('trail-match-questions', 'getTrailMatchQuestion')->middleware(['auth:sanctum','member']); //use admin and user
    Route::post('trail-match-question', 'trailMatchQuestion')->middleware(['auth:sanctum','admin']);
    Route::put('trail-match-question-update/{id}', 'updateTrailMatchQuestion')->middleware(['auth:sanctum','admin']);
    Route::delete('trail-match-question-delete/{id}', 'deleteTrailMatchQuesiton')->middleware(['auth:sanctum','admin']);
    Route::post('answer-trail-match-questions', 'answerTrailMatchQuestion')->middleware(['auth:sanctum','member']);
});
Route::group(['middleware' => ['auth:sanctum'], 'controller' => SettingController::class], function () {
    Route::put('personalInformation', [SettingController::class,'personalInformation'])->middleware(['auth:sanctum','admin']);
    Route::get('getpersonalInformation', [SettingController::class,'getPersonalInformation'])->middleware(['auth:sanctum','admin']);
    Route::apiResource('faqs',FaqController::class)->middleware(['auth:sanctum','admin']);
    Route::put('terms-and-conditions',[TermAndConditionController::class,'createOrUpdate'])->middleware(['auth:sanctum','admin']);
    Route::apiResource('abouts',AboutController::class)->middleware(['auth:sanctum','admin']);
    Route::apiResource('abouts',AboutController::class)->middleware(['auth:sanctum','member'])->only(['index']);
    Route::apiResource('terms-and-conditions',TermAndConditioncontroller::class)->middleware(['auth:sanctum','member'])->only(['index']);
    Route::apiResource('terms-and-conditions',TermAndConditionController::class)->middleware(['auth:sanctum','member'])->only(['index']);
});

