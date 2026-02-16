<?php

namespace App\Http\Controllers;

use App\Models\MstrConstitution;
use Illuminate\Http\Request;

class MstrConstitutionController extends Controller
{
    public function index(Request $request)
    {
        $items = MstrConstitution::orderBy('name')->get();
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$items]);
        return view('mstr_constitutions.index', compact('items'));
    }


    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255']);
        $item = MstrConstitution::create($data);
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$item]);
        return redirect()->back()->with('success','Saved');
    }

    public function edit($id)
    {
        $item = MstrConstitution::findOrFail($id);
        if (request()->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$item]);
        abort(404);
    }

    public function update($id, Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255']);
        $item = MstrConstitution::findOrFail($id);
        $item->update($data);
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$item]);
        return redirect()->back()->with('success','Updated');
    }

    public function destroy($id, Request $request)
    {
        $item = MstrConstitution::findOrFail($id);
        $item->delete();
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success']);
        return redirect()->back()->with('success','Deleted');
    }
}
