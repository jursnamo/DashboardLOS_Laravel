<?php

namespace App\Http\Controllers;

use App\Models\MstrEconomySector;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MstrEconomySectorController extends Controller
{
    public function index(Request $request)
    {
        $items = MstrEconomySector::orderBy('sector_name')->get();
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$items]);
        return view('mstr_economy_sectors.index', compact('items'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id' => 'nullable|string|max:50',
            'sector_name' => 'required|string|max:255',
        ]);
        if (empty($data['id'])) {
            $data['id'] = (string) Str::uuid();
        }
        $item = MstrEconomySector::create($data);
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$item]);
        return redirect()->back()->with('success','Saved');
    }

    public function edit($id)
    {
        $item = MstrEconomySector::findOrFail($id);
        if (request()->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$item]);
        abort(404);
    }

    public function update($id, Request $request)
    {
        $data = $request->validate(['sector_name' => 'required|string|max:255']);
        $item = MstrEconomySector::findOrFail($id);
        $item->update($data);
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success','data'=>$item]);
        return redirect()->back()->with('success','Updated');
    }

    public function destroy($id, Request $request)
    {
        $item = MstrEconomySector::findOrFail($id);
        $item->delete();
        if ($request->wantsJson()) return response()->json(['code'=>200,'message'=>'ok success']);
        return redirect()->back()->with('success','Deleted');
    }
}
