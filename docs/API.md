# Справочник API mxEditorJs

## Содержание

1. [Connector API (HTTP)](#connector-api-http)
2. [PHP-классы](#php-классы)
3. [JavaScript API](#javascript-api)
4. [Форматы данных](#форматы-данных)

---

## Connector API (HTTP)

Базовый URL: `assets/components/mxeditorjs/connector.php`  
Контекст: `mgr`  
Content-Type ответа: `application/json; charset=utf-8`

### Аутентификация

Все запросы требуют активной сессии менеджера MODX. Без авторизации:

```json
{ "success": false, "message": "Доступ запрещён." }
```

HTTP-код остаётся **200**, не 403.

Операции записи (`content/save`, `media/upload`, `media/uploadFile`, `content/migrate` без `dry_run`) дополнительно проверяют право `save_document`.

`media/browse` и `link/search` требуют только авторизации, без `save_document`.

---

### content/get

Получить JSON из sidecar.

| Параметр | Тип | Обяз. | Описание |
|----------|-----|:---:|----------|
| `action` | string | ✓ | `content/get` |
| `resource_id` | int | ✓ | ID ресурса |
| `tmplvar_id` | int | — | ID TV (без параметра — основной контент) |

**Найдено:**
```json
{
  "success": true,
  "data": {
    "content_json": { "time": 1709827200000, "blocks": [], "version": "2.31.0" },
    "content_version": 3
  }
}
```

**Не найдено:**
```json
{ "success": true, "data": null }
```

---

### content/save

Сохранить JSON с валидацией и серверным HTML-рендером. Используется для API и интеграций. Фронтенд менеджера при обычном сохранении ресурса **не вызывает** этот action.

| Параметр | Тип | Обяз. | Описание |
|----------|-----|:---:|----------|
| `action` | string | ✓ | `content/save` |
| `resource_id` | int | ✓ | ID ресурса |
| `tmplvar_id` | int | — | ID TV |
| `content_json` | string/object | ✓ | Editor.js OutputData |

**Успех:**
```json
{ "success": true, "data": { "html": "<h2>...</h2>" } }
```

**Ошибка валидации:**
```json
{ "success": false, "message": "Validation failed: Block type 'unknown' at index 2 is not allowed" }
```

Логика: validate → HtmlRenderer → sidecar → для основного контента также `modResource.content`.

---

### media/upload

Загрузка изображения (Image, Gallery).

| Параметр | Тип | Обяз. | Описание |
|----------|-----|:---:|----------|
| `action` | string | ✓ | `media/upload` |
| `resource_id` | int | ✓ | ID ресурса |
| `image` | file | ✓ | multipart/form-data |

**Успех:**
```json
{
  "success": 1,
  "file": { "url": "/assets/images/resources/42/photo.jpg", "name": "photo.jpg", "size": 245760 }
}
```

**Ошибка:** `{ "success": 0, "message": "..." }`

Валидация: расширение из `mxeditorjs.allowed_image_types`, MIME image/*, размер ≤ `mxeditorjs.max_upload_size`.

---

### media/uploadFile

Загрузка файла для Attaches.

| Параметр | Тип | Обяз. | Описание |
|----------|-----|:---:|----------|
| `action` | string | ✓ | `media/uploadFile` |
| `resource_id` | int | ✓ | ID ресурса |
| `file` | file | ✓ | multipart/form-data |

Ответ как у `media/upload`. Расширения: pdf, doc, docx, xls, xlsx, ppt, pptx, txt, csv, zip, rar, 7z и изображения.

---

### media/browse

Просмотр директории Media Source.

| Параметр | Тип | Обяз. | Описание |
|----------|-----|:---:|----------|
| `action` | string | ✓ | `media/browse` |
| `resource_id` | int | ✓ | ID ресурса |
| `type` | string | — | `image` (default) или `file` |
| `path` | string | — | Путь относительно корня MS. `__root__` или `/` = корень |

**Ответ:**
```json
{
  "success": true,
  "data": {
    "files": [{ "name": "photo.jpg", "url": "...", "size": 245760, "isImage": true, "extension": "jpg" }],
    "folders": [{ "name": "thumbnails", "path": "...", "type": "folder" }],
    "path": "images/resources/42",
    "parentPath": "images/resources"
  }
}
```

---

### link/search

Поиск ресурсов для LinkAutocomplete.

| Параметр | Тип | Обяз. | Описание |
|----------|-----|:---:|----------|
| `action` | string | ✓ | `link/search` |
| `query` | string | ✓ | Минимум 2 символа |
| `limit` | int | — | Default 10, max 30 |

**Ответ:**
```json
{
  "success": true,
  "data": [
    { "id": 42, "pagetitle": "About", "longtitle": "", "uri": "about/", "published": true, "context_key": "web", "url": "https://example.com/about/" }
  ]
}
```

Поиск: `pagetitle` LIKE, `longtitle` LIKE, `id` exact для числового query. Удалённые ресурсы исключены.

---

### content/migrate

Конвертация HTML основного контента ресурса в Editor.js. TV не поддерживается.

| Параметр | Тип | Обяз. | Описание |
|----------|-----|:---:|----------|
| `action` | string | ✓ | `content/migrate` |
| `resource_id` | int | ✓ | ID ресурса |
| `dry_run` | bool | — | Превью без записи |
| `confirmed` | bool | — | Подтверждение |
| `force` | bool | — | Перезапись существующего sidecar |

**dry_run:**
```json
{
  "success": true,
  "data": {
    "dry_run": true,
    "preview": { "time": 0, "blocks": [], "version": "2.31.0" },
    "blocks_count": 12,
    "html_length": 3456,
    "has_existing": false
  }
}
```

**Sidecar уже существует:**
```json
{ "success": true, "data": { "skipped": true, "reason": "sidecar_exists", "requires_confirmation": true } }
```

**Пустой HTML:**
```json
{ "success": true, "data": { "skipped": true, "reason": "empty_content" } }
```

**Выполнено:**
```json
{ "success": true, "data": { "migrated": true, "blocks_count": 12, "overwritten": false, "html": "<h2>...</h2>" } }
```

После успешной миграции JSON пишется в sidecar, HTML-снимок — в `modResource.content` (тот же путь, что у `content/save`).

---

### content/fromHtml

Конвертация HTML в Editor.js OutputData без записи в БД. Нужна авторизация менеджера. Право `save_document` не требуется.

| Параметр | Тип | Обяз. | Описание |
|----------|-----|:---:|----------|
| `action` | string | ✓ | `content/fromHtml` |
| `html` | string | ✓ | Исходный HTML |

**Успех:**
```json
{ "success": true, "data": { "time": 1709827200000, "blocks": [], "version": "2.31.0" } }
```

Используется для полей MIGX: значение хранится как HTML в JSON-строке TV, sidecar для строки MIGX нет.

---

## PHP-классы

### MxEditorJs\Config\EditorTools

```php
namespace MxEditorJs\Config;

class EditorTools
{
    public const DEFAULT_AVAILABLE = 'paragraph,header,...';
    public const PACKAGE_PROFILES = [ 'default' => [...], ... ];

    public static function parseList(string $csv): array;
    public static function resolve(modX $modx, string $profileName, array $storedProfiles): array;
    public static function migrateProfiles(array $storedProfiles): array;
    public static function migrateAvailableTools(string $availableCsv): string;
}
```

### MxEditorJs\Renderer\HtmlRenderer

```php
public function render(array $editorJsData): string;
public function registerBlockRenderer(string $type, callable $renderer): void;
```

Callable: `function (array $data, array $block): string`

### MxEditorJs\Validator\ContentValidator

```php
public function validate(array $data): bool;
public function getErrors(): array;
public function getFirstError(): ?string;
```

### MxEditorJs\Repository\ContentRepository

```php
public function findByResourceId(int $resourceId): ?array;
public function save(int $resourceId, array $jsonData, int $userId = 0): bool;
public function deleteByResourceId(int $resourceId): bool;
```

### MxEditorJs\Repository\TvContentRepository

```php
public function findByResourceAndTv(int $resourceId, int $tmplvarId): ?array;
public function save(int $resourceId, int $tmplvarId, array $jsonData, int $userId = 0): bool;
public function deleteByResourceAndTv(int $resourceId, int $tmplvarId): bool;
public function deleteByResourceId(int $resourceId): bool;
```

### MxEditorJs\Service\MediaUploader

```php
public function upload(array $file, int $resourceId): array;
public function uploadFile(array $file, int $resourceId): array;
public function browse(int $resourceId, string $type = 'image', string $subPath = ''): array;
```

### MxEditorJs\Service\HtmlMigrator

```php
public function convert(string $html): array;
```

| HTML | Block |
|------|-------|
| h1–h6 | header |
| p | paragraph |
| ul, ol | list |
| blockquote | quote |
| hr | delimiter |
| pre, code | code |
| figure/img, img | image |
| table | table |
| div, section, article | paragraph (inner HTML) |

---

## JavaScript API

### window.mxEditorJsConfig

```typescript
interface MxEditorJsConfig {
  connectorUrl: string;
  resourceId: number;
  assetsUrl: string;
  tmplvarId?: number;
  profile: string;
  enabledTools: string[];
  galleryMaxCount?: number;  // 0 = unlimited
  presets: {
    imageClass: Record<string, string>;
    linkClass: Record<string, string>;
    linkTarget: Record<string, string>;
    linkRel: Record<string, string>;
  };
  locale: string;
  i18n: Record<string, string>;
  editorJsI18n?: { messages?: Record<string, Record<string, string>> };
}
```

### MODx.loadRTE / MODx.unloadRTE

```javascript
window.MODx.loadRTE('ta');           // или массив / CSV id
window.MODx.unloadRTE('tv123');
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
      "type": "paragraph",
      "data": { "text": "Hello" },
      "tunes": { "alignmentTune": { "alignment": "left" } }
    }
  ]
}
```

### Image

```json
{
  "type": "image",
  "data": {
    "file": { "url": "/assets/images/photo.jpg" },
    "caption": "",
    "withBorder": false,
    "stretched": false,
    "withBackground": false
  }
}
```

### Gallery

```json
{
  "type": "gallery",
  "data": {
    "files": [
      { "url": "/assets/images/a.jpg", "name": "a.jpg", "size": 1024 }
    ],
    "caption": "",
    "style": "fit"
  }
}
```

`style`: `"fit"` | `"slider"`

### Embed

```json
{
  "type": "embed",
  "data": {
    "service": "youtube",
    "source": "https://www.youtube.com/watch?v=...",
    "embed": "https://www.youtube.com/embed/...",
    "width": 580,
    "height": 320,
    "caption": ""
  }
}
```

### Ответы API

Успех: `{ "success": true, "data": { ... } }`  
Ошибка: `{ "success": false, "message": "..." }`  
Upload fail: `{ "success": 0, "message": "..." }`
