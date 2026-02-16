<?php

namespace App\Http\Controllers;

use App\Models\MstrRm;
use Illuminate\Http\Request;

class MstrRmController extends Controller
{
    public function index(Request $request)
    {
        $items = MstrRm::orderBy('rm_name')->get();
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$items]);
        return view('mstr_rms.index', compact('items'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'rm_code' => 'required|string|max:50',
            'rm_name' => 'required|string|max:255',
            'unit_name' => 'nullable|string|max:255',
        ]);
        $item = MstrRm::create($data);
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$item]);
        return redirect()->back()->with('success','Saved');
    }

    public function edit($id)
    {
        // edit handled via API + modal; return JSON for client if requested
        $item = MstrRm::findOrFail($id);
        if (request()->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$item]);
        abort(404);
    }

    public function update($id, Request $request)
    {
        $data = $request->validate([
            'rm_name' => 'required|string|max:255',
            'unit_name' => 'nullable|string|max:255',
        ]);
        $item = MstrRm::findOrFail($id);
        $item->update($data);
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$item]);
        return redirect()->back()->with('success','Updated');
    }

    public function destroy($id, Request $request)
    {
        $item = MstrRm::findOrFail($id);
        $item->delete();
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success']);
        return redirect()->back()->with('success','Deleted');
    }
}
