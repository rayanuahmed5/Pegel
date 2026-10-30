<?php

namespace App\Http\Controllers;

use App\Domain\Services\ForecastService;
use App\Http\Requests\ForecastQueryRequest;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class ForecastController extends Controller
{
    public function __construct(
        protected ForecastService $forecastService
    ) {}

    public function __invoke(ForecastQueryRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $user = $request->user();
        $canViewForecast = $user->isSuperAdmin()
            || ($user->isProjectLead() && $purchaseOrder->project_lead_id === $user->id)
            || in_array($user->role, ['purchasing', 'finance'], true);

        abort_unless($canViewForecast, 403);

        $validated = $request->validated();
        $asOfDate = isset($validated['as_of_date']) && $validated['as_of_date'] !== null
            ? Carbon::parse($validated['as_of_date'])
            : null;

        return response()->json($this->forecastService->calculateForecast($purchaseOrder, $asOfDate));
    }
}
