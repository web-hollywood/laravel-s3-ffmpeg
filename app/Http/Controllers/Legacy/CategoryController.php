<?php

namespace App\Http\Controllers\Legacy;

use App\Category;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\ApiController;

class CategoryController extends ApiController
{
    /**
     * Create a new authentication controller instance.
     *
     * @return void
    */
    public function __construct()
    {
        $this->middleware('guest');
        $this->middleware('jwt.auth', ['except' => ['index']]);
        $this->middleware('jwt.admin', ['except' => ['index']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $categories = Category::all();
        return response()->json(compact('categories'));
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
            'text' => 'required'
        ]);
        $category = new Category;
        $category->text = $request->text;
        $category->save();
        return response()->json(compact('category'));
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
        $category = Category::findOrFail($id);
        $this->validate($request, [
            'text' => 'required'
        ]);
        $category->text = $request->text;
        $category->save();
        return response()->json(compact('category'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();
        return response()->json(['success' => true]);
    }
}
