<?php

namespace App\Repositories;

use App\Models\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentInvoiceRepository implements InvoiceRepositoryInterface
{
    /**
     * Get all invoices with pagination
     */
    public function getAll(int $perPage = 20): LengthAwarePaginator
    {
        return Invoice::query()
            ->orderByDesc('created_at')
            ->paginate($perPage);
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
        $invoice->update($data);
        return $invoice->fresh();
    }

    /**
     * Check if invoice can be updated (status = pending)
     */
    public function canUpdate(Invoice $invoice): bool
    {
        return $invoice->status === 'pending';
    }
}