<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;


class BranchController extends Controller
{
    /**
     * Only super admins can manage branches.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('super_admin');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $branches = Branch::withCount('users')->get();
        return view('admin_panel.branch.branch', compact('branches')); 
    }

    public function store(Request $request)
    {
        $editId = $request->edit_id ?? null;
        // Custom validation messages for better user feedback
    $messages = [
        'name.required' => 'The Branch Name field is mandatory. Please enter a name.',
        'name.max' => 'The Branch Name is too long. Please restrict it to 255 characters.',
        'branch_code.required' => 'You must provide a Unique Branch Code.',
        'branch_code.unique' => 'This Branch Code is already in use. Please choose another one.',
        'branch_code.max' => 'The Branch Code should not exceed 50 characters.',
    ];

    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'branch_code' => ['required', 'string', 'max:50', Rule::unique('branches')->ignore($editId)],
        'address' => 'nullable|string',
        'number' => 'nullable|string',
    ], $messages);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $validator->errors()
        ]);
    }


        // Step 3: Save or update logic
        if (!empty($editId)) {
            $branch = Branch::find($editId);
            $msg = [
                'success' => 'Branch Updated Successfully',
                'reload' => true
            ];
        } else {
            $branch = new Branch();
            $msg = [
                'success' => 'Branch Created Successfully',
                'redirect' => route('branch.index')
            ];
        }

        $branch->name = $request->name;
        $branch->branch_code = $request->branch_code;
        $branch->address = $request->address;
        $branch->number = $request->number;
        $branch->is_active = $request->has('is_active') ? 1 : 0;
        $branch->save();

        return response()->json($msg);
        
    }

    /**
     * Display the specified resource.
     */
  
    /**
     * Remove the specified resource from storage.
     */
    public function delete(string $id)
    {
        $branch = Branch::findOrFail($id);
        $branch->delete();

        return redirect()->route('branch.index')->with('success', 'Branch deleted successfully.');

    }
}
