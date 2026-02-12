<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
            $invoices = Invoice::query()
                ->orderByDesc('created_at')
                ->paginate(20);

            return response()->json($invoices);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $this->validateBusinessRules($validated);

        $invoice = Invoice::create($validated);

        return response()->json($invoice, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $invoice = Invoice::findOrFail($id);

        return response()->json($invoice);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->status !== 'pending') {
            return response()->json([
                'message' => 'Only invoices with pending status can be updated.',
            ], 422);
        }

        $validated = $request->validate($this->rules($invoice->id));

        $this->validateBusinessRules($validated);

        $invoice->update($validated);

        return response()->json($invoice);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return response()->json(['message' => 'Not implemented'], 405);
    }

    /**
     * Validation rules for creating/updating invoices.
     */
    protected function rules(?int $ignoreId = null): array
    {
        return [
            'number' => [
                'required',
                'string',
                Rule::unique('invoices', 'number')->ignore($ignoreId),
            ],
            'supplier_name' => ['required', 'string', 'max:255'],
            'supplier_tax_id' => ['required', 'string', 'max:255'],
            'net_amount' => ['required', 'numeric', 'gt:0'],
            'vat_amount' => ['required', 'numeric', 'gte:0'],
            'gross_amount' => ['required', 'numeric'],
            'currency' => ['required', 'string', 'size:3'],
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
        ];
    }

    /**
     * Additional business validations for monetary fields.
     */
    protected function validateBusinessRules(array $validated): void
    {
        $expectedGross = round((float) $validated['net_amount'] + (float) $validated['vat_amount'], 2);
        $gross = round((float) $validated['gross_amount'], 2);

        if ($gross !== $expectedGross) {
            throw ValidationException::withMessages([
                'gross_amount' => ['Gross amount must equal net_amount + vat_amount.'],
            ]);
        }
    }
}
