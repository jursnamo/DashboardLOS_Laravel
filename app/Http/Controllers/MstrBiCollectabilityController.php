<?php

namespace App\Http\Controllers;

use App\Models\MstrBiCollectability;
use Illuminate\Http\Request;

class MstrBiCollectabilityController extends Controller
{
    public function index(Request $request)
    {
        $items = MstrBiCollectability::orderBy('status')->get();
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$items]);
        return view('mstr_bi_collectabilities.index', compact('items'));
    }

    // create() view removed — creation is handled via API and client-side modal

    public function store(Request $request)
    {
        $data = $request->validate(['status' => 'required|string|max:255']);
        $item = MstrBiCollectability::create($data);
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$item]);
        return redirect()->back()->with('success','Saved');
    }

    public function edit($id, Request $request)
    {
        $item = MstrBiCollectability::findOrFail($id);
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$item]);
        abort(404);
    }

    public function update($id, Request $request)
    {
        $data = $request->validate(['status' => 'required|string|max:255']);
        $item = MstrBiCollectability::findOrFail($id);
        $item->update($data);
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$item]);
        return redirect()->back()->with('success','Updated');
    }

    public function destroy($id, Request $request)
    {
        $item = MstrBiCollectability::findOrFail($id);
        $item->delete();
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success']);
        return redirect()->back()->with('success','Deleted');
    }
}
