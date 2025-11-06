# Balance API

API для управления балансом пользователей на Laravel.

## Функционал
- POST /api/deposit — зачисление средств
- POST /api/withdraw — списание средств (баланс не может уйти в минус)
- POST /api/transfer — перевод между пользователями
- GET /api/balance/{user_id} — получение текущего баланса

## Технологии
- PHP 8.2
- Laravel 10
- PostgreSQL 15
- Docker & Docker Compose
- PHPUnit

Все операции выполняются в транзакциях.\
Поддержка PostgreSQL.\
Все ответы — в формате JSON с корректными HTTP-кодами.

## Защита API

Все эндпоинты защищены API-токеном.
Передайте заголовок:
`X-API-TOKEN: secret_token_12345`
Токен задаётся в `.env` → `API_TOKEN`.

## Запуск

```bash
git clone https://github.com/ваш-юзернейм/balance-api.git
cd balance-api
composer install
cp .env.example .env
php artisan key:generate
docker-compose up -d
docker-compose exec app php artisan migrate
```
Приложение будет доступно по: http://localhost:8000
git 
## Пример запроса

```
URL: POST http://localhost:8000/api/deposit

Headers:
Content-Type: application/json
Accept: application/json
X-API-TOKEN: secret_token_12345

Body (raw JSON):
{
  "user_id": 1,
  "amount": 500.00,
  "comment": "Пополнение"
}
```

## Тесты
```docker-compose exec app php artisan test```
