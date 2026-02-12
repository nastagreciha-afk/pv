## Invoice Management Demo (Laravel + Nuxt)

Мінімальний, але реалістичний full‑stack модуль для роботи з інвойсами (рахунками) для бухгалтерів.

### 1. Як структуровано frontend і backend?

- **Backend (Laravel 11, PHP 8.2+)**
  - `app/Models/Invoice.php` – Eloquent‑модель з усіма потрібними полями.
  - `database/migrations/*create_invoices_table.php` – схема таблиці `invoices` з полями:
    `id`, `number`, `supplier_name`, `supplier_tax_id`, `net_amount`, `vat_amount`,
    `gross_amount`, `currency`, `status`, `issue_date`, `due_date`, `created_at`, `updated_at`.
  - `app/Http/Controllers/Api/InvoiceController.php` – REST API:
    - `GET /api/invoices` – список (сортування `created_at desc`, pagination).
    - `GET /api/invoices/{id}` – один інвойс.
    - `POST /api/invoices` – створення з валідацією.
    - `PUT /api/invoices/{id}` – оновлення тільки для `status = pending`.
  - `routes/api.php` – JSON‑маршрути без аутентифікації.

- **Frontend (Nuxt 4, Vue 3.5, TailwindCSS 4)**
  - `frontend/nuxt.config.ts` – Nuxt 4, підключення Tailwind (`css`) та `runtimeConfig.public.apiBase`.
  - `frontend/assets/css/tailwind.css` + `frontend/tailwind.config.js` – TailwindCSS 4.
  - `frontend/pages/index.vue` – простий дашборд з переходом на список.
  - `frontend/pages/invoices/index.vue` – список інвойсів:
    відображає `number`, `supplier_name`, `gross_amount`, `status`, `due_date`,
    має `loading/error` стани, клікабельний рядок веде на `/invoices/[id]`.
  - `frontend/pages/invoices/[id].vue` – деталі інвойсу:
    повна інформація, статус‑бейдж, форматовані дати + форма редагування.
  - Валідація на фронтенді: `vee-validate` + `zod` для форми редагування.

### 2. Які компроміси зроблені і чому?

- **ID інвойсу** – використано `auto increment` (`$table->id()`), а не UUID, щоб спростити міграції
  та SQL‑запити в демонстраційному проєкті.
- **Бізнес‑логіка в контролері** – частина валідації (`gross_amount = net + vat`) розміщена
  без окремого сервіс‑класу, щоб зменшити кількість файлів і зробити приклад компактним.
- **TailwindCSS 4 (beta)** – використано актуальний beta‑реліз Tailwind 4,
  щоб відповідати вимозі стеку, але конфігурація залишена максимально простою.
- **Пагінація** – використано стандартну Laravel‑пагінацію без окремого фронтенд‑компонента
  (фронт зараз працює з першою сторінкою), бо задачі вистачає базового списку.

### 3. Що б я покращив у production‑версії?

- Виніс би бізнес‑логіку інвойсів в окремий сервіс (наприклад, `app/Services/InvoiceService.php`)
  та додав unit/feature‑тести на всі кейси валідації сум і дат.
- Додав би фільтри й сортування на фронтенді (по даті, статусу, постачальнику) та нормальну
  пагінацію зі сторінками.
- Зробив би централізований UI‑кит (кнопки, таблиці, бейджі) і впровадив дизайн‑систему.
- Додав би логування дій користувача (audit trail) для змін інвойсів.

### 4. Які UX edge cases враховано?

- **Loading/error стани**:
  - Список інвойсів і деталі показують окремі стани "Loading…" і помилки завантаження.
- **Недоступність редагування**:
  - Форма редагування повністю заблокована, якщо `status != pending`,
    з явним текстовим поясненням.
- **Консистентність сум**:
  - `gross_amount` на фронті завжди автоматично перераховується як
    `net_amount + vat_amount`, користувач не може змінити його вручну.
  - Бекенд додатково перевіряє, що `gross_amount = net + vat` (запобігання маніпуляціям).
- **Дати**:
  - Форма не дозволяє зберегти `due_date` раніше `issue_date` (перевірка в zod + бекенді).
  - Дати й `last updated` відображаються у читабельному форматі `uk-UA`.
- **М'яке повідомлення про помилки**:
  - На сторінці деталей інвойсу є toast‑повідомлення з результатом оновлення
    (успіх/помилка бекенду).

### Запуск у Docker

1. Переконайтеся, що Docker і docker-compose встановлені.
2. З кореня проєкту виконайте:

```bash
docker-compose up --build
```

- Backend (Laravel API): `http://localhost:8000`, REST‑ендпоінти під `/api/invoices`.
- MySQL: порт хоста `3307`, база `invoices`.
- Frontend (Nuxt 4): `http://localhost:3000`, працює з API через `API_BASE_URL` (`backend:8000/api` всередині мережі Docker).

