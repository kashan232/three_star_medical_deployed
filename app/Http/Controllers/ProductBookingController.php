<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductBooking;
use App\Models\Sale;
use App\Models\Warehouse;
use App\Models\Hr\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductBookingController extends Controller
{
    public function index()
    {
        // User Request: "getting only posted sale information"
        // We fetch Sales where status is 'posted'.
        // We eager load 'items' and 'items.product' to display details in the view.
        $sales = Sale::with(['customer_relation', 'items.product'])
            ->where('sale_status', 'posted') // Show ONLY posted sales
            ->latest()
            ->get();

        return view('admin_panel.sale.sale_receipt_note.index', compact('sales'));
    }

    public function receipt($id)
    {
        $booking = ProductBooking::with('customer_relation')->findOrFail($id);

        return view('admin_panel.booking.receipt', compact('booking'));
    }

    public function create(Request $request)
    {
        $Customer = Customer::all();
        $Warehouse = Warehouse::all();
        $sales = Sale::with(['customer_relation', 'items.product'])->get();
        // Use Sin- for Sale Invoice Note and So- for Sale Order
        $nextInvoice = Sale::generateInvoiceNo($request->branch_id, $request->mode == 'so' ? 'SO-' : 'SIN-'); // Bookings are usually SOs

        // Filter accounts (Cash/Bank) for Payment Voucher
        $accounts = app(\App\Services\BalanceService::class)->getPaymentAccounts();

        // Fetch active employees with 'is_sale_officer' designation for commission attribution
        $employees = \App\Models\Hr\Employee::active()
            ->whereHas('designation', function ($q) {
                $q->where('is_sale_officer', 1);
            })
            ->orderBy('first_name')
            ->get();

        return view('admin_panel.sale.sale_receipt_note.create', compact('Warehouse', 'Customer', 'nextInvoice', 'accounts', 'employees', 'sales'));
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $product_ids = $request->product_id;
            $product_names = $request->product_id;
            $product_codes = $request->item_code;
            $brands = $request->uom;
            $units = $request->unit;
            $prices = $request->price;
            $discounts = $request->item_disc;
            $quantities = $request->qty;
            $totals = $request->total;
            $colors = $request->color;

            $combined_products = [];
            $combined_codes = [];
            $combined_brands = [];
            $combined_units = [];
            $combined_prices = [];
            $combined_discounts = [];
            $combined_qtys = [];
            $combined_totals = [];
            $combined_colors = [];

            $total_items = 0;

            foreach ($product_ids as $index => $product_id) {
                $qty = $quantities[$index] ?? 0;
                $price = $prices[$index] ?? 0;

                if (! $product_id || ! $qty || ! $price) {
                    continue;
                }

                $combined_products[] = $product_names[$index] ?? '';
                $combined_codes[] = $product_codes[$index] ?? '';
                $combined_brands[] = $brands[$index] ?? '';
                $combined_units[] = $units[$index] ?? '';
                $combined_prices[] = $prices[$index] ?? 0;
                $combined_discounts[] = $discounts[$index] ?? 0;
                $combined_qtys[] = $quantities[$index] ?? 0;
                $combined_totals[] = $totals[$index] ?? 0;

                $rowColors = $colors[$index] ?? [];
                $combined_colors[] = json_encode($rowColors);

                $total_items += $qty;
            }

            $booking = new ProductBooking;
            $booking->customer = $request->customer;
            $booking->reference = $request->reference;
            $booking->product = implode(',', $combined_products);
            $booking->product_code = implode(',', $combined_codes);
            $booking->brand = implode(',', $combined_brands);
            $booking->unit = implode(',', $combined_units);
            $booking->per_price = implode(',', $combined_prices);
            $booking->per_discount = implode(',', $combined_discounts);
            $booking->qty = implode(',', $combined_qtys);
            $booking->per_total = implode(',', $combined_totals);
            $booking->color = json_encode($combined_colors);

            $booking->total_amount_Words = $request->total_amount_Words;
            $booking->total_bill_amount = $request->total_subtotal;
            $booking->total_extradiscount = $request->total_extra_cost;
            $booking->total_net = $request->total_net;

            $booking->cash = $request->cash;
            $booking->card = $request->card;
            $booking->change = $request->change;
            $booking->total_items = $total_items;
            $booking->save();

            DB::commit();

            return back()->with('success', 'Product booking saved (no stock reduced).');
        } catch (\Exception $e) {
            DB::rollback();

            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
