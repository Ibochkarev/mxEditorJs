# Справочник настроек mxEditorJs

Все настройки: **Система → Системные настройки** → фильтр по пространству имён **mxeditorjs**.

## Краткая справка (для администраторов)

| Настройка | Зачем нужна | По умолчанию |
|-----------|-------------|--------------|
| `mxeditorjs.enabled` | Включить/выключить редактор | Да |
| `mxeditorjs.profile` | Набор инструментов: `default`, `minimal`, `blog`, `full` | default |
| `mxeditorjs.enabled_tools` | Свой список инструментов (переопределяет профиль) | — |
| `mxeditorjs.image_mediasource` | Откуда загружать картинки (ID Media Source) | 1 |
| `mxeditorjs.image_upload_path` | Путь для загрузки изображений | `images/resources/{resource_id}/` |
| `mxeditorjs.file_upload_path` | Путь для загрузки файлов | `files/resources/{resource_id}/` |
| `mxeditorjs.max_upload_size` | Макс. размер файла в байтах (5 МБ = 5242880) | 5242880 |

> **Для редакторов контента:** обычно ничего настраивать не нужно. Редактор включается через `which_editor` = mxEditorJs в общих настройках MODX.

---

## Область: mxeditorjs (Основные)

### mxeditorjs.enabled

| | |
|---|---|
| **Тип** | combo-boolean |
| **По умолчанию** | `true` |
| **Описание** | Включает или выключает редактор. При `false` плагин не подключает ассеты и не обрабатывает сохранение. |

### mxeditorjs.profile

| | |
|---|---|
| **Тип** | textfield |
| **По умолчанию** | `default` |
| **Описание** | Имя активного профиля инструментов. Профили определяются в настройке `mxeditorjs.profiles`. |

**Предустановленные профили:**

| Профиль | Инструменты |
|---------|------------|
| `default` | paragraph, header, list, checklist, quote, table, code, raw, embed, image, attaches, delimiter, warning |
| `minimal` | paragraph, header, list, image |
| `blog` | paragraph, header, list, quote, image, embed, delimiter |
| `full` | Все инструменты (идентично default) |

### mxeditorjs.available_tools

| | |
|---|---|
| **Тип** | textfield |
| **По умолчанию** | `paragraph,header,list,checklist,quote,table,code,raw,embed,image,attaches,delimiter,warning` |
| **Описание** | Полный список доступных блок-инструментов. Используется как fallback, когда профиль пуст и `enabled_tools` не задан. |

**Допустимые идентификаторы инструментов:**

| ID | Описание | Тип |
|----|----------|-----|
| `paragraph` | Параграф | Block |
| `header` | Заголовок H1–H6 | Block |
| `list` | Маркированный/нумерованный список | Block |
| `checklist` | Чеклист с флажками | Block |
| `quote` | Цитата | Block |
| `table` | Таблица | Block |
| `code` | Блок кода | Block |
| `raw` | Сырой HTML | Block |
| `embed` | Встроенный контент (через Paste API) | Block |
| `image` | Изображение (кастомный) | Block |
| `attaches` | Вложенный файл | Block |
| `delimiter` | Горизонтальный разделитель | Block |
| `warning` | Предупреждение | Block |

> Inline-инструменты (marker, inline-code, underline, link) и tunes (выравнивание, undo) подключены всегда и не настраиваются через профили.

### mxeditorjs.enabled_tools

| | |
|---|---|
| **Тип** | textfield |
| **По умолчанию** | _(пусто)_ |
| **Описание** | Переопределение профиля. Если задано — профиль игнорируется, используется этот список. Список инструментов через запятую. |

**Пример:** `paragraph,header,list,embed,image`

### mxeditorjs.profiles

| | |
|---|---|
| **Тип** | textarea |
| **По умолчанию** | JSON с профилями |
| **Описание** | JSON-объект с определениями профилей. Каждый профиль — объект с массивом `tools`. |

**Формат:**

```json
{
  "default": {
    "tools": ["paragraph", "header", "list", "checklist", "quote", "table",
              "code", "raw", "embed", "image", "attaches", "delimiter", "warning"]
  },
  "minimal": {
    "tools": ["paragraph", "header", "list", "image"]
  },
  "blog": {
    "tools": ["paragraph", "header", "list", "quote", "image", "embed", "delimiter"]
  },
  "custom": {
    "tools": ["paragraph", "header", "list", "table", "image"]
  }
}
```

**Создание кастомного профиля:**

1. Отредактируйте JSON в настройке `mxeditorjs.profiles`
2. Добавьте новый ключ с массивом нужных инструментов
3. Установите `mxeditorjs.profile` = имя нового профиля

---

## Область: mxeditorjs_media (Медиа)

### mxeditorjs.image_mediasource

| | |
|---|---|
| **Тип** | textfield |
| **По умолчанию** | `1` |
| **Описание** | ID Media Source для загрузки и выбора изображений. По умолчанию — стандартный файловый Media Source. |

