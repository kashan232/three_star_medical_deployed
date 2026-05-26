<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Branch;
use App\Http\Traits\BranchScoped;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use BranchScoped;
    public function index()
    {
        $users    = User::where('usertype', '!=', 'super_admin')->with('branch')->get();
        
        if (auth()->user()->isSuperAdmin()) {
            $allRoles = Role::all();
        } else {
            $allRoles = Role::where('name', '!=', 'Super Admin')->get();
        }
        
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('admin_panel.users.users', compact('users', 'allRoles', 'branches'));
    }

    public function store(Request $request)
    {
        $editId = $request->edit_id ?? null;
        $passwordRule = $editId ? 'nullable' : 'required';

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|unique:users,email,'.$request->edit_id,
            'password' => $passwordRule,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        if (! empty($editId)) {
            $user = User::find($editId);
            $msg = [
                'success' => 'User Updated Successfully',
                'reload' => true,
            ];
        } else {
            $user = new User;
            $msg = [
                'success' => 'User Created Successfully',
                'redirect' => route('users.index'),
            ];
        }

        $user->name     = $request->name;
        $user->email    = $request->email;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        // Assign branch_id (only super admin allowed to set this)
        if ($request->has('branch_id')) {
            $user->branch_id = $request->branch_id ?: null;
        }
        $user->save();

        // Protect Super Admin role assignment
        $requestedRoles = $request->roles ?? [];
        if (!auth()->user()->isSuperAdmin() && in_array('Super Admin', $requestedRoles)) {
            $requestedRoles = array_diff($requestedRoles, ['Super Admin']);
        }
        
        $user->syncRoles($requestedRoles);

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
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');

    }

    public function updateRoles(Request $request)
    {
        $user = User::findOrFail($request->edit_id);

        // Assign new roles (by name)
        $requestedRoles = $request->roles ?? [];
        if (!auth()->user()->isSuperAdmin() && in_array('Super Admin', $requestedRoles)) {
            $requestedRoles = array_diff($requestedRoles, ['Super Admin']);
        }
        $user->syncRoles($requestedRoles);

        // Return JSON so AJAX handlers get a clear response
        return response()->json(['success' => 'User roles updated successfully!', 'reload' => true]);
    }
}
