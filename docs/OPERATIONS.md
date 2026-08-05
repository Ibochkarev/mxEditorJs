# Эксплуатация и отладка mxEditorJs

## Для пользователей и администраторов

### Редактор не отображается

1. `which_editor` = **mxEditorJs**
2. `mxeditorjs.enabled` = **Да**
3. `use_editor` = **Да**
4. **Система → Очистить кэш**

### Редактор не появляется в TV

- TV: **Textarea**, **Rich Text** = **Да**
- Откройте вкладку **Дополнительные поля**

### Не добавляется видео

Вставьте URL (YouTube, RuTube и т.д.) в пустой блок через Ctrl+V. Кнопки Embed нет.

### Картинки и галерея не загружаются

- Media Source (`mxeditorjs.image_mediasource`)
- Права на запись в папку (`mxeditorjs.image_upload_path`)
- Размер ≤ `mxeditorjs.max_upload_size` (5 МБ по умолчанию)
- Формат: JPG, PNG, GIF, WebP, SVG

Подробнее: [USER_GUIDE.md](USER_GUIDE.md), [CONFIGURATION.md](CONFIGURATION.md).

---

## Для разработчиков

### Две копии файлов

| Директория | Назначение |
|------------|------------|
| `Extras/mxEditorJs/` | Исходники |
| `core/components/mxeditorjs/` | PHP, читается MODX |
| `assets/components/mxeditorjs/` | JS, CSS, connector |

MODX не читает `Extras/` напрямую. После правок синхронизируйте файлы.

### Синхронизация

```bash
cp -r Extras/mxEditorJs/core/components/mxeditorjs/ core/components/mxeditorjs/
cp -r Extras/mxEditorJs/assets/components/mxeditorjs/ assets/components/mxeditorjs/
```

Или rsync:

```bash
rsync -av --delete Extras/mxEditorJs/core/components/mxeditorjs/ core/components/mxeditorjs/
rsync -av --delete --exclude='node_modules' Extras/mxEditorJs/assets/components/mxeditorjs/ assets/components/mxeditorjs/
```

### Static Plugin

```sql
SELECT id, name, static, static_file FROM modx_site_plugins WHERE name = 'mxEditorJs';

UPDATE modx_site_plugins
SET static = 1,
    static_file = 'Extras/mxEditorJs/core/components/mxeditorjs/elements/plugins/mxeditorjs.plugin.php'
WHERE name = 'mxEditorJs';
```

PHP-правки в static-файле применяются без пересохранения плагина в менеджере.

### Очистка кэша

```bash
rm -rf core/cache/mgr/ core/cache/includes/ core/cache/scripts/
```

Или **Система → Очистить кэш**.

---

## Сборка фронтенда

```bash
cd Extras/mxEditorJs/
npm install    # применяет patch-package для @editorjs/attaches
npm run build  # production bundle
npm run dev    # watch + sourcemap
```

После сборки скопируйте `mxeditorjs.js` в `assets/components/mxeditorjs/js/`.

### patch-package

Патч `patches/@editorjs+attaches+1.3.2.patch` меняет lifecycle hook Attaches: `appendCallback` → `rendered`. Без него диалог выбора файла не откроется при добавлении блока из toolbox.

Если после `npm update @editorjs/attaches` патч не применился, обновите patch-файл:

```bash
npx patch-package @editorjs/attaches
```

### Версионирование ассетов

Плагин добавляет `?v={filemtime}` к URL CSS и JS. Браузер подхватывает новые файлы без ручной очистки.

---

## Отладка

### PHP

```
core/cache/logs/error.log
```

```bash
grep '\[mxEditorJs\]' core/cache/logs/error.log
```

### JavaScript

DevTools → Console. Ошибки инициализации и connector-запросов выводятся с префиксом `[mxEditorJs]`.

### Проверка загрузки

Network: `mxeditorjs.css?v=...`, `mxeditorjs.js?v=...`, `gallery-front.css`.

Console:

```javascript
console.log(window.mxEditorJsConfig);
```

Ожидаемые поля: `connectorUrl`, `resourceId`, `enabledTools`, `galleryMaxCount`, `locale`.

---

## Типичные проблемы (разработчик)

| Симптом | Причина | Решение |
|---------|---------|---------|
| Редактор не грузится | Нет `mxeditorjs.js` в assets | `npm run build`, синхронизация |
| MutationObserver error | Старый билд | Пересобрать и скопировать JS |
| TV не инициализируется | Rich Text выключен | Textarea + Rich Text = Да |
| Attaches не открывает файл | Патч не применён | `npm install`, проверить postinstall |
| Gallery нет в toolbar | Старые настройки после upgrade | Проверить `available_tools` и профиль, переустановить resolver или добавить `gallery` вручную |
| HTML на сайте ≠ preview | Два рендерера | Сверить `HtmlRenderer` и `renderPreviewHtml()` |

---

## Upgrade с версий до 1.1.0

Resolver `resolve.settings.php` добавляет `gallery` в `mxeditorjs.available_tools` и профили `default`, `full`, `blog`. Если профили редактировали вручную, проверьте JSON в `mxeditorjs.profiles`.

Новая настройка: `mxeditorjs.gallery_max_count` (default `0`).

CSS галереи на фронте: `assets/components/mxeditorjs/css/gallery-front.css`.
