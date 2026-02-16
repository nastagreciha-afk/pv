<?php

namespace App\Services;

use App\Repositories\InvoiceRepositoryInterface;
use App\Validations\InvoiceBusinessValidator;
use App\Models\Invoice;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    protected InvoiceRepositoryInterface $repository;

    public function __construct(InvoiceRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all invoices
     */
    public function getAllInvoices(int $perPage = 20, int $page = 1): array
    {
        $invoices = $this->repository->getAll($perPage, $page);
        
        return [
            'data' => $invoices->items(),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
                'last_page' => $invoices->lastPage(),
            ],
            'links' => [
                'first' => $invoices->url(1),
                'last' => $invoices->url($invoices->lastPage()),
                'prev' => $invoices->previousPageUrl(),
                'next' => $invoices->nextPageUrl(),
            ],
        ];
    }

    /**
     * Get single invoice
     */
    public function getInvoice(int $id): Invoice
    {
        $invoice = $this->repository->find($id);
        
        if (!$invoice) {
            abort(404, 'Invoice not found');
        }
        
        return $invoice;
    }

    /**
     * Create new invoice
     */
    public function createInvoice(array $data): Invoice
    {
        // Validate business rules
        InvoiceBusinessValidator::validate($data);
        
        // Create invoice
        return $this->repository->create($data);
    }

    /**
     * Update existing invoice
     */
    public function updateInvoice(int $id, array $data): Invoice
    {
        $invoice = $this->getInvoice($id);
        
        // Check if invoice can be updated
        if (!$this->repository->canUpdate($invoice)) {
            throw ValidationException::withMessages([
                'status' => ['Only invoices with pending status can be updated.'],
            ]);
        }
        
        // Validate business rules
        InvoiceBusinessValidator::validate($data, $id);
        
        // Update invoice
        return $this->repository->update($invoice, $data);
    }
}