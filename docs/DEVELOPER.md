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

mxEditorJs использует паттерн **Canonical JSON + HTML Snapshot**:

```
Manager (браузер)
  Editor.js ←→ MxEditorJsApp (TypeScript)
       │              │
       │              ├── content/get, content/migrate
       │              ├── media/upload, media/uploadFile, media/browse
       │              └── link/search
       │
       ▼ Form submit (MODX)
  POST: textarea (HTML) + mxeditorjs_json + mxeditorjs_tv_{id}_json
       │
       ▼ OnBeforeDocFormSave
  ContentRepository / TvContentRepository → sidecar tables

Server (PHP)
  connector.php → Validator, HtmlRenderer, Repositories, MediaUploader, HtmlMigrator
  bootstrap.php → addPackage('mxeditorjs')
```

### Принципы

1. **JSON — источник истины** — Editor.js OutputData в `mxeditorjs_content` и `mxeditorjs_tv_content`.
2. **HTML — снимок для фронтенда** — textarea ресурса или TV получает HTML при сохранении формы.
3. **Два пути рендера HTML** — клиент (`renderPreviewHtml` в `mxeditorjs.ts`) при обычном сохранении, сервер (`HtmlRenderer`) при вызове connector `content/save`.
4. **Дедупликация по хешу** — sidecar не обновляется, если SHA-256 JSON не изменился.
5. **Версионирование** — каждое реальное сохранение увеличивает `content_version`.

---

## Жизненный цикл данных

### Сохранение через форму MODX (основной путь)

```
Пользователь нажимает «Сохранить»
    │
    ▼
Editor.js → OutputData
    │
    ├── syncToTextarea(): HTML → textarea (клиентский renderPreviewHtml)
    ├── mxeditorjs_json → hidden field (основной контент)
    └── mxeditorjs_tv_{id}_json → hidden fields (TV)
    │
    ▼
OnBeforeDocFormSave
    │
    ├── ContentRepository.save(resourceId, json)     // только JSON
    └── TvContentRepository.save(resourceId, tvId, json)
```

`HtmlRenderer` при этом **не вызывается**. HTML в `modResource.content` приходит из textarea.

### Сохранение через connector (API)

```
POST content/save + content_json
    │
    ├── ContentValidator
    ├── HtmlRenderer → HTML
    ├── sidecar save
    └── modResource.content = HTML (только основной контент, не TV)
```

### Загрузка

```
OnDocFormPrerender → window.mxEditorJsConfig
    │
    ▼
MxEditorJsApp.initForElement()
    │
    ├── content/get (sidecar JSON)
    ├── normalizeContent() — приведение legacy-структур
    └── если JSON null и основной контент → tryMigrateContent()
```

---

## Серверная часть (PHP)

### Структура классов

```
core/components/mxeditorjs/src/
├── Config/
│   └── EditorTools.php           # Резолв профилей и whitelist
├── Renderer/
│   └── HtmlRenderer.php          # JSON → HTML (14 типов блоков)
├── Repository/
│   ├── ContentRepository.php
│   └── TvContentRepository.php
├── Service/
│   ├── HtmlMigrator.php
│   └── MediaUploader.php
└── Validator/
    └── ContentValidator.php
```

### EditorTools

Класс `MxEditorJs\Config\EditorTools` определяет:

- `DEFAULT_AVAILABLE` — CSV всех block tools пакета
- `PACKAGE_PROFILES` — эталонные профили default, minimal, blog, full
- `resolve($modx, $profileName, $storedProfiles)` — итоговый список инструментов
- `migrateProfiles()` / `migrateAvailableTools()` — добавление `gallery` при upgrade

### HtmlRenderer

14 типов блоков:

