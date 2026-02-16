<?php

namespace App\Http\Controllers;

use App\Models\MstrBiIndustry;
use Illuminate\Http\Request;

class MstrBiIndustryController extends Controller
{
    public function index(Request $request)
    {
        $items = MstrBiIndustry::orderBy('description')->get();
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$items]);
        return view('mstr_bi_industries.index', compact('items'));
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'bi_code' => 'required|string|max:50',
            'description' => 'required|string|max:255',
        ]);
        $item = MstrBiIndustry::create($data);
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$item]);
        return redirect()->back()->with('success','Saved');
    }

    public function edit($id)
    {
        $item = MstrBiIndustry::findOrFail($id);
        if (request()->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$item]);
        abort(404);
    }

    public function update($id, Request $request)
    {
        $data = $request->validate([
            'description' => 'required|string|max:255',
        ]);
        $item = MstrBiIndustry::findOrFail($id);
        $item->update($data);
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$item]);
        return redirect()->back()->with('success','Updated');
    }

    public function destroy($id, Request $request)
    {
        $item = MstrBiIndustry::findOrFail($id);
        $item->delete();
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success']);
        return redirect()->back()->with('success','Deleted');
    }
}
