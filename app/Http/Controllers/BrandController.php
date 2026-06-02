<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BrandController extends Controller
{
    public function index()
    {
        $Brand = Brand::get();
        return view("admin_panel.brand.index", compact('Brand'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:brands,name,' . $request->edit_id,
        ]);

        // ✅ AJAX SAFE VALIDATION (NO REDIRECTS)
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {

            // UPDATE
            if ($request->filled('edit_id')) {

                $brand = Brand::find($request->edit_id);

                if (!$brand) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Brand not found'
                    ], 404);
                }

                $message = 'Brand Updated Successfully';
            }
            // CREATE
            else {
                $brand = new Brand();
                $message = 'Brand Created Successfully';
            }

            $brand->name = $request->name;
            $brand->save();

            // ALWAYS JSON RESPONSE FOR AJAX
            return response()->json([
                'status' => true,
                'message' => $message,
                'data' => [
                    'id' => $brand->id,
                    'name' => $brand->name
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
        $brand = Brand::find($id);

        if (!$brand) {
            return response()->json([
                'status' => false,
                'message' => 'Brand Not Found'
            ], 404);
        }

        $brand->delete();

        return response()->json([
            'status' => true,
            'message' => 'Brand Deleted Successfully'
        ]);
    }
}