<?php

namespace App\Http\Controllers\User;

use App\User;
use Hash;
use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Exceptions\NotAuthorizedException;
use App\AccessCode;

class UserController extends ApiController
{
    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct()
    {
      $this->middleware('jwt.auth', ['only' => ['me', 'update', 'password', 'share']]);
      $this->middleware('guest', ['only' => ['register', 'getByShareToken']]);
      $this->middleware('jwt.admin', ['only' => ['index', 'delete', 'store']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      $users = User::with('funeral_home')->orderBy('created_at', 'desc');

      if ($request->funeral_home_id > 0){
        $users = $users->where('funeral_home_id', '=', $request->funeral_home_id);
      }

      if ($request->q) { //search user
        $users = $users->where(function($query) use ($request) {
          $query->where('first_name', 'LIKE', '%'.$request->q.'%')
            ->orWhere('last_name', 'LIKE', '%'.$request->q.'%');
        });
      }

      $offset = isset($request->offset) ? $request->offset : 0;
      $limit = isset($request->limit) ? $request->limit : 25;
      $users = $users->skip($offset)
        ->take($limit)
        ->get();
      return response()->json(compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

      $this->validate($request, [
        'email' => 'required|email|unique:users',
        'password' => 'required|confirmed|min:6|max:100',
        // 'username' => 'required|unique:users',
        'first_name' => 'required|min:3|max:200',
        'last_name' => 'required|min:3|max:200',
        'user_level' => 'in:0,1',
        'status' => 'in:0,1',
        'birthday' => 'required|date'
      ]);

      $user = new User;
      $user->email = $request->email;
      $user->password = bcrypt($request->password);
      // $user->username = $request->username;
      $user->first_name = $request->first_name;
      $user->last_name = $request->last_name;
      $user->birthday = $request->birthday;
      $user->user_level = $request->user_level;
      $user->status = $request->status;
      $user->share_token = str_random(10);

      $user->save();
      return response()->json(['user' => $user]);
    }

    /**
     * Register a user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {

      $this->validate($request, [
        'email' => 'required|email|unique:users',
        'password' => 'required|confirmed|min:6|max:100',
        // 'username' => 'required|unique:users',
        'first_name' => 'required|min:3|max:200',
        'last_name' => 'required|min:3|max:200',
        'birthday' => 'required|date'
      ]);

      // Check access code
      if ($request->has('access_code'))
      {
        $access_code = AccessCode::where('access_code', $request->access_code)->first();

        if (!$access_code)
        {
          // Access code doesn't exist
          return response()->json(['error' => 'Access code not valid.'], 401);
        }
        else if ($access_code->used != 0 && $access_code->used != 2)
        {
          // Access code is not Available or Master
          return response()->json(['error' => 'Access code already taken.'], 401);
        }
      }
      
      $user = new User;
      $user->email = $request->email;
      $user->password = bcrypt($request->password);
      // $user->username = $request->username;
      $user->first_name = $request->first_name;
      $user->last_name = $request->last_name;
      $user->birthday = $request->birthday;
      $user->user_level = 0;
      $user->status = config('legacysuite.default_user_status');
      $user->confirmation_code = str_random(30);
      $user->share_token = str_random(10);

      if ($access_code)
      {
        // Record the user's access code
        $user->access_code_id = $access_code->id;
      }

      // funeral home id optional check
      if ($request->funeral_home_id) {
          $this->validate($request, [
            'funeral_home_id' => 'exists:funeral_homes,id'
          ]);
          $user->funeral_home_id = $request->funeral_home_id;
      }

      // Create New User
      $user->save();

      if ($access_code)
      {
        // If not a Master access code, mark the code as Used
        if ($access_code->used != 2)
        {
          $access_code->used = 1;
          $access_code->save(); 
        }
      }

      $user->sendWelcomeEmail();

      return response()->json(['user' => $user]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
      $user = User::findOrFail($id);
      return response()->json(compact('user'));
    }

  /**
     * Finds user by share token
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getByShareToken($share_token)
    {
      $user = User::select('first_name', 'last_name', 'id')
      ->where('share_token', '=', $share_token)
      ->where('status', '=', 1)
      ->firstOrFail();
      return response()->json(compact('user'));
    }

    /**
     * Display current user
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function me()
    {
      $user = \Auth::user();
      return response()->json(compact('user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
      $user = User::findOrFail($id);
      $current_user = \Auth::user();
      if ($current_user->isAdmin() || $user->id === $current_user->id){
        if (isset($request->email)){
          $this->validate($request, [
            'email' => 'required|email|unique:users'
          ]);
          $user->email = $request->email;
        }
        // if (isset($request->username)){
        //   $this->validate($request, [
        //    'username' => 'required|unique:users'
        //  ]);
        //  $user->username = $request->username;
        // }
        if (isset($request->birthday)){
          $this->validate($request, [
            'birthday' => 'date'
          ]);
          $user->birthday = $request->birthday;
        }
        if (isset($request->first_name)){
          $this->validate($request, [
            'first_name' => 'min:3|max:200'
          ]);
          $user->first_name = $request->first_name;
        }
        if (isset($request->last_name)){
          $this->validate($request, [
            'last_name' => 'min:3|max:200'
          ]);
          $user->last_name = $request->last_name;
        }
        if ($current_user->isAdmin() && isset($request->user_level)){
          $this->validate($request, [
            'user_level' => 'in:0,1'
          ]);
          $user->user_level = $request->user_level;
        }
        if ($current_user->isAdmin() && isset($request->status)){
          $this->validate($request, [
            'status' => 'in:0,1'
          ]);
          $user->status = $request->status;
        }
        $user->save();
        return response()->json(['success' => true]);
      }else{
        throw new NotAuthorizedException;
      }
    }

    /**
     * Update user's password
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function password(Request $request, $id)
    {
      $user = User::findOrFail($id);
      $current_user = \Auth::user();
      if ($current_user->isAdmin() || $user->id === $current_user->id){
      $this->validate($request, [
          'password' => 'required|confirmed|min:6|max:100'
       ]);

       if (!$current_user->isAdmin() && !Hash::check($request->old_password, $user->password)){
         return response()->json(['error' => 'password_is_wrong'], 401);
       }

        $user->password = bcrypt($request->password);
        $user->save();
        return response()->json(['success' => true]);
      }else{
        throw new NotAuthorizedException;
      }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
      $user = \Auth::user();
      if ($id !== $user->id){
        User::destroy($id);
        return response()->json(['success' => true]);
      }else{
        return response()->json(['error' => 'can_not_delete_himself'], 400);
      }
    }

  /**
     * share video to an email
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
  public function share(Request $request, $id) {
    $user = User::findOrFail($id);
        $current_user = \Auth::user();
    $this->validate($request, [
      'email' => 'required|email'
    ]);
    $email = $request->email;
        if ($current_user->isAdmin() || $user->id === $current_user->id){
      \Mail::send('emails.share_email', ['user' => $user, 'text' => $request->mail_text],
      function($message) use ($email) {
                $message->to($email)
                    ->subject(config('legacysuite.share_email_subject'));
            });
      return response()->json(['success' => true]);
    } else {
      throw new NotAuthorizedException;
    }
  }
}
