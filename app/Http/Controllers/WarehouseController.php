<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    // Return warehouses for a given product_id
    public function getWarehouses(Request $request)
    {
        $productId = $request->input('product_id');

        // Get all warehouses first
        $allWarehouses = Warehouse::all();

        // Get stock entries for this product
        $warehouseStocks = WarehouseStock::with(['stockWarehouse', 'product'])
            ->where('product_id', $productId)
            ->get()
            ->keyBy('warehouse_id');

        $response = $allWarehouses->map(function ($warehouse) use ($warehouseStocks) {
            $ws = $warehouseStocks->get($warehouse->id);
            $stockVal = 0;

            if ($ws) {
                $ppb = ($ws->product && $ws->product->pieces_per_box > 0) ? $ws->product->pieces_per_box : 1;

                // Robust Calculation:
                // Trust 'quantity' (Boxes) as the primary source if it exists, as users usually trade in boxes.
                // Recalculate pieces from quantity to ensure consistency.
                $calcPieces = $ws->quantity * $ppb;

                // Use calculated pieces if it differs significantly from stored total_pieces (e.g. data sync issue)
                // or if total_pieces is 0 but quantity > 0.
                if (abs($calcPieces - $ws->total_pieces) > 0.1) {
                    $stockVal = $calcPieces;
                } else {
                    $stockVal = $ws->total_pieces;
                }
            }

            $ppb = $ws && $ws->product ? ($ws->product->pieces_per_box > 0 ? $ws->product->pieces_per_box : 1) : 1;
            $stockDisplay = $stockVal;
            if ($ppb > 1) {
                $b = floor($stockVal / $ppb);
                $l = $stockVal % $ppb;
                $stockDisplay = $l > 0 ? "$b.$l" : $b;
            }

            return [
                'warehouse_id' => $warehouse->id,
                'warehouse_name' => $warehouse->warehouse_name,
                'stock' => $stockVal, // Total pieces
                'stock_display' => $stockDisplay,
                'boxes' => $ws ? $ws->quantity : 0, // Actual box quantity from DB
                'ppb' => $ppb,
                'size_mode' => $ws && $ws->product ? $ws->product->size_mode : 'std',
            ];
        });

        return response()->json($response);
    }

    // VendorController.php aur WarehouseController.php same hoga
    public function index()
    {
        if (! auth()->user()->can('warehouse.view')) {
            abort(403, 'Unauthorized action.');
        }

        $user = auth()->user();
        $query = Warehouse::with(['user', 'branch']);

        if (! $user->hasRole('Super Admin')) {
            $query->where('branch_id', $user->branch_id);
        }

        $warehouses = $query->get();
        $branches = \App\Models\Branch::all();

        return view('admin_panel.warehouses.index', compact('warehouses', 'branches')); // ya warehouses.index
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if ($user->hasRole('Super Admin')) {
            $request->validate([
                'branch_id' => 'required|exists:branches,id',
                'warehouse_name' => 'required|string|max:255',
            ]);
        } else {
            $request->validate([
                'warehouse_name' => 'required|string|max:255',
            ]);
        }

        $allowedFields = ['warehouse_name', 'creater_id', 'branch_id', 'location', 'remarks'];
        $data = $request->only($allowedFields);

        if (! $user->hasRole('Super Admin')) {
            $data['branch_id'] = $user->branch_id;
        }

        if ($request->id) {
            if (! $user->can('warehouse.edit')) {
                return back()->with('error', 'Unauthorized action.');
            }
            Warehouse::findOrFail($request->id)->update($data);

            return back()->with('success', 'Warehouse Updated Successfully');
        } else {
            if (! $user->can('warehouse.create')) {
                return back()->with('error', 'Unauthorized action.');
            }
            // Ensure creater_id is set if not provided or to ensure it's the current user
            $data['creater_id'] = $user->id;

            Warehouse::create($data);

            return back()->with('success', 'Warehouse Created Successfully');
        }
    }

    public function delete($id)
    {
        if (! auth()->user()->can('warehouse.delete')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }
        Warehouse::findOrFail($id)->delete();

        return response()->json([
            'success' => 'Warehouse Deleted Successfully',
            'reload' => true,
        ]);
    }
}
