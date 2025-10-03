# Product Catalog API

REST API для каталога товаров с интеграцией Elasticsearch и DaData.

## Развертывание

### Автоматическая настройка
```bash
make setup
```
API будет доступно по адресу: http://localhost:8000

### Ручная настройка

1. Запустите сервисы:
```bash
make up
```

2. Установите зависимости:
```bash
docker compose -f compose.yaml run --rm cli composer install
```

3. Выполните миграции:
```bash
make migrate
```

4. Загрузите тестовые данные:
```bash
make seed
```

## Доступные команды

```bash
make help          # Показать все доступные команды
make up            # Запустить все сервисы
make down          # Остановить все сервисы
make logs          # Просмотр логов
make status        # Статус контейнеров
make migrate       # Выполнить миграции
make seed          # Загрузить тестовые данные
make shell         # Доступ к PHP контейнеру
make test          # Запустить все тесты
make test-unit     # Запустить только unit тесты
make test-integration # Запустить только integration тесты
make test-feature  # Запустить только feature тесты
make test-coverage # Запустить тесты с отчетом покрытия
make clean         # Очистить контейнеры и volumes
```

## API Endpoints

### Товары

#### Создание товара
```bash
curl -X POST http://localhost:8000/products \
  -H "Content-Type: application/json" \
  -d '{
    "name": "iPhone 15 Pro",
    "inn": "1234567890",
    "barcode": "1234567890123",
    "category_ids": [1, 2]
  }'
```

#### Получение товара по ID
```bash
curl http://localhost:8000/products/1
```

#### Обновление товара
```bash
curl -X PUT http://localhost:8000/products/1 \
  -H "Content-Type: application/json" \
  -d '{
    "name": "iPhone 15 Pro Max",
    "description": "Обновленное описание"
  }'
```

#### Удаление товара
```bash
curl -X DELETE http://localhost:8000/products/1
```

#### Поиск и фильтрация товаров
```bash
# Получить все товары
curl http://localhost:8000/products

# Поиск по названию
curl "http://localhost:8000/products?query=iPhone"

# Фильтр по категории
curl "http://localhost:8000/products?categoryId=1"

# Пагинация
curl "http://localhost:8000/products?limit=10&offset=0"

# Комбинированный поиск
curl "http://localhost:8000/products?query=smartphone&categoryId=1&limit=5&offset=0"
```

## Тестирование

### Запуск всех тестов
```bash
make test
```

### Запуск отдельных типов тестов
```bash
make test-unit        # Unit тесты
make test-integration # Integration тесты
make test-feature     # Feature тесты
```

### Тесты с покрытием кода
```bash
make test-coverage
```

### Структура тестов

- `tests/Unit/` - Unit тесты для отдельных классов
- `tests/Integration/` - Integration тесты с реальной базой данных
- `tests/Feature/` - Feature тесты для API endpoints

## Мониторинг и логи

### Просмотр логов
```bash
make logs
```

### Статус сервисов
```bash
make status
```