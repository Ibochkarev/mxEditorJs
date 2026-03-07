# Справочник API mxEditorJs

## Содержание

1. [Connector API (HTTP)](#connector-api-http)
2. [PHP-классы](#php-классы)
3. [JavaScript API](#javascript-api)
4. [Форматы данных](#форматы-данных)

---

## Connector API (HTTP)

Все запросы выполняются к `assets/components/mxeditorjs/connector.php`. Формат ответа — JSON. Требуется авторизация в менеджере MODX.

### Аутентификация

Все эндпоинты требуют активной сессии менеджера MODX. Неавторизованные запросы возвращают:

```json
{ "success": false, "message": "Permission denied" }
```

Операции записи (save, upload, migrate) дополнительно проверяют право `save_document`.

---

### content/get

Получить JSON-контент ресурса или TV.

| Параметр | Тип | Обязательный | Описание |
|----------|-----|:---:|----------|
| `action` | string | ✓ | `content/get` |
| `resource_id` | int | ✓ | ID ресурса MODX |
| `tmplvar_id` | int | — | ID TV (если не указан — основной контент) |

**Ответ (контент найден):**
```json
{
  "success": true,
  "data": {
    "content_json": { "time": 1709827200000, "blocks": [...], "version": "2.31.0" },
    "content_version": 3
  }
}
```

**Ответ (контент не найден):**
```json
{ "success": true, "data": null }
```

---

### content/save

Сохранить JSON-контент с валидацией и генерацией HTML-снимка.

| Параметр | Тип | Обязательный | Описание |
|----------|-----|:---:|----------|
| `action` | string | ✓ | `content/save` |
| `resource_id` | int | ✓ | ID ресурса |
| `tmplvar_id` | int | — | ID TV (если не указан — основной контент) |
| `content_json` | string/object | ✓ | Editor.js OutputData |

**Ответ (успех):**
```json
{
  "success": true,
  "data": { "html": "<h2>Заголовок</h2>\n<p>Текст</p>" }
}
```

**Ответ (ошибка валидации):**
```json
{
  "success": false,
  "message": "Validation failed: Block type 'unknown' at index 2 is not allowed"
}
```

**Логика сохранения:**
1. JSON декодируется и валидируется (`ContentValidator`)
2. `HtmlRenderer` генерирует HTML
3. Для основного контента: JSON сохраняется в sidecar + HTML записывается в `modResource.content`
4. Для TV: JSON сохраняется в sidecar `mxeditorjs_tv_content`

---

### media/upload

Загрузить изображение.

| Параметр | Тип | Обязательный | Описание |
|----------|-----|:---:|----------|
| `action` | string | ✓ | `media/upload` |
| `resource_id` | int | ✓ | ID ресурса |
| `image` | file | ✓ | Файл изображения (multipart/form-data) |

**Ответ (успех):**
```json
{
  "success": 1,
  "file": {
    "url": "/assets/images/resources/42/photo.jpg",
    "name": "photo.jpg",
    "size": 245760
  }
}
```

**Валидация:**
- Расширение файла входит в `mxeditorjs.allowed_image_types`
- MIME-тип: image/jpeg, image/png, image/gif, image/webp, image/svg+xml
- Размер ≤ `mxeditorjs.max_upload_size`

---

### media/uploadFile

Загрузить файл-вложение (для инструмента Attaches).

| Параметр | Тип | Обязательный | Описание |
|----------|-----|:---:|----------|
| `action` | string | ✓ | `media/uploadFile` |
| `resource_id` | int | ✓ | ID ресурса |
| `file` | file | ✓ | Файл (multipart/form-data) |

**Ответ:** идентичен `media/upload`.

**Допустимые расширения:** pdf, doc, docx, xls, xlsx, ppt, pptx, txt, csv, zip, rar, 7z, jpg, jpeg, png, gif, webp, svg.

---

### media/browse

Просмотреть файлы в Media Source.

| Параметр | Тип | Обязательный | Описание |
|----------|-----|:---:|----------|
| `action` | string | ✓ | `media/browse` |
| `resource_id` | int | ✓ | ID ресурса |
| `type` | string | — | `image` (по умолчанию) или `file` |
| `path` | string | — | Путь относительно корня Media Source. `__root__` или `/` — корень. |

**Ответ:**
```json
{
  "success": true,
  "data": {
    "files": [
      {
        "name": "photo.jpg",
        "url": "/assets/images/resources/42/photo.jpg",
        "size": 245760,
        "modified": 1709827200,
        "type": "file",
        "extension": "jpg",
        "isImage": true
      }
    ],
    "folders": [
      { "name": "thumbnails", "path": "images/resources/42/thumbnails", "type": "folder" }
    ],
    "path": "images/resources/42",
    "parentPath": "images/resources"
  }
}
```

---

### link/search

Поиск ресурсов MODX для автодополнения ссылок.

| Параметр | Тип | Обязательный | Описание |
|----------|-----|:---:|----------|
| `action` | string | ✓ | `link/search` |
| `query` | string | ✓ | Поисковый запрос (минимум 2 символа) |
| `limit` | int | — | Максимум результатов (по умолчанию 10, макс. 30) |

**Ответ:**
```json
{
  "success": true,
  "data": [
    {
      "id": 42,
      "pagetitle": "About Us",
      "longtitle": "About Our Company",
      "uri": "about/",
      "published": true,
      "context_key": "web",
      "url": "https://example.com/about/"
    }
  ]
}
```

Поиск выполняется по полям: `pagetitle` (LIKE), `longtitle` (LIKE), `id` (точное совпадение для числовых запросов). Удалённые ресурсы исключаются.

---

### content/migrate

Миграция HTML-контента ресурса в формат Editor.js.

| Параметр | Тип | Обязательный | Описание |
|----------|-----|:---:|----------|
| `action` | string | ✓ | `content/migrate` |
| `resource_id` | int | ✓ | ID ресурса |
| `dry_run` | bool | — | Предпросмотр без сохранения |
| `confirmed` | bool | — | Подтверждение перезаписи |
| `force` | bool | — | Принудительная перезапись существующих данных |

**Ответ (dry_run):**
```json
{
  "success": true,
  "data": {
    "dry_run": true,
    "preview": { "time": ..., "blocks": [...], "version": "2.31.0" },
    "blocks_count": 12,
    "html_length": 3456,
    "has_existing": false
  }
}
```

**Ответ (требуется подтверждение):**
```json
{
  "success": true,
  "data": {
    "requires_confirmation": true,
    "preview": { ... },
    "blocks_count": 12,
    "message": "Migration will overwrite existing content. Send confirmed=true to proceed."
  }
}
```

**Ответ (миграция выполнена):**
```json
{
  "success": true,
  "data": { "migrated": true, "blocks_count": 12, "overwritten": false }
}
```

---

## PHP-классы

### MxEditorJs\Renderer\HtmlRenderer

```php
namespace MxEditorJs\Renderer;

class HtmlRenderer
{
    public function __construct();
    public function render(array $editorJsData): string;
    public function registerBlockRenderer(string $type, callable $renderer): void;
}
```

| Метод | Параметры | Возвращает | Описание |
|-------|-----------|-----------|----------|
| `render` | `array $editorJsData` — Editor.js OutputData | `string` HTML | Рендерит все блоки в HTML-строку |
| `registerBlockRenderer` | `string $type`, `callable $renderer` | `void` | Регистрирует кастомный рендерер для типа блока |

Сигнатура callable: `function(array $data, array $block): string`

---

### MxEditorJs\Validator\ContentValidator

```php
namespace MxEditorJs\Validator;

class ContentValidator
{
    public function validate(array $data): bool;
    public function getErrors(): array;
    public function getFirstError(): ?string;
}
```

| Метод | Описание |
|-------|----------|
| `validate` | Проверяет структуру. Возвращает `true` если валидно. |
| `getErrors` | Массив строк с описаниями всех ошибок. |
| `getFirstError` | Первая ошибка или `null`. |

---

### MxEditorJs\Repository\ContentRepository

```php
namespace MxEditorJs\Repository;

class ContentRepository
{
    public function __construct(\MODX\Revolution\modX $modx);
    public function findByResourceId(int $resourceId): ?array;
    public function save(int $resourceId, array $jsonData, int $userId = 0): bool;
    public function deleteByResourceId(int $resourceId): bool;
}
```

| Метод | Описание |
|-------|----------|
| `findByResourceId` | Возвращает массив записи или `null` |
| `save` | Создаёт или обновляет запись. Пропускает, если хеш не изменился. |
| `deleteByResourceId` | Удаляет запись. Возвращает `true` если запись отсутствовала или успешно удалена. |

---

### MxEditorJs\Repository\TvContentRepository

```php
namespace MxEditorJs\Repository;

class TvContentRepository
{
    public function __construct(\MODX\Revolution\modX $modx);
    public function findByResourceAndTv(int $resourceId, int $tmplvarId): ?array;
    public function save(int $resourceId, int $tmplvarId, array $jsonData, int $userId = 0): bool;
    public function deleteByResourceAndTv(int $resourceId, int $tmplvarId): bool;
    public function deleteByResourceId(int $resourceId): bool;
}
```

| Метод | Описание |
|-------|----------|
| `findByResourceAndTv` | Поиск по составному ключу (resource_id, tmplvar_id) |
| `save` | Создание/обновление с дедупликацией по хешу |
| `deleteByResourceAndTv` | Удаление конкретной TV-записи |
| `deleteByResourceId` | Удаление всех TV-записей ресурса |

---

### MxEditorJs\Service\MediaUploader

```php
namespace MxEditorJs\Service;

class MediaUploader
{
    public function __construct(\MODX\Revolution\modX $modx);
    public function upload(array $file, int $resourceId): array;
    public function uploadFile(array $file, int $resourceId): array;
    public function browse(int $resourceId, string $type = 'image', string $subPath = ''): array;
}
```

| Метод | Описание |
|-------|----------|
| `upload` | Загрузка изображения в Media Source. Бросает `RuntimeException` при ошибке. |
| `uploadFile` | Загрузка файла-вложения. |
| `browse` | Возвращает массив `{files, folders, path, parentPath}` для указанного пути. |

---

### MxEditorJs\Service\HtmlMigrator

```php
namespace MxEditorJs\Service;

class HtmlMigrator
{
    public function convert(string $html): array;
}
```

| Метод | Описание |
|-------|----------|
| `convert` | Принимает HTML-строку, возвращает Editor.js OutputData `{time, blocks, version}` |

**Поддерживаемые HTML-элементы:**

| HTML | Тип блока Editor.js |
|------|---------------------|
| `<p>` | paragraph |
| `<h1>`–`<h6>` | header (level 1–6) |
| `<ul>`, `<ol>` | list (unordered/ordered) |
| `<blockquote>` | quote |
| `<hr>` | delimiter |
| `<pre>`, `<code>` | code |
| `<figure><img>` | image |
| `<img>` | image |
| `<table>` | table |
| `<div>`, `<section>`, `<article>` | paragraph (fallback) |
| Текстовые ноды | paragraph |

---

## JavaScript API

### window.mxEditorJsConfig

Объект конфигурации, доступный после `OnDocFormPrerender`:

```typescript
interface MxEditorJsConfig {
    connectorUrl: string;       // URL коннектора
    resourceId: number;         // ID текущего ресурса
    assetsUrl: string;          // URL директории ассетов
    profile: string;            // Имя профиля
    enabledTools: string[];     // Массив включённых инструментов
    presets: {
        imageClass: Record<string, string>;
        linkClass: Record<string, string>;
        linkTarget: Record<string, string>;
        linkRel: Record<string, string>;
    };
    locale: string;             // Код языка (en, ru, ...)
    i18n: Record<string, string>;          // Переводы UI
    editorJsI18n: { messages: object };    // Переводы Editor.js
}
```

### MODx.loadRTE / MODx.unloadRTE

mxEditorJs перехватывает стандартные хуки MODX для инициализации RTE:

```javascript
// Вызывается MODX при появлении textarea
window.MODx.loadRTE(textareaId);

// Вызывается при удалении textarea
window.MODx.unloadRTE(textareaId);
```

---

## Форматы данных

### Editor.js OutputData

```json
{
  "time": 1709827200000,
  "version": "2.31.0",
  "blocks": [
    {
      "id": "abc123",
      "type": "paragraph",
      "data": { "text": "Hello world" },
      "tunes": {
        "alignmentTune": { "alignment": "left" }
      }
    }
  ]
}
```

### Структура блока Image

```json
{
  "type": "image",
  "data": {
    "file": { "url": "/assets/images/photo.jpg" },
    "caption": "Подпись",
    "withBorder": false,
    "stretched": false,
    "withBackground": false
  }
}
```

### Структура блока Embed

```json
{
  "type": "embed",
  "data": {
    "service": "youtube",
    "source": "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
    "embed": "https://www.youtube.com/embed/dQw4w9WgXcQ",
    "width": 580,
    "height": 320,
    "caption": ""
  }
}
```

### Ответ API: успех

```json
{ "success": true, "data": { ... } }
```

### Ответ API: ошибка

```json
{ "success": false, "message": "Error description" }
```