### mxeditorjs.file_mediasource

| | |
|---|---|
| **Тип** | textfield |
| **По умолчанию** | `1` |
| **Описание** | ID Media Source для загрузки файлов-вложений (инструмент Attaches). |

### mxeditorjs.image_upload_path

| | |
|---|---|
| **Тип** | textfield |
| **По умолчанию** | `images/resources/{resource_id}/` |
| **Описание** | Шаблон пути для загрузки изображений внутри Media Source. Плейсхолдер `{resource_id}` заменяется на ID ресурса. |

**Примеры:**
- `images/resources/{resource_id}/` → `images/resources/42/`
- `uploads/images/` → все изображения в одну папку
- `content/{resource_id}/img/` → `content/42/img/`

### mxeditorjs.file_upload_path

|| |
|---|---|
| **Тип** | textfield |
| **По умолчанию** | `files/resources/{resource_id}/` |
| **Описание** | Шаблон пути для загрузки файлов-вложений (блок Attaches) внутри Media Source. Плейсхолдер `{resource_id}` заменяется на ID ресурса. |

**Примеры:**
- `files/resources/{resource_id}/` → `files/resources/42/`
- `uploads/files/` → все файлы в одну папку
- `content/{resource_id}/attachments/` → `content/42/attachments/`

### mxeditorjs.allowed_image_types

| | |
|---|---|
| **Тип** | textfield |
| **По умолчанию** | `jpg,jpeg,png,gif,webp,svg` |
| **Описание** | Допустимые расширения файлов изображений. Через запятую, без пробелов. |

### mxeditorjs.max_upload_size

| | |
|---|---|
| **Тип** | numberfield |
| **По умолчанию** | `5242880` (5 МБ) |
| **Описание** | Максимальный размер загружаемого файла в байтах. |

| Значение | Размер |
|----------|--------|
| `1048576` | 1 МБ |
| `2097152` | 2 МБ |
| `5242880` | 5 МБ |
| `10485760` | 10 МБ |
| `20971520` | 20 МБ |

---

## Область: mxeditorjs_presets (Пресеты)

### mxeditorjs.image_class_presets

| | |
|---|---|
| **Тип** | textarea |
| **По умолчанию** | JSON |
| **Описание** | CSS-классы для изображений. Пользователь выбирает стиль из выпадающего списка в настройках блока Image. |

**Формат:** `{"display_name": "css-class"}`

```json
{
  "default": "",
  "full-width": "img-fluid w-100",
  "thumbnail": "img-thumbnail",
  "rounded": "rounded",
  "circle": "rounded-circle",
  "shadow": "shadow"
}
```

### mxeditorjs.link_class_presets

| | |
|---|---|
| **Тип** | textarea |
| **По умолчанию** | JSON |
| **Описание** | CSS-классы для ссылок. Пользователь выбирает стиль при редактировании ссылки. |

```json
{
  "default": "",
  "button-primary": "btn btn-primary",
  "button-secondary": "btn btn-secondary",
  "button-outline": "btn btn-outline-primary",
  "external": "external-link",
  "download": "download-link"
}
```

### mxeditorjs.link_target_options

| | |
|---|---|
| **Тип** | textarea |
| **По умолчанию** | JSON |
| **Описание** | Варианты target для ссылок. |

```json
{
  "_self": "Same window",
  "_blank": "New window",
  "_parent": "Parent frame",
  "_top": "Top frame"
}
```

### mxeditorjs.link_rel_options

| | |
|---|---|
| **Тип** | textarea |
| **По умолчанию** | JSON |
| **Описание** | Варианты атрибута rel для ссылок. |

```json
{
  "": "None",
  "nofollow": "nofollow",
  "noopener": "noopener",
  "noreferrer": "noreferrer",
  "noopener noreferrer": "noopener noreferrer",
  "sponsored": "sponsored",
  "ugc": "ugc"
}
```

---

## Связанные системные настройки MODX

Эти настройки MODX влияют на работу mxEditorJs:

| Настройка | Значение для mxEditorJs | Описание |
|---|---|---|
| `which_editor` | `mxEditorJs` | Выбор RTE. Обязательно для активации. |
| `use_editor` | `true` | Глобальное включение RTE. |
| `which_element_editor` | _(любое)_ | Редактор кода элементов. **Не влияет** на mxEditorJs. |
| `cultureKey` | `en` / `ru` | Язык интерфейса MODX. mxEditorJs наследует для локализации. |

---

## Приоритет настроек инструментов

Логика выбора набора инструментов:

```
1. mxeditorjs.enabled_tools (если не пустое) ← ВЫСШИЙ приоритет
   │
   └── (пусто) →
       2. Профиль из mxeditorjs.profiles[mxeditorjs.profile].tools
          │
          └── (пустой массив) →
              3. mxeditorjs.available_tools ← FALLBACK
```
