# Balance API

API для управления балансом пользователей на Laravel.

## Функционал
- Зачисление средств пользователю\
\
POST /api/deposit\
{ "user_id": 1,
"amount": 500.00,
  "comment": "Пополнение через карту"
}
  

- Cписание средств (баланс не может уйти в минус)\
\
  POST /api/withdraw\
  {
  "user_id": 1,
  "amount": 200.00,
  "comment": "Покупка подписки"
  }


- Перевод между пользователями\
\
  POST /api/transfer\
  {
  "from_user_id": 1,
  "to_user_id": 2,
  "amount": 150.00,
  "comment": "Перевод другу"
  }


- Получение текущего баланса\
\
  GET /api/balance/{user_id}\
  {
  "user_id": 1,
  "balance": 350.00
  }


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
