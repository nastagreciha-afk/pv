<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $net = $this->faker->randomFloat(2, 100, 10_000);
        $vat = $this->faker->randomFloat(2, 0, 0.2 * $net);
        $gross = $net + $vat;

        $issueDate = $this->faker->dateTimeBetween('-1 month', 'now');
        $dueDate = (clone $issueDate)->modify('+7 days');

        return [
            'number' => 'INV-' . $this->faker->unique()->numerify('2025-####'),
            'supplier_name' => $this->faker->company(),
            'supplier_tax_id' => $this->faker->numerify('##########'),
            'net_amount' => $net,
            'vat_amount' => $vat,
            'gross_amount' => $gross,
            'currency' => 'UAH',
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'issue_date' => $issueDate->format('Y-m-d'),
            'due_date' => $dueDate->format('Y-m-d'),
        ];
    }

    public function pending(): self
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function approved(): self
    {
        return $this->state(fn () => ['status' => 'approved']);
    }

    public function rejected(): self
    {
        return $this->state(fn () => ['status' => 'rejected']);
    }
}

