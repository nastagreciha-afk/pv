# Invoice Manager

Full-stack app: Nuxt 4 + Laravel API for managing invoices.

---

## 1. How are frontend and backend structured?

**Backend (Laravel 11, PHP 8.2+)**
- `app/Models/Invoice.php` – Eloquent model with required fields.
- `database/migrations/*create_invoices_table.php` – `invoices` table: `id`, `number`, `supplier_name`, `supplier_tax_id`, `net_amount`, `vat_amount`, `gross_amount`, `currency`, `status`, `issue_date`, `due_date`, `created_at`, `updated_at`.
- `app/Http/Controllers/Api/InvoiceController.php` – REST API: `GET /api/invoices` (list, `created_at desc`, pagination), `GET /api/invoices/{id}`, `POST /api/invoices`, `PUT /api/invoices/{id}` (only when `status = pending`).
- `routes/api.php` – JSON routes, no auth.

**Frontend (Nuxt 4, Vue 3.5, Tailwind)**
- `frontend/nuxt.config.ts` – Nuxt 4, Tailwind via `css` and `runtimeConfig.public.apiBase`.
- `frontend/pages/invoices/index.vue` – list (number, supplier_name, gross_amount, status, due_date), loading/error, row click → `/invoices/[id]`.
- `frontend/pages/invoices/[id]` – detail view and edit form; validation with zod.

---

## 2. What trade-offs were made?

- **Invoice ID** – `auto increment` instead of UUID for simpler migrations and queries.
- **Business logic** – validation and update rules live in `InvoiceService` and `InvoiceBusinessValidator`; controller stays thin.
- **Pagination** – Laravel pagination; frontend shows 10 per page with Previous/Next and page numbers.

---

## 3. What would you improve for production?

- Dedicated service layer and full unit/feature tests for validation and dates.
- Filters and sorting on the list; design system / shared UI components.
- Audit trail for invoice changes.

---

## 4. UX edge cases covered

- Loading and error states on list and detail.
- Edit form disabled when `status !== 'pending'` with clear message.
- `gross_amount` computed as `net_amount + vat_amount`; backend validates equality.
- `due_date` must be >= `issue_date` (zod + backend); dates shown in readable format (e.g. uk-UA).
- Save errors shown above the form without hiding it.

---

## Run with Docker

From project root:

```bash
cd /path/to/pv
docker-compose up --build
```

Backend runs migrations on startup. To stop: `Ctrl+C` then `docker-compose down`.

---

## Composer, migrations, and seeds

**Inside Docker (backend container running):**

```bash
docker-compose exec backend composer install
docker-compose exec backend php artisan migrate --force
docker-compose exec backend php artisan db:seed --force
```

Reset DB and run migrations + seeds:

```bash
docker-compose exec backend php artisan migrate:fresh --seed --force
```

**Local (no Docker):** PHP 8.2+ and MySQL required.

```bash
composer install
# Configure .env (DB_*)
php artisan migrate
php artisan db:seed
# Or: php artisan migrate:fresh --seed
```

---

## URLs after startup

| Service     | URL |
|------------|-----|
| **Frontend** | http://localhost:3000 |
| **Backend API** | http://localhost:8000/api/invoices |
| **MySQL**   | Host: `localhost`, port: **3307**, DB: `invoices`, user/pass: `invoices` |

Example: `curl http://localhost:8000/api/invoices`
