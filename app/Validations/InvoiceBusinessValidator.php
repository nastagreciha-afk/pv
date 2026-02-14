<?php

namespace App\Validations;

use Illuminate\Validation\ValidationException;

class InvoiceBusinessValidator
{
    /**
     * Validate business rules for invoice data
     */
    public static function validate(array $data, ?int $invoiceId = null): void
    {
        self::validateGrossAmount($data);
        self::validateStatusTransition($data, $invoiceId);
    }

    /**
     * Validate that gross_amount = net_amount + vat_amount
     */
    protected static function validateGrossAmount(array $data): void
    {
        $expectedGross = round((float) $data['net_amount'] + (float) $data['vat_amount'], 2);
        $gross = round((float) $data['gross_amount'], 2);

        if ($gross !== $expectedGross) {
            throw ValidationException::withMessages([
                'gross_amount' => ['Gross amount must equal net_amount + vat_amount.'],
            ]);
        }
    }

    /**
     * Validate status transition rules
     */
    protected static function validateStatusTransition(array $data, ?int $invoiceId): void
    {
        // Only validate transitions for existing invoices
        if ($invoiceId === null) {
            return;
        }

        // This would be implemented with actual status transition logic
        // For now, we'll just ensure pending status can be updated
        if (isset($data['status']) && $data['status'] !== 'pending') {
            // Additional transition rules would go here
        }
    }
}