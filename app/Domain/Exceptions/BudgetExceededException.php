<?php

namespace App\Domain\Exceptions;

use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetExceededException extends DomainException
{
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'error' => 'BudgetExceeded',
            'message' => $this->getMessage(),
        ], 422);
    }
}
