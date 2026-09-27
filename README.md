# mxEditorJs — блочный редактор Editor.js для MODX 3

Редактор контента для MODX 3 на базе [Editor.js](https://editorjs.io/). Вы собираете страницу из блоков — заголовок, текст, картинка, галерея, видео. На сайте выводится чистый HTML.

**Версия:** 1.1.0-beta4

## Кому что читать

| Вы | Документ |
|----|----------|
| Редактор контента | [Руководство пользователя](docs/USER_GUIDE.md) |
| Администратор | [Справочник настроек](docs/CONFIGURATION.md) |
| Разработчик | [Руководство разработчика](docs/DEVELOPER.md), [API](docs/API.md) |
| Проблемы с редактором | [Эксплуатация и отладка](docs/OPERATIONS.md) |

Полный индекс: [docs/README.md](docs/README.md).

## Возможности

- **14 типов блоков** — параграф, заголовок, список, чеклист, цитата, таблица, код, raw HTML, embed, изображение, галерея, вложение, разделитель, предупреждение
- **TV-поля** — редактор в основном контенте и в Template Variables (Textarea + Rich Text)
- **Медиа** — drag-and-drop, браузер Media Source, отдельные пути для картинок и файлов
- **Галерея** — несколько изображений, сортировка, режимы fit и slider
- **Ссылки** — автодополнение по ресурсам MODX, target, rel, CSS-классы
- **Миграция HTML → Editor.js** — для основного контента ресурса
- **Профили инструментов** — default, minimal, blog, full и свои
- **Полноэкранный режим** и **Source Preview**
- **Локализация** — русский и английский
- **Undo/Redo** и выравнивание текста

## Требования

| Зависимость | Версия |
|-------------|--------|
| MODX | 3.0.3+ |
| PHP | 8.2+ |
| Node.js | 18+ (только для сборки фронтенда) |

## Установка

### Через Package Manager

1. Скачайте `mxeditorjs-*.transport.zip`
2. **Пакеты → Установщик → Загрузить пакет**
3. Установите пакет

### Из исходников

```bash
cd /path/to/modx/Extras/
git clone <repo-url> mxEditorJs
cd mxEditorJs
npm install
npm run build
php _build/build.php
```

## Быстрый старт

1. **Система → Системные настройки** → `which_editor` = **mxEditorJs**
2. `mxeditorjs.enabled` = **Да**
3. Откройте ресурс — в поле контента появится блочный редактор

## Ключевые настройки

| Настройка | По умолчанию | Назначение |
|-----------|--------------|------------|
| `mxeditorjs.enabled` | `true` | Включить редактор |
| `mxeditorjs.profile` | `default` | Профиль инструментов |
| `mxeditorjs.enabled_tools` | _(пусто)_ | Свой список инструментов |
| `mxeditorjs.image_mediasource` | `1` | Media Source для картинок |
| `mxeditorjs.gallery_max_count` | `0` | Лимит картинок в галерее (`0` = без лимита) |

Подробнее: [docs/CONFIGURATION.md](docs/CONFIGURATION.md).

## Структура проекта

```
mxEditorJs/
├── _build/                     # Сборка transport-пакета
├── assets/components/mxeditorjs/
│   ├── connector.php           # HTTP API
│   ├── css/                    # mxeditorjs.css, gallery-front.css
│   └── js/
│       ├── mxeditorjs.js       # Собранный бандл
│       └── src/                # TypeScript-исходники
├── core/components/mxeditorjs/
│   ├── elements/plugins/       # MODX-плагин
│   ├── lexicon/                # en, ru
│   ├── model/                  # xPDO-схема
│   └── src/                    # PHP-классы
├── docs/                       # Документация
├── patches/                    # patch-package для @editorjs/attaches
├── build.mjs
└── package.json
```

## Документация

| Документ | Описание |
|----------|----------|
| [USER_GUIDE.md](docs/USER_GUIDE.md) | Работа с блоками, медиа, TV |
| [CONFIGURATION.md](docs/CONFIGURATION.md) | Все системные настройки |
| [DEVELOPER.md](docs/DEVELOPER.md) | Архитектура, сборка, расширение |
| [API.md](docs/API.md) | Connector API, PHP и JS |
| [OPERATIONS.md](docs/OPERATIONS.md) | Отладка, синхронизация, типичные проблемы |
| [TESTING.md](docs/TESTING.md) | Чеклист QA |
| [RELEASE.md](docs/RELEASE.md) | Процесс релиза |

## Лицензия

MIT
