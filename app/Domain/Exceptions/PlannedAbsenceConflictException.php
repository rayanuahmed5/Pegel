<?php

namespace App\Domain\Exceptions;

use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlannedAbsenceConflictException extends DomainException
{
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'error' => 'PlannedAbsenceConflict',
            'message' => $this->getMessage(),
        ], 422);
    }
}
