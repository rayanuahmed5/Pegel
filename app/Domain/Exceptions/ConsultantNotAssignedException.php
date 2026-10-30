<?php

namespace App\Domain\Exceptions;

use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsultantNotAssignedException extends DomainException
{
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'error' => 'ConsultantNotAssigned',
            'message' => $this->getMessage(),
        ], 403);
    }
}
