<?php

namespace App\Http\Controllers\Hours;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hours\StoreRateRequest;
use App\Models\Rate;
use Illuminate\Http\JsonResponse;

/**
 * A user's own pay rates. One row per user per team, so this is an upsert.
 */
class RateController extends Controller
{
    public function __invoke(StoreRateRequest $request): JsonResponse
    {
        Rate::updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->validated(),
        );

        return response()->json(['message' => 'rates_updated']);
    }
}
