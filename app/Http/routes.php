<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/info', function () {
    return Redirect::to('/landing-page/index.html');    
    //return File::get(public_path() . '/landing-page/index.html');
});

Route::get('/', function () {
    return view('welcome');
});

Route::group(['prefix' => 'api'], function()
{
	Route::group(['prefix' => 'auth'], function(){
		//authentication routes
		Route::post('login', 'Auth\AuthenticateController@login');
        Route::post('logout', 'Auth\AuthenticateController@logout');

        //password reset routes...
		Route::post('trigger_reset_password', 'Auth\PasswordController@postEmail');
		Route::post('reset_password', 'Auth\PasswordController@postReset');

		//verification routes
		Route::get('verify/{confirmationCode}', 'Auth\AuthenticateController@confirm');
		Route::post('resend_verification', 'Auth\AuthenticateController@resendVerification');

		Route::post('register', 'User\UserController@register');

		//access code generation and validation
		Route::post('accesscode/generate', 'Auth\AccessCodeController@generate');
		Route::post('accesscode/validate', 'Auth\AccessCodeController@validateAccessCode');
	});

	//user routes
	Route::get('users/me', 'User\UserController@me');
    Route::get('/users/byshare/{share_token}', 'User\UserController@getByShareToken');
	Route::put('users/{id}/password', 'User\UserController@password');
	Route::resource('users', 'User\UserController', ['only' => ['index', 'show', 'store', 'update', 'destroy']]);
    Route::post('users/{id}/share', 'User\UserController@share');
	Route::get('users/{id}/prompts', 'Legacy\PromptController@promptsForUser');
    Route::get('users/{id}/videos', 'Legacy\VideoController@getSharedVideos');

	//other routes for legacy suite
	Route::resource('categories', 'Legacy\CategoryController', ['only' => ['index', 'store', 'update', 'destroy']]);

	Route::resource('funeral_homes', 'Legacy\FuneralHomesController', ['only' => ['index']]);

	Route::resource('prompts', 'Legacy\PromptController', ['only' => ['store', 'update', 'destroy', 'index']]);
	Route::get('prompts/{id}/clone', 'Legacy\PromptController@copy');

	//video apis
    Route::get('videos/{id}/view', 'Legacy\VideoController@view');
	Route::resource('videos', 'Legacy\VideoController', ['only' => ['destroy', 'store', 'show', 'index', 'update']]);

});