| Тип | HTML |
|-----|------|
| `paragraph` | `<p>` |
| `header` | `<h1>`–`<h6>` |
| `list` | `<ul>` / `<ol>` |
| `checklist` | `<ul class="mxeditorjs-checklist">` |
| `image` | `<figure class="mxeditorjs-image"><img>` |
| `gallery` | `<figure class="mxeditorjs-gallery mxeditorjs-gallery--{fit\|slider}">` |
| `attaches` | `<p><a download>` |
| `embed` | `<div class="mxeditorjs-embed"><iframe>` |
| `delimiter` | `<hr>` |
| `quote` | `<blockquote>` + `<cite>` |
| `code` | `<pre><code>` |
| `raw` | сырой HTML |
| `table` | `<table>` |
| `warning` | `<div class="mxeditorjs-warning">` |

Выравнивание через `tunes.alignmentTune.alignment` для paragraph, header, list, quote.

Кастомный рендерер:

```php
$renderer = new \MxEditorJs\Renderer\HtmlRenderer();
$renderer->registerBlockRenderer('myBlock', function (array $data, array $block): string {
    return '<div class="my-block">' . htmlspecialchars($data['text'] ?? '', ENT_QUOTES, 'UTF-8') . '</div>';
});
```

### ContentValidator

Whitelist типов: `paragraph`, `header`, `list`, `checklist`, `quote`, `table`, `code`, `raw`, `embed`, `image`, `gallery`, `attaches`, `delimiter`, `warning`.

### HtmlMigrator

Конвертирует HTML в Editor.js blocks. Не создаёт embed, gallery, attaches, warning, checklist — только базовую разметку.

---

## Клиентская часть (TypeScript)

### Файловая структура

```
assets/components/mxeditorjs/js/src/
├── mxeditorjs.ts              # MxEditorJsApp, RTE hooks, sync
├── types.d.ts
└── tools/
    ├── ImageTool.ts           # Upload + MediaBrowser
    ├── GalleryTool.ts         # @kiberpro/editorjs-gallery + browse
    ├── MediaBrowser.ts        # Общий браузер Media Source
    ├── LinkAutocomplete.ts    # Поиск ресурсов MODX
    ├── ParagraphTool.ts       # validate: пустые блоки при редактировании
    ├── HeaderTool.ts
    ├── ChecklistTool.ts
    └── AttachesTool.ts        # validate + patch rendered()
```

### Block tools (настраиваются профилем)

| ID | Источник | Особенности |
|----|----------|-------------|
| paragraph | `@editorjs/paragraph` | preserveBlank |
| header | `@editorjs/header` | levels **2–5**, default 2 |
| list | `@editorjs/list` | NestedList |
| checklist | `@editorjs/checklist` | обёртка validate |
| quote, table, code, raw, delimiter, warning | стандартные пакеты | |
| embed | `@editorjs/embed` | Paste API, + rutube; нет кнопки в toolbox |
| image | `ImageTool` | upload, browse, CSS presets (UI) |
| gallery | `GalleryTool` | sortable, fit/slider, `gallery_max_count` |
| attaches | `AttachesTool` | uploader, patch-package |

### Всегда включены (не в профилях)

**Inline:** marker, inlineCode, underline, linkAutocomplete
**Tunes:** alignmentTune
**Plugin:** editorjs-undo

### window.mxEditorJsConfig

Плагин передаёт: `connectorUrl`, `resourceId`, `assetsUrl`, `profile`, `enabledTools`, `galleryMaxCount`, `presets`, `locale`, `i18n`, `editorJsI18n`.

### RTE integration

- `MODx.loadRTE` / `MODx.unloadRTE` — перехват для основного контента и TV
- `MutationObserver` — инициализация `textarea.modx-richtext` (кроме `#ta`)
- Toolbar: Source (Ctrl+U), Fullscreen (F11)

---

## Модель данных

### mxeditorjs_content

| Поле | Описание |
|------|----------|
| resource_id | UNIQUE, ID ресурса |
| content_json | Editor.js OutputData |
| content_version | Счётчик версий |
| content_hash | SHA-256 JSON |
| schema_version | Версия Editor.js из JSON |
| created_at, updated_at, created_by, updated_by | Аудит |

