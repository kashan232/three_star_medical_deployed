$request = new \Illuminate\Http\Request();
$request->merge(['start_date' => '2026-04-01', 'end_date' => '2026-04-30', 'product_id' => 'all', 'type' => 'all']);
$controller = new \App\Http\Controllers\ReportingController();
echo json_encode($controller->fetchPriceAdjustmentReport($request)->getData());
