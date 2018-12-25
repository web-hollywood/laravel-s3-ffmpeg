<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Mail\Message;

class PasswordController extends ApiController
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Create a new password controller instance.
     *
     * @return void
     */
    public function __construct(PasswordBroker $passwords)
    {
        $this->passwords = $passwords;
        $this->middleware('guest');
        $this->subject = 'Your Password Reset Link';
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postReset(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
        ]);

        $credentials = $request->only(
            'email', 'password', 'password_confirmation', 'token'
        );

        $response = $this->passwords->reset($credentials, function ($user, $password) {
            $this->resetPassword($user, $password);
            return response()->json(['success' => true]);
        });

        switch ($response) {
            case PasswordBroker::PASSWORD_RESET:
                return response()->json(['success' => true]);

            default:
                return response()->json(['error' => 'There was an error while resetting your password.'], 500);
        }
    }

    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postEmail(Request $request)
    {
        $this->validate($request, ['email' => 'required|email']);

        $response = $this->passwords->sendResetLink($request->only('email'), function (Message $message) {
            $message->subject($this->getEmailSubject());
        });

        switch ($response) {
            case PasswordBroker::RESET_LINK_SENT:
                return response()->json(['success' => true]);

            case PasswordBroker::INVALID_USER:
                return response()->json(['error' => 'Email not found.'], 404);
        }
    }
}
