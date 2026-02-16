<?php

namespace App\Repositories;

use App\Models\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;

interface InvoiceRepositoryInterface
{
    /**
     * Get all invoices with pagination
     */
    public function getAll(int $perPage = 20, int $page = 1): LengthAwarePaginator;

    /**
     * Find invoice by ID
     */
    public function find(int $id): ?Invoice;

    /**
     * Create new invoice
     */
    public function create(array $data): Invoice;

    /**
     * Update existing invoice
     */
    public function update(Invoice $invoice, array $data): Invoice;

    /**
     * Check if invoice can be updated (status = pending)
     */
    public function canUpdate(Invoice $invoice): bool;
}