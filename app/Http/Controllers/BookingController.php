<?php

namespace App\Http\Controllers;

use App\Domain\Exceptions\ArbzgViolationException;
use App\Domain\Exceptions\BudgetExceededException;
use App\Domain\Exceptions\ConsultantNotAssignedException;
use App\Domain\Exceptions\OverlappingBookingException;
use App\Domain\Exceptions\PlannedAbsenceConflictException;
use App\Domain\Services\BookingService;
use App\Http\Requests\StoreTimeBookingRequest;
use App\Models\Consultant;
use App\Models\PurchaseOrder;
use App\Models\TimeBooking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {}

    /**
     * Store a new time booking with server-side legal & budget validation.
     */
    public function store(StoreTimeBookingRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        $user = $request->user();
        abort_unless($user->isConsultant(), 403);

        /** @var Consultant|null $consultant */
        $consultant = $user->consultant;

        if (! $consultant) {
            return back()->withErrors(['error' => 'No active consultant profile associated with this account.']);
        }

        /** @var PurchaseOrder $order */
        $order = PurchaseOrder::query()->findOrFail($validated['purchase_order_id']);

        try {
            $booking = $this->bookingService->book(
                order: $order,
                consultant: $consultant,
                date: $validated['date'],
                durationHours: (float) $validated['duration_hours'],
                description: $validated['description'],
                accountingObject: $validated['accounting_object'] ?? null,
                activity: $validated['activity'] ?? null,
                startTime: $validated['start_time'] ?? null,
                endTime: $validated['end_time'] ?? null,
                status: 'pending'
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Booking logged successfully on order {$order->order_number}.",
                    'booking_id' => $booking->id,
                ], 201);
            }

            return back()->with('success', "Booking logged successfully on order {$order->order_number} ({$validated['duration_hours']}h).");
        } catch (
            BudgetExceededException|
            ArbzgViolationException|
            OverlappingBookingException|
            PlannedAbsenceConflictException|
            ConsultantNotAssignedException $e
        ) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 400);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Approve a proof of work entry (Project Lead role).
     */
    public function approve(Request $request, TimeBooking $booking): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $user->isSuperAdmin() ||
                ($user->isProjectLead() && $booking->purchaseOrder->project_lead_id === $user->id),
            403
        );

        $booking->approve($user);

        return back()->with('success', "Booking #{$booking->id} approved.");
    }

    /**
     * Reject a booking entry with feedback.
     */
    public function reject(Request $request, TimeBooking $booking): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $user->isSuperAdmin() ||
                ($user->isProjectLead() && $booking->purchaseOrder->project_lead_id === $user->id),
            403
        );

        $reason = $request->input('reason', 'Work description or hours require clarification.');
        $booking->reject($user, $reason);

        return back()->with('success', "Booking #{$booking->id} rejected with feedback.");
    }
}
