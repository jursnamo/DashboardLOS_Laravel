<?php

namespace App\Http\Controllers;

use App\Models\MstrBaselType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MstrBaselTypeController extends Controller
{
    public function index(Request $request)
    {
        $items = MstrBaselType::orderBy('type_name')->get();
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$items]);
        return view('mstr_basel_types.index', compact('items'));
    }

    // create() view removed — creation is handled via API and client-side modal

    public function store(Request $request)
    {
        $data = $request->validate([
            'id' => 'nullable|string|max:50',
            'type_name' => 'required|string|max:255',
        ]);
        if (empty($data['id'])) {
            $data['id'] = (string) Str::uuid();
        }
        $item = MstrBaselType::create($data);
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$item]);
        return redirect()->back()->with('success','Saved');
    }

    public function edit($id, Request $request)
    {
        $item = MstrBaselType::findOrFail($id);
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$item]);
        abort(404);
    }

    public function update($id, Request $request)
    {
        $data = $request->validate(['type_name' => 'required|string|max:255']);
        $item = MstrBaselType::findOrFail($id);
        $item->update($data);
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$item]);
        return redirect()->back()->with('success','Updated');
    }

    public function destroy($id, Request $request)
    {
        $item = MstrBaselType::findOrFail($id);
        $item->delete();
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success']);
        return redirect()->back()->with('success','Deleted');
    }
}