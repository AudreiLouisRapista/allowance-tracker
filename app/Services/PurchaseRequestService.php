<?php

namespace App\Services;

use App\Models\FamilyMember;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PurchaseRequestService
{
    // A single request cannot contain more line items than this.
    public const MAX_ITEMS = 50;

    // A quantity fits comfortably in the unsigned integer column.
    public const MAX_QUANTITY = 9999;

    // decimal(10,2) stores at most 99,999,999.99.
    public const MAX_MONEY_CENTS = 9_999_999_999;

    /**
     * Create a pending request for the signed-in student and its line items.
     *
     * @param  array{store_name?: mixed, items?: array<int, array{material_name?: mixed, quantity?: mixed, unit_price?: mixed}>}  $data
     */
    public function createForStudent(int $studentId, array $data): PurchaseRequest
    {
        $student = FamilyMember::active()->onlyStudents()->find($studentId);

        if ($student === null) {
            throw ValidationException::withMessages([
                'student_id' => 'This student was not found in your family.',
            ]);
        }

        $storeName = $this->cleanName($data['store_name'] ?? '', 'store_name', 'Enter the store name.');
        $items = $this->normalizeItems($data['items'] ?? []);
        $requestedAt = now();

        $request = DB::connection(TenantConnection::CONNECTION)->transaction(
            function () use ($student, $storeName, $items, $requestedAt): PurchaseRequest {
                $request = PurchaseRequest::create([
                    'student_id' => $student->id,
                    'purchase_req_status_id' => $this->statusId(PurchaseRequestStatus::PENDING),
                    'store_name' => $storeName,
                    'total_amount' => $this->formatCents($this->totalCents($items)),
                    // Nothing has been funded yet; reviews and funds arrive in Phase 7.
                    'lacking_amount' => '0.00',
                    'requested_at' => $requestedAt,
                ]);

                $request->items()->createMany($items);

                return $request->load(['student:id,name', 'status:id,status', 'items']);
            }
        );

        return $request;
    }

    /**
     * Cancel a pending request owned by the signed-in student.
     */
    public function cancel(PurchaseRequest $request): PurchaseRequest
    {
        if ($request->status?->status !== PurchaseRequestStatus::PENDING) {
            throw ValidationException::withMessages([
                'request' => 'Only pending requests can be cancelled.',
            ]);
        }

        $request->purchase_req_status_id = $this->statusId(PurchaseRequestStatus::CANCELLED);
        $request->cancelled_at = now();
        $request->save();

        return $request->load('status:id,status');
    }

    /**
     * Validate, trim, and price every line item before anything is written.
     *
     * @param  array<int, array{material_name?: mixed, quantity?: mixed, unit_price?: mixed}>  $items
     * @return array<int, array{material_name: string, quantity: int, unit_price: string, line_total: string, created_at: CarbonImmutable}>
     */
    private function normalizeItems(array $items): array
    {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one item to this request.',
            ]);
        }

        if (count($items) > self::MAX_ITEMS) {
            throw ValidationException::withMessages([
                'items' => 'A request can contain at most '.self::MAX_ITEMS.' items.',
            ]);
        }

        $normalized = [];
        $totalCents = 0;
        $createdAt = now();

        foreach (array_values($items) as $index => $item) {
            $materialName = $this->cleanName(
                $item['material_name'] ?? '',
                "items.{$index}.material_name",
                'Enter an item name.'
            );

            $quantity = (int) ($item['quantity'] ?? 0);

            if ($quantity < 1 || $quantity > self::MAX_QUANTITY) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => 'Enter a quantity between 1 and '.self::MAX_QUANTITY.'.',
                ]);
            }

            $unitPrice = $item['unit_price'] ?? null;

            if (! is_numeric($unitPrice)) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit_price" => 'Enter a valid unit price.',
                ]);
            }

            $unitPriceCents = (int) round((float) $unitPrice * 100);

            if ($unitPriceCents < 1) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit_price" => 'The unit price must be at least 0.01.',
                ]);
            }

            if ($unitPriceCents > self::MAX_MONEY_CENTS) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit_price" => 'The unit price is too large.',
                ]);
            }

            $lineTotalCents = $quantity * $unitPriceCents;

            if ($lineTotalCents > self::MAX_MONEY_CENTS) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => 'The quantity and unit price make this line too large.',
                ]);
            }

            $totalCents += $lineTotalCents;

            if ($totalCents > self::MAX_MONEY_CENTS) {
                throw ValidationException::withMessages([
                    'items' => 'The request total is too large.',
                ]);
            }

            $normalized[] = [
                'material_name' => $materialName,
                'quantity' => $quantity,
                'unit_price' => $this->formatCents($unitPriceCents),
                'line_total' => $this->formatCents($lineTotalCents),
                'created_at' => $createdAt,
            ];
        }

        return $normalized;
    }

    /**
     * Trim a name and reject blank or overlong values with a field-specific error.
     */
    private function cleanName(mixed $value, string $field, string $message): string
    {
        $name = trim((string) $value);

        if ($name === '') {
            throw ValidationException::withMessages([$field => $message]);
        }

        if (Str::length($name) > 150) {
            throw ValidationException::withMessages([$field => 'Use 150 characters or fewer.']);
        }

        return $name;
    }

    /**
     * Sum already-normalized item line totals.
     *
     * @param  array<int, array{line_total: string}>  $items
     */
    private function totalCents(array $items): int
    {
        return array_reduce(
            $items,
            fn (int $total, array $item): int => $total + (int) round((float) $item['line_total'] * 100),
            0
        );
    }

    /**
     * Convert integer cents to the decimal string the database expects.
     */
    private function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    /**
     * The id of a status row, for example the id of "pending".
     *
     * @return int<1, max>
     */
    private function statusId(string $status): int
    {
        $id = PurchaseRequestStatus::query()->where('status', $status)->value('id');

        if (! is_numeric($id) || (int) $id < 1) {
            throw new RuntimeException("The purchase request status \"{$status}\" is missing.");
        }

        return (int) $id;
    }
}
