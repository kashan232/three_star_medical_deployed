<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\JournalEntry;

$entries = JournalEntry::where('description', 'like', "%GRN-0001%")->get();
if ($entries->isEmpty()) {
    $entries = JournalEntry::latest()->limit(20)->get();
}

foreach ($entries as $e) {
    echo "ID: {$e->id}, Account: {$e->account_id}, Party: {$e->party_type} #{$e->party_id}, Desc: {$e->description}, Source: {$e->source_type} #{$e->source_id}\n";
}
