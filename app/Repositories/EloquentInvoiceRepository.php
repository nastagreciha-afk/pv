<?php

namespace App\Repositories;

use App\Models\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentInvoiceRepository implements InvoiceRepositoryInterface
{
    /**
     * Get all invoices with pagination
     */
    public function getAll(int $perPage = 20, int $page = 1): LengthAwarePaginator
    {
        return Invoice::query()
            ->orderByDesc('created_at')
            ->paginate(perPage: $perPage, page: $page);
    }

    /**
     * Find invoice by ID
     */
    public function find(int $id): ?Invoice
    {
        return Invoice::find($id);
    }

    /**
     * Create new invoice
     */
    public function create(array $data): Invoice
    {
        return Invoice::create($data);
    }

    /**
     * Update existing invoice
     */
    public function update(Invoice $invoice, array $data): Invoice
    {
        $fillable = array_flip($invoice->getFillable());
        $data = array_intersect_key($data, $fillable);

        $data['updated_at'] = now();
        $data['issue_date'] = $this->toDateString($data['issue_date'] ?? null);
        $data['due_date'] = $this->toDateString($data['due_date'] ?? null);

        $updated = Invoice::where('id', $invoice->id)->update($data);

        if ($updated === 0) {
            throw new \RuntimeException('Invoice update affected 0 rows.');
        }

        return $invoice->fresh();
    }

    /**
     * Check if invoice can be updated (status = pending)
     */
    public function canUpdate(Invoice $invoice): bool
    {
        return $invoice->status === 'pending';
    }

    private function toDateString(mixed $value): string
    {
        if ($value === null || $value === '') {
            throw new \InvalidArgumentException('Date value is required.');
        }
        $date = $value instanceof \DateTimeInterface
            ? $value
            : new \DateTimeImmutable((string) $value);
        return $date->format('Y-m-d');
    }
}