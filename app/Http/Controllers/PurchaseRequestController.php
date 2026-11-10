<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Models\StudentBalance;
use App\Services\PurchaseRequestService;
use App\Services\TenantConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PurchaseRequestController extends Controller
{
    /**
     * Show every request the signed-in parent is allowed to see.
     */
    public function parentIndex(Request $request): Response
    {
        $base = $this->parentVisibleQuery($request);

        $requests = (clone $base)
            ->with(['student:id,name', 'status:id,status', 'items'])
            ->orderByDesc('requested_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $pending = (clone $base)->pending();

        return Inertia::render('parent/purchase-requests', [
            'requests' => $requests,
            'stats' => [
                'pending_count' => (clone $pending)->count(),
                'pending_total' => number_format((float) (clone $pending)->sum('total_amount'), 2, '.', ''),
                'request_count' => (clone $base)->count(),
                'student_count' => (clone $base)->distinct()->count('student_id'),
            ],
        ]);
    }

    /**
     * Show the signed-in student's own requests and current balance.
     */
    public function studentIndex(Request $request): Response
    {
        $user = $request->attributes->get('auth_user');

        if ($user === null) {
            abort(403, 'You cannot view these purchase requests.');
        }

        $studentId = (int) $user->id;

        $base = PurchaseRequest::query()->where('student_id', $studentId);

        $requests = (clone $base)
            ->with(['status:id,status', 'items'])
            ->orderByDesc('requested_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $balance = StudentBalance::query()->find($studentId);

        return Inertia::render('student/purchase-requests', [
            'requests' => $requests,
            'balance' => $balance === null ? '0.00' : $balance->balance,
            'stats' => [
                'pending_count' => (clone $base)->pending()->count(),
                'pending_total' => number_format((float) (clone $base)->pending()->sum('total_amount'), 2, '.', ''),
                'request_count' => (clone $base)->count(),
            ],
        ]);
    }

    /**
     * Save a new pending request and all of its line items.
     */
    public function store(Request $request, PurchaseRequestService $requestService): RedirectResponse
    {
        $user = $request->attributes->get('auth_user');

        if ($user === null || $user->role !== 'student') {
            abort(403, 'Only a student can create a purchase request.');
        }

        $validated = $request->validate([
            'store_name' => ['required', 'string', 'max:150', 'not_regex:/^\s*$/'],
            'items' => [
                'required',
                'array',
                'min:1',
                'max:'.PurchaseRequestService::MAX_ITEMS,
            ],
            'items.*' => ['required', 'array'],
            'items.*.material_name' => ['required', 'string', 'max:150', 'not_regex:/^\s*$/'],
            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
                'max:'.PurchaseRequestService::MAX_QUANTITY,
            ],
            'items.*.unit_price' => [
                'required',
                'numeric',
                'min:0.01',
                'max:99999999.99',
                'decimal:0,2',
            ],
        ], [
            'store_name.required' => 'Enter the store name.',
            'store_name.not_regex' => 'Enter the store name.',
            'items.required' => 'Add at least one item to this request.',
            'items.min' => 'Add at least one item to this request.',
            'items.max' => 'A request can contain at most '.PurchaseRequestService::MAX_ITEMS.' items.',
            'items.*.material_name.required' => 'Enter an item name.',
            'items.*.material_name.not_regex' => 'Enter an item name.',
            'items.*.quantity.required' => 'Enter a quantity.',
            'items.*.quantity.integer' => 'Enter a whole number for the quantity.',
            'items.*.quantity.min' => 'The quantity must be at least 1.',
            'items.*.quantity.max' => 'The quantity is too large.',
            'items.*.unit_price.required' => 'Enter a unit price.',
            'items.*.unit_price.numeric' => 'Enter a valid unit price.',
            'items.*.unit_price.min' => 'The unit price must be at least 0.01.',
            'items.*.unit_price.decimal' => 'Enter a price with up to 2 decimals.',
        ]);

        try {
            $requestService->createForStudent((int) $user->id, $validated);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'request' => 'We could not save your purchase request. Please try again.',
            ]);
        }

        return to_route('student.purchase-requests.index')->with(
            'purchase_request_success',
            'Purchase request sent to your parents.'
        );
    }

    /**
     * Cancel one pending request owned by the signed-in student.
     */
    public function cancel(Request $request, int $id, PurchaseRequestService $requestService): RedirectResponse
    {
        $user = $request->attributes->get('auth_user');

        if ($user === null || $user->role !== 'student') {
            abort(403, 'Only the student who made this request can cancel it.');
        }

        $purchaseRequest = PurchaseRequest::query()
            ->with('status')
            ->where('student_id', (int) $user->id)
            ->findOrFail($id);

        try {
            $requestService->cancel($purchaseRequest);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'request' => 'We could not cancel this purchase request. Please try again.',
            ]);
        }

        return to_route('student.purchase-requests.index')->with(
            'purchase_request_success',
            'Purchase request cancelled.'
        );
    }

    /**
     * The creator sees every request; a co-parent only requests from linked students.
     *
     * @return Builder<PurchaseRequest>
     */
    private function parentVisibleQuery(Request $request): Builder
    {
        $user = $request->attributes->get('auth_user');

        if ($user === null) {
            abort(403, 'You cannot view these purchase requests.');
        }

        $query = PurchaseRequest::query();

        if ((int) $user->is_creator !== 1) {
            $query->whereIn(
                'student_id',
                DB::connection(TenantConnection::CONNECTION)
                    ->table('parent_student')
                    ->where('parent_id', (int) $user->id)
                    ->select('student_id')
            );
        }

        return $query;
    }
}
