<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$customer = App\Models\Customer::find(2);
echo "Customer ID: " . $customer->id . "\n";
echo "Name: " . $customer->customer_name . "\n";
echo "Credit Terms: '" . $customer->credit_terms . "'\n";

$sale = App\Models\Sale::latest()->first();
echo "\nLatest Sale ID: " . $sale->id . "\n";
echo "Date: " . $sale->sale_date . "\n";
echo "Due Amount: " . ($sale->due_amount ?? 'N/A') . "\n";
echo "Total Net: " . $sale->total_net . "\n";

$dueDate = Carbon\Carbon::parse($sale->sale_date)->addDays((int)$customer->credit_terms);
echo "Calculated Due Date: " . $dueDate->toDateString() . "\n";
echo "Now: " . now()->toDateString() . "\n";
echo "Is Overdue: " . (now()->startOfDay()->gt($dueDate->startOfDay()) ? "YES" : "NO") . "\n";
