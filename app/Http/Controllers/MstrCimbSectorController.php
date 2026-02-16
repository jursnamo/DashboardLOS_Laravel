<?php

namespace App\Http\Controllers;

use App\Models\MstrCimbSector;
use Illuminate\Http\Request;

class MstrCimbSectorController extends Controller
{
    public function index(Request $request)
    {
        $items = MstrCimbSector::orderBy('description')->get();
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$items]);
        return view('mstr_cimb_sectors.index', compact('items'));
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'sectoral_code' => 'required|string|max:50',
            'description' => 'required|string|max:255',
        ]);
        $item = MstrCimbSector::create($data);
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$item]);
        return redirect()->back()->with('success','Saved');
    }

    public function edit($id)
    {
        $item = MstrCimbSector::findOrFail($id);
        if (request()->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$item]);
        abort(404);
    }

    public function update($id, Request $request)
    {
        $data = $request->validate([
            'description' => 'required|string|max:255',
        ]);
        $item = MstrCimbSector::findOrFail($id);
        $item->update($data);
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$item]);
        return redirect()->back()->with('success','Updated');
    }

    public function destroy($id, Request $request)
    {
        $item = MstrCimbSector::findOrFail($id);
        $item->delete();
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success']);
        return redirect()->back()->with('success','Deleted');
    }
}
