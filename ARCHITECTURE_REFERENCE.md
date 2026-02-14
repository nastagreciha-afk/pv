# Refactored Invoice Architecture

## Folder Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── InvoiceController.php      # HTTP layer only
│   └── Requests/
│       └── Api/
│           ├── InvoiceStoreRequest.php    # Form validation
│           └── InvoiceUpdateRequest.php   # Form validation
├── Models/
│   └── Invoice.php                        # Data structure only
├── Repositories/
│   ├── InvoiceRepositoryInterface.php     # Interface
│   └── EloquentInvoiceRepository.php      # Implementation
├── Services/
│   └── InvoiceService.php                  # Business logic
├── Validations/
│   └── InvoiceBusinessValidator.php        # Business rules validation
└── Providers/
    └── RepositoryServiceProvider.php       # Dependency binding
```

## Key Components

### 1. Refactored Controller (HTTP Layer Only)

**File:** `app/Http/Controllers/Api/InvoiceController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\InvoiceStoreRequest;
use App\Http\Requests\Api\InvoiceUpdateRequest;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function index(): JsonResponse
    {
        $invoices = $this->invoiceService->getAllInvoices();
        return response()->json($invoices);
    }

    public function store(InvoiceStoreRequest $request): JsonResponse
    {
        $invoice = $this->invoiceService->createInvoice($request->validated());
        return response()->json($invoice, 201);
    }

    public function show(string $id): JsonResponse
    {
        $invoice = $this->invoiceService->getInvoice((int)$id);
        return response()->json($invoice);
    }

    public function update(InvoiceUpdateRequest $request, string $id): JsonResponse
    {
        $invoice = $this->invoiceService->updateInvoice((int)$id, $request->validated());
        return response()->json($invoice);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 405);
    }
}
```

### 2. Form Request Validation Classes

**File:** `app/Http/Requests/Api/InvoiceStoreRequest.php`

```php
<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'number' => ['required', 'string', Rule::unique('invoices', 'number')],
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
}
```

### 3. Business Validation Class

**File:** `app/Validations/InvoiceBusinessValidator.php`

```php
<?php

namespace App\Validations;

use Illuminate\Validation\ValidationException;

class InvoiceBusinessValidator
{
    public static function validate(array $data, ?int $invoiceId = null): void
    {
        self::validateGrossAmount($data);
        self::validateStatusTransition($data, $invoiceId);
    }

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

    protected static function validateStatusTransition(array $data, ?int $invoiceId): void
    {
        if ($invoiceId === null) {
            return;
        }

        if (isset($data['status']) && $data['status'] !== 'pending') {
            // Additional transition rules would go here
        }
    }
}
```

### 4. Repository Interface

**File:** `app/Repositories/InvoiceRepositoryInterface.php`

```php
<?php

namespace App\Repositories;

use App\Models\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;

interface InvoiceRepositoryInterface
{
    public function getAll(int $perPage = 20): LengthAwarePaginator;
    public function find(int $id): ?Invoice;
    public function create(array $data): Invoice;
    public function update(Invoice $invoice, array $data): Invoice;
    public function canUpdate(Invoice $invoice): bool;
}
```

### 5. Repository Implementation

**File:** `app/Repositories/EloquentInvoiceRepository.php`

```php
<?php

namespace App\Repositories;

use App\Models\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentInvoiceRepository implements InvoiceRepositoryInterface
{
    public function getAll(int $perPage = 20): LengthAwarePaginator
    {
        return Invoice::query()
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function find(int $id): ?Invoice
    {
        return Invoice::find($id);
    }

    public function create(array $data): Invoice
    {
        return Invoice::create($data);
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        $invoice->update($data);
        return $invoice->fresh();
    }

    public function canUpdate(Invoice $invoice): bool
    {
        return $invoice->status === 'pending';
    }
}
```

### 6. Invoice Service (Business Logic Layer)

**File:** `app/Services/InvoiceService.php`

```php
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

    public function getAllInvoices(int $perPage = 20): array
    {
        $invoices = $this->repository->getAll($perPage);
        
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

    public function getInvoice(int $id): Invoice
    {
        $invoice = $this->repository->find($id);
        
        if (!$invoice) {
            throw new \Exception('Invoice not found', 404);
        }
        
        return $invoice;
    }

    public function createInvoice(array $data): Invoice
    {
        InvoiceBusinessValidator::validate($data);
        return $this->repository->create($data);
    }

    public function updateInvoice(int $id, array $data): Invoice
    {
        $invoice = $this->getInvoice($id);
        
        if (!$this->repository->canUpdate($invoice)) {
            throw ValidationException::withMessages([
                'status' => ['Only invoices with pending status can be updated.'],
            ]);
        }
        
        InvoiceBusinessValidator::validate($data, $id);
        return $this->repository->update($invoice, $data);
    }
}
```

### 7. Service Provider (Dependency Binding)

**File:** `app/Providers/RepositoryServiceProvider.php`

```php
<?php

namespace App\Providers;

use App\Repositories\InvoiceRepositoryInterface;
use App\Repositories\EloquentInvoiceRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            InvoiceRepositoryInterface::class,
            EloquentInvoiceRepository::class
        );
    }
}
```

## Why This Structure is Better Than CRUD-Style Controller Logic

### 1. **Single Responsibility Principle**
- **Controller**: Only handles HTTP concerns (request/response, status codes)
- **Service**: Only handles business logic and workflows
- **Repository**: Only handles data access
- **Model**: Only represents data structure
- **Validation**: Separated into dedicated classes

### 2. **Testability**
- Services can be unit tested without HTTP layer
- Repositories can be mocked for service testing
- Business logic is isolated and testable
- No need for complex HTTP test setups to test business rules

### 3. **Maintainability**
- Changes to business logic don't require controller changes
- Database changes only affect repository layer
- Validation rules can be modified independently
- Clear separation makes code easier to understand

### 4. **Flexibility**
- Can switch database systems by implementing new repository
- Can reuse service logic across different controllers/API versions
- Easy to add new features without breaking existing code
- Business rules can be extended without touching HTTP layer

### 5. **Reusability**
- Same service can be used by multiple controllers
- Business logic can be called from CLI commands, queues, etc.
- Validation rules can be reused across different contexts
- Repository can be used by different services

### 6. **Scalability**
- Easy to add caching layer in repository
- Simple to implement complex business workflows in service
- Can add logging, metrics, or other cross-cutting concerns
- Supports team parallel development (different layers)

### 7. **Dependency Management**
- Service depends on interface, not concrete implementation
- Easy to swap implementations (e.g., Eloquent → Doctrine)
- Clear dependency injection makes testing easier
- Follows SOLID principles

### 8. **Error Handling**
- Business errors handled at appropriate layer
- HTTP errors handled at controller level
- Validation errors separated from business errors
- Clear error propagation through layers

## Real-World Benefits

1. **When requirements change**: Only modify the relevant layer
2. **When adding new features**: Clear place to add new functionality
3. **When fixing bugs**: Isolate the issue to specific layer
4. **When performance tuning**: Optimize repository without affecting business logic
5. **When adding monitoring**: Add logging at service layer without cluttering controllers

This architecture provides a clean, maintainable foundation that can grow with the application while keeping technical debt low.