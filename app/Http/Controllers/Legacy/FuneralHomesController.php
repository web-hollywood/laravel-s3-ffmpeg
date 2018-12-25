<?php

namespace App\Http\Controllers\Legacy;

use App\FuneralHome;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\ApiController;

class FuneralHomesController extends ApiController
{
    /**
     * Create a new authentication controller instance.
     *
     * @return void
    */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $funeral_homes = FuneralHome::all();
        return response()->json(compact('funeral_homes'));
    }
}
