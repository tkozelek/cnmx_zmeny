<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRatesRequest;
use App\Models\Rate;

class RateController extends Controller
{
    public function __invoke(StoreRatesRequest $request)
    {
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();

        Rate::upsert($validated, ['user_id'], ['weekday', 'saturday', 'sunday', 'break']);

        return response()->json(['message' => 'rates_updated']);
    }
}
