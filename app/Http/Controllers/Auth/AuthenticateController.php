<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\ApiController;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\User;


class AuthenticateController extends ApiController
{

    public function __construct()
    {
       // Apply the jwt.auth middleware to all methods in this controller
       // except for the authenticate method. We don't want to prevent
       // the user from retrieving their token if they don't already have it
       $this->middleware('jwt.auth', ['only' => ['logout']]);
       $this->middleware('guest', ['only' => ['login', 'confirm']]);
    }

    public function login(Request $request)
    {
        $email_credentials = $request->only('email', 'password');
        // $username_credentials = $request->only('username', 'password');

        try {
            // verify the credentials and create a token for the user
            if (! $token = JWTAuth::attempt($email_credentials)) {
                // if (! $token = JWTAuth::attempt($username_credentials)) {
                    return response()->json(['error' => 'Incorrect credentials.'], 401);
                // }
            }
        } catch (JWTException $e) {
            // something went wrong
            return response()->json(['error' => 'There was an error while creating token.'], 500);
        }

        $user = JWTAuth::toUser($token);
        if ($user->status < 1){
            return response()->json(['error' => 'This account is deactivated. If you have any questions, please email us at support@mylegacysuite.com'], 401);
        }

        // if no errors are encountered we can return a JWT
        return response()->json(['token' => $token, 'user' => $user]);
    }

    public function logout(){
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not log out.'], 500);
        }

        return response()->json(['success' => true]);
    }

    public function confirm($confirmation_code){
        if(!$confirmation_code){
            return redirect()->away('http://mylegacysuite.com');
        }

        $user = User::whereConfirmationCode($confirmation_code)->first();

        if ($user){
            $user->confirmation_code = null;
            $user->save();
        }

        return redirect()->away('http://mylegacysuite.com');
    }

    public function resendVerification(Request $request) {
        $user = User::whereEmail($request->email)->first();
        if (!$user) {
            return response()->json(['error' => 'Email not found.'], 500);
        }
        $user->sendWelcomeEmail();
        return response()->json(['success' => true]);
    }

}
