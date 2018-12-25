<?php

namespace App\Http\Controllers\Legacy;

use App\Prompt;
use App\User;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\ApiController;
use App\Exceptions\NotAuthorizedException;

class PromptController extends ApiController
{
    /**
     * Create a new authentication controller instance.
     *
     * @return void
    */
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['index']]);
    }

    /**
     * Display a listing of the prompts for a user
     *
     * @return \Illuminate\Http\Response
     */
    public function promptsForUser($id)
    {
        $user = User::findOrFail($id);
        $current_user = \Auth::user();
        if (!$current_user->isAdmin() && $user->id !== $current_user->id){
            throw new NotAuthorizedException;
        }

        $prompts = Prompt::where('user_id', '=', $user->id)
            ->orWhereNull('user_id')
            ->with(['videos' => function ($query) use ($user){
                $query->where('user_id', '=', $user->id);
            }])
            ->get();

        $results = $prompts->toArray();
        foreach ($prompts as $key => $prompt){
            if (count($prompt->videos)) {
                $results[$key]['videos'] = $prompt->videos->toArray();
            }
        }
        return response()->json(['prompts' => $results]);
    }

    public function index()
    {
        $prompts = Prompt::whereNull('user_id')
            ->get();
        return response()->json(['prompts' => $results]);
    }

    /**
     * Clone a prompt for a user
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function copy($id)
    {
        $user = \Auth::user();
        $prompt = Prompt::findOrFail($id);
        $new_prompt = new Prompt;
        $new_prompt->category_id = $prompt->category_id;
        $new_prompt->text = $prompt->text;
        if (!$user->isAdmin()){
            $new_prompt->user_id = $user->id;
        }
        $new_prompt->save();
        return response()->json(['prompt' => $new_prompt]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = \Auth::user();
        $this->validate($request, [
            'text' => 'required',
            'category_id' => 'exists:categories,id'
        ]);
        $prompt = new Prompt;
        $prompt->text = $request->text;
        $prompt->category_id = $request->category_id;
        if (!$user->isAdmin()){
            $prompt->user_id = $user->id;
        }
        $prompt->save();
        return response()->json(compact('prompt'));
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
        $user = \Auth::user();
        $prompt = Prompt::with(['videos' => function ($query) use ($user){
            $query->where('user_id', '=', $user->id);
        }])->findOrFail($id);
        if ($user->isAdmin() || $prompt->user_id === $user->id){
            $this->validate($request, [
                'text' => 'required'
            ]);
            $prompt->text = $request->text;
            $prompt->save();
            return response()->json(compact('prompt'));
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
        $prompt = Prompt::findOrFail($id);
        if ($user->isAdmin() || $prompt->user_id === $user->id){
            $prompt->delete();
            return response()->json(['success' => true]);
        }else{
            throw new NotAuthorizedException;
        }
    }
}
