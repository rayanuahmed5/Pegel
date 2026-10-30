<?php

namespace App\Domain\Exceptions;

use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OverlappingBookingException extends DomainException
{
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'error' => 'OverlappingBooking',
            'message' => $this->getMessage(),
        ], 422);
    }
}
