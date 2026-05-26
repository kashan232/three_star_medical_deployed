<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        if (auth()->user()->isSuperAdmin()) {
            $permissions = Permission::orderBy('name',"ASC")->get();
        } else {
            $permissions = Permission::where('name', 'not like', 'branches.%')->orderBy('name', "ASC")->get();
        }
        return view('admin_panel.permissions.permission', compact('permissions'));
    }

    public function store(Request $request)
    {
        return response()->json([
            'errors' => ['name' => ['System management of permissions via UI is disabled for security.']]
        ], 403);
    }

    /**
     * Display the specified resource.
     */

    /**
     * Remove the specified resource from storage.
     */
    public function delete(string $id)
    {
        return redirect()->route('permissions.index')->with('error', 'Deleting permissions via UI is disabled.');
    }
}
