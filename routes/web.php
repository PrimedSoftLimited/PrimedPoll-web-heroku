 <?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return ["message" => "All API routes are on {server}/api"];
});

$router->get('/api', function () use ($router) {
    return ["message" => "Welcome to PrimePoll API"];
});

// show all existing interest as created by admin
$router->get('/api/interest', 'InterestController@index');

// show one interest as created by admin with affiliated poll as created by users
$router->get('/api/interest/{interest_id}', 'InterestController@show');

//****************Users Routes**************** */
$router->post('/api/register', 'SignupController@register');
$router->post('api/register/verify', 'VerifyUserController@verifyUser');
$router->post('api/login', 'SignInController@userLogin');

//Tino
$router->post('api/password/reset', 'PasswordController@resetpassword');
$router->put('api/password/change', 'ChangePasswordController@updatepassword');

//****************End Routes****************** */

//JuniCodefire****************Admin Custom Routes**************** */
$router->post('api/admin/login', 'SignInController@adminLogin');
//****************End Routes****************** */


$router->group(['middleware' => 'jwt.auth', 'prefix' => 'api'], function () use ($router) {
    //Put you controller inside this block for authorization or create a new ground with new prefix
    //This is the Admin Private route(Work here with caution)
    //JuniCodefire************************************* */
    $router->get('admin/profile', 'AdminProfileController@adminData');
    $router->put('admin/password/change', 'AdminProfileController@updatePass');
    $router->post('admin/create/interest', 'AdminInterestController@store');
    $router->get('admin/show/all/interest', 'AdminInterestController@index');
    $router->put('admin/edit/interest/{interest_id}', 'AdminInterestController@update');
    $router->delete('admin/delete/interest/{interest_id}', 'AdminInterestController@destroy');
    //************************************** */

    //for admin******************************Jeremiahiro******************************start/

    //for admin******************************Francis******************************start/
    $router->delete('admin/users/{user_id}', 'AdminController@deleteUser');
    //for admin******************************Jeremiahiro******************************end here/
    $router->get('admin/polls/{id}', 'AdminInterestController@showAdmin');

    // Statistics For Primed Poll
    //All User Stat
    $router->get('statistics/users', 'AdminStatisticsController@users');
    // All Poll stat
    $router->get('statistics/polls', 'AdminStatisticsController@polls');
    //Treanding category base on highest poll
    $router->get('statistics/interest', 'AdminStatisticsController@interest');
    //Gender Count
    $router->get('statistics/gender', 'AdminStatisticsController@GenderCount');
    //Period Count
    $router->get('statistics/period/count', 'AdminStatisticsController@periodCount');


    //This is the Users Public route
    //************************************** */

    //for users******************************Jeremiahiro******************************start/

    // edit users profile
    $router->put('/edit', 'UserProfileController@editprofile');

    // edit username
    $router->put('/username', 'UserProfileController@editUsername');

    // change users password
    $router->put('/password', 'UserProfileController@updatePassword');

    // upload profile picture
    $router->post('/upload', 'UserProfileController@uploadImage');


    // show all poll a user has created, their options and total vote count
    $router->get('/poll', 'UserPollController@index');

    // show one poll a user has created, their options and total vote count
    $router->get('/poll/{id}', 'UserPollController@show');

    // a user can edit/update a poll/option he created
    $router->put('/poll/{id}', 'UserPollController@update');

    // a user can create poll/options under an interest
    $router->post('/{id}/poll', 'UserPollController@create');

    // a user can delete a poll he created
    $router->delete('/poll/{id}', 'UserPollController@destroy');

    //A user can see all poll voters
    $router->get('/poll/voters/{id}', 'UserPollController@voters');


    // show all interest that user subscribed to
    $router->get('/user/interest/', 'UserInterestController@index');

    // show a single interest that user subscribed to
    $router->get('/user/{interest_id}', 'UserInterestController@show');

    // a user can deselect an interest
    $router->delete('/user/{interest_id}', 'UserInterestController@destroy');



    // show single options of a poll and their vote count
    $router->get('/{option_id}/option', 'UserOptionsController@show');

    // delete single option of a poll
    $router->delete('/{option_id}/option', 'UserOptionsController@destroy');


    // a user can vote
    $router->post('/{poll_id}/vote', 'UserVotesController@create');

    //for users******************************Jeremiahiro******************************end here/


    //Tino


    //francis
    $router->get('/profile', 'UserProfileController@index');
    $router->put('/complete/registration', 'UserCompleteRegistrationController@update');

    //************************************** */

    //JuniCodefire
    $router->get('/feeds', 'UserFeedsController@index');
    $router->get('/feeds/{offset}', 'UserFeedsController@scrolledfeeds');

    //with single interest id
    $router->get('single/feeds/{id}', 'UserFeedsController@index');
    $router->get('single/feeds/{id}/{offset}', 'UserFeedsController@scrolledfeeds');

    //Added newly remeber documentation
    $router->get('/not/subscribed/interest', 'UserInterestController@showNotSubscribedInterest');

    $router->post('/add/interest', 'UserInterestController@create');

    // upload profile picture
    $router->put('/bio', 'UserProfileController@createBio');
    //***************************************************

    //Follow a memeber
    $router->get('/follow', 'UserFollowController@show');
    $router->post('/follow/{id}', 'UserFollowController@follow');

    //User Statistics
    $router->get('/user/poll/count/{poll_id}', 'UserPollStatisticsController@user_poll_count');
    $router->get('/user/poll/count', 'UserPollStatisticsController@user_general_count');

});


$router->group(['middleware' => 'usernameCheck', 'prefix' => 'api'], function () use ($router) {
    //JuniCodefire
    $router->get('profile/{permission}/{onSession}/{username}', 'UserPublicProfile@showData');
    $router->get('public/feeds/{id}/{username}', 'UserFeedsController@usersFeeds');
    $router->get('followers/{id}/{username}', 'UserFollowController@followers');
    $router->get('following/{id}/{username}', 'UserFollowController@following');
});
