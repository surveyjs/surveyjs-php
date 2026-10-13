<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

// #region sjs:I.6.server
// GET /api/customers/exists?email= — answers emailExists()
Route::get('/api/customers/exists', function (Request $request) {
    $email = Str::lower(trim((string) $request->query('email')));

    return ['exists' => DB::table('customers')->where('email', $email)->exists()];
});

// GET /api/shipping?postcode= — answers shippingCost(): the longest matching postcode prefix wins,
// { "price": null } when none matches (the form shows "We don't deliver to this postcode yet")
Route::get('/api/shipping', function (Request $request) {
    $postcode = Str::upper(str_replace(' ', '', (string) $request->query('postcode')));
    // only a whole German (10115) or UK (SW1A 1AA) postcode gets a price, not anything that starts with a prefix
    if (! preg_match('/^(\d{5}|[A-Z]{1,2}\d[A-Z\d]?\d[A-Z]{2})$/', $postcode)) {
        return ['price' => null];
    }
    $rate = DB::table('shipping_rates')->get()
        ->filter(fn ($rate) => str_starts_with($postcode, $rate->postcode_prefix))
        ->sortByDesc(fn ($rate) => strlen($rate->postcode_prefix))
        ->first();

    return ['price' => $rate ? (float) $rate->price : null];
});
// #endregion
