# API сервиса управления заказами

Backend-сервис для интернет-магазина запчастей: управление заказами, каталог товаров, очереди экспорта.

**Стек:** PHP 8.4, Laravel 12, MySQL, Redis, Docker

---

## Быстрый старт (Docker)

### 1. Клонировать репозиторий

```bash
git clone <repo-url>
cd efremov.crm
```

### 2. Скопировать .env

```bash
cp .env.example .env
```

Убедитесь, что в `.env` установлены параметры для Docker:

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=crm
DB_USERNAME=crm
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PORT=6379
QUEUE_CONNECTION=redis
CACHE_STORE=redis
```

### 3. Собрать и запустить контейнеры

```bash
docker compose up -d --build
```

### 4. Установить зависимости и настроить приложение

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

### 5. Проверить работоспособность

API доступен по адресу: `http://localhost:8080/api/v1/`

### 6. Запуск очереди

Очередь работает автоматически через отдельный контейнер `queue`. Проверить логи:

```bash
docker compose logs -f queue
```

### 7. Запуск тестов

```bash
docker compose exec app php artisan test
```

---

## API Endpoints

| Метод | URL | Описание |
|-------|-----|----------|
| GET | `/api/v1/products` | Список товаров (фильтры: `category`, `search`, пагинация: `per_page`) |
| POST | `/api/v1/orders` | Создание заказа |
| GET | `/api/v1/orders` | Список заказов (фильтры: `status`, `customer_id`, `date_from`, `date_to`) |
| GET | `/api/v1/orders/{id}` | Детали заказа |
| PATCH | `/api/v1/orders/{id}/status` | Смена статуса заказа |

### Примеры запросов

**Создание заказа:**

```json
POST /api/v1/orders
{
    "customer_id": 1,
    "items": [
        {"product_id": 1, "quantity": 2},
        {"product_id": 3, "quantity": 1}
    ]
}
```

**Смена статуса:**

```json
PATCH /api/v1/orders/1/status
{
    "status": "confirmed"
}
```

---

## Архитектура

- **Service Layer** — бизнес-логика в `OrderService`, контроллеры тонкие
- **DTO** — `CreateOrderDTO` для передачи данных в сервис
- **Resource-классы** — единообразное форматирование ответов
- **Form Requests** — валидация входящих данных
- **Rate Limiting** — ограничение на создание заказов (10/мин по IP)
- **Queue Jobs** — `ExportOrderJob` при подтверждении заказа (Redis, 3 попытки)
