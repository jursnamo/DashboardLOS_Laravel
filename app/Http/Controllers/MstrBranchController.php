<?php

namespace App\Http\Controllers;

use App\Models\MstrBranch;
use Illuminate\Http\Request;

class MstrBranchController extends Controller
{
    public function index(Request $request)
    {
        $items = MstrBranch::orderBy('branch_name')->get();
        if ($request->wantsJson()) {
            return response()->json(['code' => 200, 'message' => 'ok success', 'data' => $items]);
        }
        return view('mstr_branches.index', compact('items'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_name' => 'required|string|max:255',
            'area_region' => 'nullable|string|max:255',
        ]);

        $item = MstrBranch::create($data);
        if ($request->wantsJson()) {
            return response()->json(['code' => 200, 'message' => 'ok success', 'data' => $item]);
        }
        return redirect()->back()->with('success', 'Saved');
    }

    

    public function update($id, Request $request)
    {
        $data = $request->validate([
            'branch_name' => 'required|string|max:255',
            'area_region' => 'nullable|string|max:255',
        ]);

        $item = MstrBranch::findOrFail($id);
        $item->update($data);

        if ($request->wantsJson()) {
            return response()->json(['code' => 200, 'message' => 'ok success', 'data' => $item]);
        }
        return redirect()->back()->with('success', 'Updated');
    }

    public function destroy($id, Request $request)
    {
        $item = MstrBranch::findOrFail($id);
        $item->delete();
        if ($request->wantsJson()) {
            return response()->json(['code' => 200, 'message' => 'ok success']);
        }
        return redirect()->back()->with('success', 'Deleted');
    }
}
