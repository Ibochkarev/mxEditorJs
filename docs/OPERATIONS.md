# Эксплуатация и отладка mxEditorJs

## Для пользователей и администраторов

### Редактор не отображается

1. **Система → Системные настройки** → `which_editor` = **mxEditorJs**
2. В настройках mxeditorjs: `mxeditorjs.enabled` = **Да**
3. `use_editor` = **Да**
4. **Система → Очистить кэш**

### Редактор не появляется в дополнительных полях (TV)

- TV должен быть типа **Textarea** с опцией **Rich Text** = `Да`
- Перейдите на вкладку «Дополнительные поля» — редактор загружается при появлении поля

### Не добавляется видео

Кнопки «Embed» нет. **Скопируйте ссылку** (YouTube, RuTube и т.д.) и **вставьте** (Ctrl+V) в пустой блок.

### Картинки не загружаются

- Проверьте Media Source (настройка `mxeditorjs.image_mediasource`)
- Права на запись в папку загрузок
- Размер файла (до 5 МБ по умолчанию)
- Формат: JPG, PNG, GIF, WebP, SVG

Подробнее — [Руководство пользователя](USER_GUIDE.md) и [Справочник настроек](CONFIGURATION.md).

---

## Для разработчиков

### Две копии файлов

В процессе разработки проект существует в двух местах:

| Директория | Назначение |
|---|---|
| `Extras/mxEditorJs/` | Исходники. Здесь ведётся разработка. |
| `core/components/mxeditorjs/` | Установленные PHP-файлы. Читаются MODX в рантайме. |
| `assets/components/mxeditorjs/` | Установленные JS/CSS/connector. Отдаются браузеру. |

MODX читает файлы из установленных директорий, а не из `Extras/`. После изменений в исходниках необходима синхронизация.

## Синхронизация без пересборки пакета

```bash
# Копирование PHP
cp -r Extras/mxEditorJs/core/components/mxeditorjs/ core/components/mxeditorjs/

# Копирование JS + CSS + connector
cp -r Extras/mxEditorJs/assets/components/mxeditorjs/ assets/components/mxeditorjs/
```

Или с помощью rsync:

```bash
rsync -av --delete Extras/mxEditorJs/core/components/mxeditorjs/ core/components/mxeditorjs/
rsync -av --delete --exclude='node_modules' Extras/mxEditorJs/assets/components/mxeditorjs/ assets/components/mxeditorjs/
```

## Static Plugin

Плагин MODX может быть настроен как **Static** — это означает, что PHP-код читается из файла на диске, а не из базы данных.

Проверка и установка через SQL:

```sql
-- Проверить текущее состояние
SELECT id, name, static, static_file FROM modx_site_plugins WHERE name = 'mxEditorJs';

-- Установить Static
UPDATE modx_site_plugins
SET static = 1,
    static_file = 'Extras/mxEditorJs/core/components/mxeditorjs/elements/plugins/mxeditorjs.plugin.php'
WHERE name = 'mxEditorJs';
```

> При Static режиме изменения в PHP-файле плагина применяются без пересохранения в менеджере.

## Очистка кэша MODX

После изменения PHP-файлов очистите кэш:

```bash
rm -rf core/cache/mgr/ core/cache/includes/ core/cache/scripts/
```

Или через менеджер: **Система → Очистить кэш**.

## Сборка фронтенда

### Однократная сборка

```bash
cd Extras/mxEditorJs/
npm run build
```

Создаёт минифицированный `assets/components/mxeditorjs/js/mxeditorjs.js`.

### Режим наблюдения

```bash
cd Extras/mxEditorJs/
npm run dev
```

ESBuild следит за изменениями TypeScript и пересобирает бандл. Sourcemap включён, минификация выключена.

> После пересборки скопируйте `mxeditorjs.js` в `assets/components/mxeditorjs/js/`.

## Версионирование ассетов

Плагин автоматически добавляет `?v={filemtime}` к URL CSS и JS файлов. Обновлённые файлы автоматически обходят кэш браузера.

## Отладка

### PHP

Логи плагина и коннектора записываются в стандартный лог MODX:

```
core/cache/logs/error.log
```

Поиск записей mxEditorJs:

```bash
grep '\[mxEditorJs\]' core/cache/logs/error.log
```

### JavaScript

Откройте DevTools браузера (F12) → Console. Ошибки инициализации Editor.js и запросов к коннектору выводятся в консоль.

### Проверка загрузки ассетов

В DevTools → Network проверьте, что загружаются:
- `mxeditorjs.css?v=...`
- `mxeditorjs.js?v=...`
- `window.mxEditorJsConfig` — в HTML-источнике страницы

### Проверка конфигурации

В DevTools → Console выполните:

```javascript
console.log(window.mxEditorJsConfig);
```

Должен вывести объект с `connectorUrl`, `resourceId`, `enabledTools`, `locale`, `i18n`.

## Типичные проблемы (детали)

### Редактор не отображается

1. `which_editor` ≠ `mxEditorJs`
2. `mxeditorjs.enabled` = `false`
3. `use_editor` = `false`
4. Файл `mxeditorjs.js` не найден — пересоберите фронтенд (`npm run build`) и скопируйте в `assets/components/mxeditorjs/js/`
5. Кэш MODX не очищен

### Ошибка MutationObserver в консоли

Скрипт выполняется до загрузки `document.body`. Убедитесь, что используете актуальный билд.

### TV-поля не инициализируются

1. TV: **Textarea** + **Rich Text** = `Да`
2. Откройте вкладку «Дополнительные поля»
3. Проверьте консоль браузера (F12)

### Изображения не загружаются

1. Media Source (ID из `mxeditorjs.image_mediasource`) существует и доступен
2. Права записи в директорию
3. Размер и формат файла в пределах настроек
4. Лог: `core/cache/logs/error.log` на `[mxEditorJs] Upload error`