### mxeditorjs_tv_content

Те же поля + `tmplvar_id`, UNIQUE `(resource_id, tmplvar_id)`.

Схема: `core/components/mxeditorjs/model/schema/mxeditorjs.mysql.schema.xml`

---

## Система событий MODX

| Событие | Действие |
|---------|----------|
| **OnRichTextEditorRegister** | Регистрация `mxEditorJs` в `which_editor` |
| **OnDocFormPrerender** | CSS, JS, `mxEditorJsConfig`, cache-bust `?v=filemtime` |
| **OnBeforeDocFormSave** | JSON из POST → sidecar (без HtmlRenderer) |
| **OnResourceDelete** | Удаление sidecar основного контента и всех TV |

---

## Сборка фронтенда

```bash
npm install    # postinstall → patch-package
npm run build  # minify, ES2020 IIFE → mxeditorjs.js
npm run dev    # watch + sourcemap, без minify
```

Конфигурация `build.mjs`:

| Параметр | Значение |
|----------|----------|
| entry | `assets/.../src/mxeditorjs.ts` |
| output | `assets/.../js/mxeditorjs.js` |
| format | IIFE, globalName `MxEditorJs` |
| target | ES2020 |

### patch-package

Файл `patches/@editorjs+attaches+1.3.2.patch` заменяет устаревший `appendCallback()` на `rendered()`, чтобы диалог выбора файла открывался при добавлении блока Attaches из toolbox. Патч применяется автоматически при `npm install`.

### Зависимости (актуальные)

- Editor.js 2.31.x и официальные `@editorjs/*` tools
- `@kiberpro/editorjs-gallery` — блок Gallery
- `editorjs-text-alignment-blocktune`, `editorjs-undo`, `sortablejs`
- esbuild ^0.28, TypeScript ^7 (dev)

---

## Расширение рендерера

```php
require_once $corePath . 'src/Renderer/HtmlRenderer.php';

$renderer = new \MxEditorJs\Renderer\HtmlRenderer();
$renderer->registerBlockRenderer('customBlock', function (array $data, array $block): string {
    $title = htmlspecialchars($data['title'] ?? '', ENT_QUOTES, 'UTF-8');
    return '<div class="custom-block"><h3>' . $title . '</h3></div>';
});
$html = $renderer->render($editorJsData);
```

Для совпадения менеджера и фронтенда продублируйте логику в `renderPreviewHtml()` в `mxeditorjs.ts`.

---

## Добавление нового инструмента

1. `npm install @editorjs/new-tool`
2. Импорт и регистрация в `buildTools()` (`mxeditorjs.ts`)
3. `'newTool'` в `ContentValidator::ALLOWED_BLOCK_TYPES`
4. Рендерер в `HtmlRenderer::registerDefaults()`
5. `newTool` в `mxeditorjs.available_tools` и профили
6. `npm run build`

---

## Разработка и отладка

### Две копии файлов

| Путь | Назначение |
|------|------------|
| `Extras/mxEditorJs/` | Исходники |
| `core/components/mxeditorjs/` + `assets/components/mxeditorjs/` | Файлы, которые читает MODX |

```bash
cp -r Extras/mxEditorJs/core/components/mxeditorjs/ core/components/mxeditorjs/
cp -r Extras/mxEditorJs/assets/components/mxeditorjs/ assets/components/mxeditorjs/
rm -rf core/cache/mgr/ core/cache/includes/ core/cache/scripts/
```

Подробнее: [OPERATIONS.md](OPERATIONS.md).

### Transport-пакет

```bash
php _build/build.php
# → core/packages/mxeditorjs-1.1.0-beta2.transport.zip
```

При upgrade настройки из `_build/elements/settings.php` **не перезаписываются** автоматически (`settings => false` в config). Новые ключи добавляют resolvers (например `resolve.settings.php` для gallery).

### Metrics

Resolver `resolver_06_metrics.php` отправляет анонимную статистику установки на `https://metrics.modx.pro/`.
