<?php

namespace App\Http\Controllers;

use App\Models\Subcategory;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubcategoryController extends Controller
{
    public function index()
    {
        $category = Category::get();
        $subcategory = Subcategory::with('category')->get();

        return view("admin_panel.subcategory.index", compact('subcategory', 'category'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:subcategories,name,' . $request->edit_id,
            'category_id' => 'required',
        ]);

        // ✅ ALWAYS AJAX JSON (no redirects ever)
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            // UPDATE
            if ($request->filled('edit_id')) {

                $subcategory = Subcategory::find($request->edit_id);

                if (!$subcategory) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Record not found',
                    ], 404);
                }

                $subcategory->name = $request->name;
                $subcategory->category_id = $request->category_id;
                $subcategory->save();

                return response()->json([
                    'status' => true,
                    'message' => 'Subcategory Updated Successfully',
                    'data' => [
                        'id' => $subcategory->id,
                        'name' => $subcategory->name,
                        'category_id' => $subcategory->category_id,
                    ]
                ]);
            }

            // CREATE
            $subcategory = new Subcategory();
            $subcategory->name = $request->name;
            $subcategory->category_id = $request->category_id;
            $subcategory->save();

            return response()->json([
                'status' => true,
                'message' => 'Subcategory Created Successfully',
                'data' => [
                    'id' => $subcategory->id,
                    'name' => $subcategory->name,
                    'category_id' => $subcategory->category_id,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Server Error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function delete($id)
    {
        $subcategory = Subcategory::find($id);

        if (!$subcategory) {
            return response()->json([
                'status' => false,
                'message' => 'Subcategory Not Found'
            ], 404);
        }

        $subcategory->delete();

        return response()->json([
            'status' => true,
            'message' => 'Subcategory Deleted Successfully',
            'reload' => true
        ]);
    }
}