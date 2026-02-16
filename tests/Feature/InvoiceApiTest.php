<?php

namespace Tests\Feature;

use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_invoices_returns_paginated_collection(): void
    {
        Invoice::factory()->count(3)->create();

        $response = $this->getJson('/api/invoices');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_show_single_invoice(): void
    {
        $invoice = Invoice::factory()->create();

        $response = $this->getJson("/api/invoices/{$invoice->id}");

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $invoice->id,
                'number' => $invoice->number,
            ]);
    }

    public function test_create_invoice_with_valid_data(): void
    {
        $payload = [
            'number' => 'INV-2025-0001',
            'supplier_name' => 'ТОВ "Альфа Консалтинг"',
            'supplier_tax_id' => '1234567890',
            'net_amount' => 10_000.00,
            'vat_amount' => 2_000.00,
            'gross_amount' => 12_000.00,
            'currency' => 'UAH',
            'status' => 'pending',
            'issue_date' => '2025-02-10',
            'due_date' => '2025-02-20',
        ];

        $response = $this->postJson('/api/invoices', $payload);

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'number' => 'INV-2025-0001',
                'gross_amount' => 12_000.00,
            ]);

        $this->assertDatabaseHas('invoices', [
            'number' => 'INV-2025-0001',
            'gross_amount' => 12_000.00,
        ]);
    }

    public function test_create_invoice_fails_when_gross_not_equal_net_plus_vat(): void
    {
        $payload = [
            'number' => 'INV-2025-0002',
            'supplier_name' => 'ТОВ "Бета"',
            'supplier_tax_id' => '9876543210',
            'net_amount' => 10_000.00,
            'vat_amount' => 2_000.00,
            'gross_amount' => 13_000.00, // wrong amount (must equal net + vat)
            'currency' => 'UAH',
            'status' => 'pending',
            'issue_date' => '2025-02-10',
            'due_date' => '2025-02-20',
        ];

        $response = $this->postJson('/api/invoices', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['gross_amount']);
    }

    public function test_create_invoice_fails_when_due_date_before_issue_date(): void
    {
        $payload = [
            'number' => 'INV-2025-0003',
            'supplier_name' => 'ТОВ "Гамма"',
            'supplier_tax_id' => '1111111111',
            'net_amount' => 10_000.00,
            'vat_amount' => 2_000.00,
            'gross_amount' => 12_000.00,
            'currency' => 'UAH',
            'status' => 'pending',
            'issue_date' => '2025-02-10',
            'due_date' => '2025-02-01', // before issue_date
        ];

        $response = $this->postJson('/api/invoices', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);
    }

    public function test_update_invoice_when_pending(): void
    {
        $invoice = Invoice::factory()->pending()->create([
            'net_amount' => 10_000.00,
            'vat_amount' => 2_000.00,
            'gross_amount' => 12_000.00,
        ]);

        $payload = [
            'number' => $invoice->number,
            'supplier_name' => $invoice->supplier_name,
            'supplier_tax_id' => $invoice->supplier_tax_id,
            'net_amount' => 11_000.00,
            'vat_amount' => 2_200.00,
            'gross_amount' => 13_200.00,
            'currency' => $invoice->currency,
            'status' => 'pending',
            'issue_date' => $invoice->issue_date,
            'due_date' => $invoice->due_date,
        ];

        $response = $this->putJson("/api/invoices/{$invoice->id}", $payload);

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $invoice->id,
                'gross_amount' => 13_200.00,
            ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'gross_amount' => 13_200.00,
        ]);
    }

    public function test_update_invoice_fails_when_not_pending(): void
    {
        $invoice = Invoice::factory()->approved()->create();

        $payload = [
            'number' => $invoice->number,
            'supplier_name' => $invoice->supplier_name,
            'supplier_tax_id' => $invoice->supplier_tax_id,
            'net_amount' => $invoice->net_amount,
            'vat_amount' => $invoice->vat_amount,
            'gross_amount' => $invoice->gross_amount,
            'currency' => $invoice->currency,
            'status' => 'approved',
            'issue_date' => $invoice->issue_date,
            'due_date' => $invoice->due_date,
        ];

        $response = $this->putJson("/api/invoices/{$invoice->id}", $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status'])
            ->assertJsonPath('errors.status.0', 'Only invoices with pending status can be updated.');
    }

    public function test_number_must_be_unique(): void
    {
        $invoice = Invoice::factory()->create([
            'number' => 'INV-UNIQUE-001',
        ]);

        $payload = [
            'number' => $invoice->number,
            'supplier_name' => 'Test supplier',
            'supplier_tax_id' => '2222222222',
            'net_amount' => 100.00,
            'vat_amount' => 20.00,
            'gross_amount' => 120.00,
            'currency' => 'UAH',
            'status' => 'pending',
            'issue_date' => '2025-02-10',
            'due_date' => '2025-02-20',
        ];

        $response = $this->postJson('/api/invoices', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['number']);
    }
}

