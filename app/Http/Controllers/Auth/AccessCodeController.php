<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\ApiController;
use App\Exceptions\NotAuthorizedException;
use JWTAuth;
use App\AccessCode;

class AccessCodeController extends ApiController
{

    /**
     * Create a new authentication controller instance.
     *
     * @return void
    */
    public function __construct()
    {
        $this->middleware('guest', ['only' => ['validateAccessCode']]);
        $this->middleware('jwt.admin', ['only' => ['generate']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function generate(Request $request)
    {
        $count = $request->count;

        $preAccessCodes = AccessCode::get()->lists('accesscode')->all();
        $tmpAccessCodes = $preAccessCodes;
        $addAccessCodes = [];

        $cnt = 0;
        $loop = 0;
        while($cnt < $count){
            $code = str_pad(rand(0, pow(10, AccessCode::LENGTH)-1), AccessCode::LENGTH, '0', STR_PAD_LEFT);
            if(!in_array($code, $tmpAccessCodes)){
                array_push($tmpAccessCodes, $code);
                array_push($addAccessCodes, $code);
                $record = new AccessCode;
                $record->access_code = $code;
                $record->save();
                $cnt++;
            }
            $loop++;
        }

        return response()->json(['success' => $addAccessCodes]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function validateAccessCode(Request $request)
    {
        $record = AccessCode::where('access_code', '=', $request->accesscode)->first();

        if($record == null)
            $result = 'NOT_FOUND';
        else if($record->used == 1)
            $result = 'USED';
        else
            $result = 'AVAILABLE';

        return response()->json(['success' => $result]);
    }
}
