# Руководство разработчика mxEditorJs

## Содержание

1. [Архитектура](#архитектура)
2. [Жизненный цикл данных](#жизненный-цикл-данных)
3. [Серверная часть (PHP)](#серверная-часть-php)
4. [Клиентская часть (TypeScript)](#клиентская-часть-typescript)
5. [Модель данных](#модель-данных)
6. [Система событий MODX](#система-событий-modx)
7. [Сборка фронтенда](#сборка-фронтенда)
8. [Расширение рендерера](#расширение-рендерера)
9. [Добавление нового инструмента](#добавление-нового-инструмента)
10. [Разработка и отладка](#разработка-и-отладка)

---

## Архитектура

mxEditorJs построен на паттерне **Canonical JSON + HTML Snapshot**:

```
┌──────────────────────────────────────────────────────────────┐
│                     Manager (браузер)                        │
│  ┌─────────────┐    ┌──────────────┐    ┌───────────────┐   │
│  │  Editor.js   │───▶│ MxEditorJsApp│───▶│  Connector    │   │
│  │  (блочный)  │◀───│ (TypeScript) │◀───│  (AJAX)       │   │
│  └─────────────┘    └──────────────┘    └───────┬───────┘   │
└─────────────────────────────────────────────────┼───────────┘
                                                  │ HTTP/JSON
┌─────────────────────────────────────────────────┼───────────┐
│                     Server (PHP)                │           │
│  ┌──────────────┐   ┌──────────────┐   ┌───────▼────────┐  │
│  │ HtmlRenderer │   │ContentValidator│  │  connector.php │  │
│  │ (JSON→HTML)  │   │ (структура)  │   │  (маршрутизация)│  │
│  └──────┬───────┘   └──────────────┘   └───────┬────────┘  │
│         │                                       │           │
│  ┌──────▼──────────────────────────────────────▼────────┐  │
│  │          Repository (ContentRepository,              │  │
│  │                      TvContentRepository)            │  │
│  └──────────────────────┬───────────────────────────────┘  │
│                         │                                   │
│  ┌──────────────────────▼───────────────────────────────┐  │
│  │           MySQL (sidecar-таблицы)                    │  │
│  │   mxeditorjs_content  │  mxeditorjs_tv_content       │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### Ключевые принципы

1. **JSON — источник истины**: Канонический формат Editor.js OutputData хранится в sidecar-таблицах, не в `modx_site_content.content`
2. **HTML Snapshot**: При каждом сохранении `HtmlRenderer` генерирует HTML и записывает его в `modResource.content` для совместимости с фронтендом
3. **Hash-based deduplication**: Контент сохраняется только если SHA-256 хеш изменился
4. **Версионирование**: Каждое сохранение увеличивает `content_version`

---

## Жизненный цикл данных

### Сохранение ресурса

```
Пользователь нажимает "Сохранить"
    │
    ▼
Editor.js → OutputData (JSON)
    │
    ├── Основной контент: POST mxeditorjs_json
    └── TV-поля: POST mxeditorjs_tv_{id}_json
    │
    ▼
MODX Plugin: OnBeforeDocFormSave
    │
    ├── ContentRepository.save(resourceId, jsonData)
    └── TvContentRepository.save(resourceId, tmplvarId, jsonData)
```

### Загрузка ресурса

```
MODX Plugin: OnDocFormPrerender
    │
    ├── Проверяет: mxeditorjs.enabled, use_editor, which_editor
    ├── Подключает CSS + JS
    └── Передаёт window.mxEditorJsConfig
    │
    ▼
MxEditorJsApp.init()
    │
    ├── Загружает JSON через connector: content/get
    ├── Если JSON есть → инициализирует Editor.js
    └── Если JSON нет, а HTML есть → предлагает миграцию
```

---

## Серверная часть (PHP)

### Структура классов

```
core/components/mxeditorjs/src/
├── Renderer/
│   └── HtmlRenderer.php          # JSON → HTML рендеринг
├── Repository/
│   ├── ContentRepository.php      # CRUD для основного контента
│   └── TvContentRepository.php    # CRUD для TV-контента
├── Service/
│   ├── HtmlMigrator.php           # HTML → JSON конвертация
│   └── MediaUploader.php          # Загрузка файлов + браузер
└── Validator/
    └── ContentValidator.php       # Валидация структуры Editor.js
```

### HtmlRenderer

Преобразует Editor.js JSON в HTML. Поддерживает 13 типов блоков:

| Тип | HTML-выход |
|-----|-----------|
| `paragraph` | `<p>текст</p>` |
| `header` | `<h1>`...`<h6>` |
| `list` | `<ul>`/`<ol>` |
| `checklist` | `<ul class="mxeditorjs-checklist">` с `<input type="checkbox">` |
| `image` | `<figure class="mxeditorjs-image"><img>` |
| `attaches` | `<p><a download>` |
| `embed` | `<div class="mxeditorjs-embed"><iframe>` |
| `delimiter` | `<hr>` |
| `quote` | `<blockquote>` + `<cite>` |
| `code` | `<pre><code>` |
| `raw` | Сырой HTML без экранирования |
| `table` | `<table>` с `<th>`/`<td>` |
| `warning` | `<div class="mxeditorjs-warning">` |

Выравнивание: блоки paragraph, header, list, quote поддерживают `tunes.alignmentTune.alignment`.

Можно зарегистрировать кастомный рендерер:

```php
$renderer = new \MxEditorJs\Renderer\HtmlRenderer();
$renderer->registerBlockRenderer('myBlock', function(array $data, array $block): string {
    return '<div class="my-block">' . htmlspecialchars($data['text']) . '</div>';
});
```

### ContentValidator

Проверяет структуру Editor.js OutputData перед сохранением:
- Наличие массива `blocks`
- Каждый блок содержит `type` (строка из whitelist) и `data` (массив)

Допустимые типы: `paragraph`, `header`, `list`, `image`, `attaches`, `embed`, `delimiter`, `quote`, `code`, `raw`, `table`, `warning`, `checklist`.

### ContentRepository

Работает с таблицей `mxeditorjs_content`:

```php
$repo = new \MxEditorJs\Repository\ContentRepository($modx);

// Получить JSON по ID ресурса
$row = $repo->findByResourceId(42);

// Сохранить (с дедупликацией по хешу)
$repo->save(42, $editorJsData, $userId);

// Удалить
$repo->deleteByResourceId(42);
```

### TvContentRepository

Аналогичен `ContentRepository`, но для TV-полей с составным ключом `(resource_id, tmplvar_id)`:

```php
$tvRepo = new \MxEditorJs\Repository\TvContentRepository($modx);
$tvRepo->findByResourceAndTv(42, 5);
$tvRepo->save(42, 5, $editorJsData, $userId);
$tvRepo->deleteByResourceAndTv(42, 5);
$tvRepo->deleteByResourceId(42); // удалить все TV ресурса
```

### MediaUploader

Загрузка файлов через MODX Media Sources:

- `upload(array $file, int $resourceId)` — загрузка изображения в путь из `mxeditorjs.image_upload_path`
- `uploadFile(array $file, int $resourceId)` — загрузка файла-вложения
- `browse(int $resourceId, string $type, string $subPath)` — просмотр содержимого директории

Валидация: MIME-тип, расширение, размер файла. Автоматическое создание директорий и уникальные имена файлов.

### HtmlMigrator

Конвертирует HTML-контент в формат Editor.js. Поддержка: `<p>`, `<h1>`–`<h6>`, `<ul>`/`<ol>`, `<blockquote>`, `<hr>`, `<pre>`, `<figure>`, `<img>`, `<table>`, `<div>`/`<section>`/`<article>`.

```php
$migrator = new \MxEditorJs\Service\HtmlMigrator();
$editorJsData = $migrator->convert('<h1>Title</h1><p>Content</p>');
// → ['time' => ..., 'blocks' => [...], 'version' => '2.31.0']
```

---

## Клиентская часть (TypeScript)

### Файловая структура

```
assets/components/mxeditorjs/js/src/
├── mxeditorjs.ts              # Главный модуль
├── types.d.ts                 # Объявления типов
└── tools/
    ├── ImageTool.ts           # Кастомный инструмент изображений
    └── LinkAutocomplete.ts    # Инструмент вставки ссылок
```

### MxEditorJsApp

Главный класс приложения. Инициализируется через `window.mxEditorJsConfig`, которую передаёт плагин в `OnDocFormPrerender`.

Ключевые функции:
- **registerRteHooks()** — перехватывает `window.MODx.loadRTE` / `window.MODx.unloadRTE` для интеграции с MODX Manager
- **startTvObserver()** — `MutationObserver` отслеживает появление новых `textarea.modx-richtext` (TV-полей) и инициализирует для них Editor.js
- **buildTools()** — собирает конфигурацию инструментов из `config.enabledTools`
- **Toolbar** — кнопки Source Preview и Fullscreen

### ImageTool

Кастомный блок-инструмент для изображений:
- Загрузка через drag-and-drop
- Выбор из Media Source через встроенный браузер
- Настройки: border, stretch, background
- CSS-пресеты классов из `mxeditorjs.image_class_presets`

### LinkAutocomplete

Inline-инструмент для ссылок:
- Автодополнение ресурсов MODX по pagetitle/longtitle/id
- Настройки: target, rel, CSS-класс
- Выпадающие списки из `mxeditorjs.link_target_options` и `mxeditorjs.link_rel_options`

### Подключённые библиотеки Editor.js

| Пакет | Тип | Описание |
|-------|-----|----------|
| `@editorjs/editorjs` | Ядро | Движок редактора |
| `@editorjs/header` | Block | Заголовки H1–H6 |
| `@editorjs/list` | Block | Маркированные/нумерованные списки |
| `@editorjs/paragraph` | Block | Параграфы |
| `@editorjs/delimiter` | Block | Горизонтальный разделитель |
| `@editorjs/quote` | Block | Цитата с подписью |
| `@editorjs/code` | Block | Блок кода |
| `@editorjs/raw` | Block | Сырой HTML |
| `@editorjs/table` | Block | Таблица |
| `@editorjs/attaches` | Block | Файлы-вложения |
| `@editorjs/embed` | Block | Встроенный контент (YouTube, Vimeo и др.) — работает через Paste API |
| `@editorjs/warning` | Block | Предупреждение (заголовок + сообщение) |
| `@editorjs/checklist` | Block | Чеклист с флажками |
| `@editorjs/marker` | Inline | Маркер (выделение текста) |
| `@editorjs/inline-code` | Inline | Инлайн-код |
| `@editorjs/underline` | Inline | Подчёркивание |
| `editorjs-text-alignment-blocktune` | Tune | Выравнивание текста |
| `editorjs-undo` | Plugin | Undo/Redo |

> **Embed** — не имеет кнопки в тулбаре. Чтобы вставить видео/контент — просто вставьте URL (YouTube, Vimeo, Instagram и др.) в редактор. Embed-блок создастся автоматически.

---

## Модель данных

### Таблица mxeditorjs_content

Хранит JSON основного контента ресурса.

| Поле | Тип | Описание |
|------|-----|----------|
| `id` | `int AUTO_INCREMENT` | PK |
| `resource_id` | `int UNSIGNED UNIQUE` | ID ресурса MODX |
| `content_json` | `mediumtext` | Editor.js OutputData (JSON) |
| `content_version` | `int UNSIGNED` | Инкрементальный счётчик версий |
| `content_hash` | `varchar(64)` | SHA-256 хеш content_json |
| `schema_version` | `varchar(16)` | Версия Editor.js (например, `2.31`) |
| `created_at` | `datetime` | Дата создания |
| `updated_at` | `datetime` | Дата последнего обновления |
| `created_by` | `int UNSIGNED` | ID пользователя-создателя |
| `updated_by` | `int UNSIGNED` | ID последнего редактора |

### Таблица mxeditorjs_tv_content

Хранит JSON контента TV-полей. Структура идентична `mxeditorjs_content`, но с добавленным полем `tmplvar_id` и составным уникальным индексом `(resource_id, tmplvar_id)`.

### Формат JSON (Editor.js OutputData)

```json
{
  "time": 1709827200000,
  "version": "2.31.0",
  "blocks": [
    {
      "type": "header",
      "data": {
        "text": "Заголовок статьи",
        "level": 2
      }
    },
    {
      "type": "paragraph",
      "data": {
        "text": "Текст параграфа с <b>форматированием</b>"
      }
    },
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
  ]
}
```

---

## Система событий MODX

Плагин `mxeditorjs.plugin.php` обрабатывает 4 события:

### OnRichTextEditorRegister

Регистрирует `mxEditorJs` в списке доступных RTE. Позволяет выбрать его в системной настройке `which_editor`.

### OnDocFormPrerender

Вызывается при открытии формы редактирования ресурса. Плагин:

1. Проверяет `mxeditorjs.enabled`, `use_editor`, `which_editor`
2. Загружает лексикон `mxeditorjs:default`
3. Собирает конфигурацию: профиль инструментов, пресеты, i18n
4. Подключает CSS (`mxeditorjs.css`) и JS (`mxeditorjs.js`)
5. Выводит `window.mxEditorJsConfig` в HTML страницы

### OnBeforeDocFormSave

Вызывается при сохранении ресурса:

1. Извлекает `mxeditorjs_json` из POST-данных
2. Сохраняет JSON в sidecar-таблицу через `ContentRepository`
3. Для каждого `mxeditorjs_tv_{id}_json` сохраняет TV-контент через `TvContentRepository`

### OnResourceDelete

При удалении ресурса удаляет связанные записи из `mxeditorjs_content` и `mxeditorjs_tv_content`.

---

## Сборка фронтенда

Проект использует **ESBuild** для компиляции TypeScript в единый бандл.

```bash
# Однократная сборка (минифицированная)
npm run build

# Режим наблюдения (без минификации, с sourcemap)
npm run dev
```

Конфигурация в `build.mjs`:

- **Вход**: `assets/components/mxeditorjs/js/src/mxeditorjs.ts`
- **Выход**: `assets/components/mxeditorjs/js/mxeditorjs.js`
- **Формат**: IIFE (самовыполняющаяся функция)
- **Global**: `MxEditorJs`
- **Target**: ES2020
- **Бандлинг**: все зависимости Editor.js включены в один файл

---

## Расширение рендерера

Добавьте свой рендерер для кастомного типа блока:

```php
require_once $corePath . 'src/Renderer/HtmlRenderer.php';

$renderer = new \MxEditorJs\Renderer\HtmlRenderer();

$renderer->registerBlockRenderer('customBlock', function(array $data, array $block): string {
    $title = htmlspecialchars($data['title'] ?? '', ENT_QUOTES, 'UTF-8');
    $body = htmlspecialchars($data['body'] ?? '', ENT_QUOTES, 'UTF-8');
    return '<div class="custom-block"><h3>' . $title . '</h3><p>' . $body . '</p></div>';
});

$html = $renderer->render($editorJsData);
```

---

## Добавление нового инструмента

### 1. Установите пакет

```bash
npm install @editorjs/new-tool
```

### 2. Зарегистрируйте в TypeScript

В файле `assets/components/mxeditorjs/js/src/mxeditorjs.ts`, метод `buildTools()`:

```typescript
import NewTool from '@editorjs/new-tool';

// В объекте allTools:
newTool: {
    class: NewTool,
    config: { /* ... */ }
}
```

### 3. Добавьте тип в валидатор

В `ContentValidator.php`, добавьте `'newTool'` в `ALLOWED_BLOCK_TYPES`.

### 4. Добавьте рендерер

В `HtmlRenderer.php`, зарегистрируйте метод рендеринга в `registerDefaults()`.

### 5. Обновите настройки

Добавьте `newTool` в значение `mxeditorjs.available_tools` и в профили `mxeditorjs.profiles`.

### 6. Соберите

```bash
npm run build
```

---

## Разработка и отладка

### Две копии файлов

В процессе разработки существуют две копии:

| Местоположение | Назначение |
|---|---|
| `Extras/mxEditorJs/` | Исходники (для разработки) |
| `core/components/mxeditorjs/` + `assets/components/mxeditorjs/` | Установленные файлы (читаются MODX) |

### Синхронизация

После внесения изменений скопируйте файлы:

```bash
# PHP
cp -r Extras/mxEditorJs/core/components/mxeditorjs/ core/components/mxeditorjs/

# JS + CSS
cp -r Extras/mxEditorJs/assets/components/mxeditorjs/ assets/components/mxeditorjs/
```

### Очистка кэша

```bash
rm -rf core/cache/mgr/ core/cache/includes/ core/cache/scripts/
```

### Static Plugin

Если плагин настроен как Static, MODX читает PHP-код из файла на диске (путь к `mxeditorjs.plugin.php`). Это избавляет от необходимости обновлять содержимое плагина в БД после каждого изменения.

### Режим dev

```bash
cd Extras/mxEditorJs/
npm run dev
```

ESBuild будет следить за изменениями TypeScript-файлов и пересобирать бандл автоматически. Не забудьте скопировать скомпилированный `mxeditorjs.js` в `assets/components/mxeditorjs/js/`.
