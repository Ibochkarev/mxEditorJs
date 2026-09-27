# Чеклист тестирования mxEditorJs

Версия пакета: **1.1.0-beta4**

## Предварительные условия

- [ ] MODX 3.0.3+
- [ ] PHP 8.2+
- [ ] `which_editor` = `mxEditorJs`
- [ ] `mxeditorjs.enabled` = `true`
- [ ] `use_editor` = `true`
- [ ] `npm run build` выполнен
- [ ] Файлы синхронизированы в `core/` и `assets/`
- [ ] Кэш MODX очищен

---

## 1. Инициализация

- [ ] Редактор отображается в поле контента ресурса
- [ ] Toolbar: Source, Fullscreen
- [ ] Плейсхолдер в пустом редакторе
- [ ] Консоль без ошибок

## 2. Block tools

- [ ] Paragraph (Enter)
- [ ] Header — уровни H2–H5
- [ ] List: маркированный и нумерованный
- [ ] Checklist
- [ ] Quote с подписью
- [ ] Table: строки, столбцы, заголовок
- [ ] Code
- [ ] Raw HTML
- [ ] Delimiter
- [ ] Warning

## 3. Inline и tunes

- [ ] Bold, Italic, Underline
- [ ] Inline code, Marker
- [ ] Link
- [ ] Alignment: left, center, right

## 4. Image

- [ ] Drag-and-drop upload
- [ ] Кнопка загрузки
- [ ] Browse Media Source
- [ ] Навигация: Назад, В корень
- [ ] Caption, Border, Stretch, Background
- [ ] CSS preset (если настроен)

## 5. Gallery

- [ ] Создать блок Gallery
- [ ] Загрузить несколько изображений
- [ ] Browse из Media Source
- [ ] Drag-and-drop сортировка миниатюр
- [ ] Режим fit (сетка)
- [ ] Режим slider (горизонтальный скрoll)
- [ ] Общая подпись
- [ ] Лимит `gallery_max_count` (если > 0)
- [ ] HTML на сайте: классы `mxeditorjs-gallery`, `mxeditorjs-gallery--fit|slider`

## 6. Attaches

- [ ] Загрузка файла из toolbox (диалог открывается)
- [ ] Drag-and-drop
- [ ] Ссылка download в HTML

## 7. Links

- [ ] Inline link
- [ ] Autocomplete по pagetitle
- [ ] Autocomplete по ID
- [ ] Target, rel, CSS class

## 8. Embed

- [ ] Paste URL YouTube
- [ ] Paste URL RuTube
- [ ] Paste URL Vimeo
- [ ] iframe в HTML preview и на сайте

## 9. Save / load

- [ ] Save → sidecar JSON записан
- [ ] Reopen → контент из sidecar
- [ ] HTML в `modResource.content`
- [ ] Повторный save без изменений → version не растёт (hash dedup)

## 10. Template Variables

- [ ] TV Textarea + Rich Text → Editor.js на вкладке TV
- [ ] Save → `mxeditorjs_tv_content`
- [ ] Reopen → TV JSON загружается
- [ ] Delete resource → sidecar TV удалён
- [ ] TV: миграция HTML **не** предлагается

## 11. Fullscreen и Source

- [ ] Fullscreen (кнопка и F11)
- [ ] Escape выходит из fullscreen
- [ ] Source preview (Ctrl+U)
- [ ] HTML в Source соответствует блокам

## 12. Migration

- [ ] Ресурс с HTML, без sidecar → modal migration
- [ ] dry_run: blocks_count, html_length
- [ ] Confirm → blocks созданы
- [ ] Cancel → пустой редактор
- [ ] force перезаписывает sidecar
- [ ] После миграции `modResource.content` содержит HTML-снимок, не исходный HTML
- [ ] Очистить все блоки → сохранить → `content/get` возвращает `blocks: []`
- [ ] MIGX TV, `inputTVtype: richtext` → в окне строки открывается mxEditorJs
- [ ] Сохранить строку MIGX → HTML в JSON TV, повторное открытие восстанавливает блоки

## 13. Undo / Redo

- [ ] Ctrl/Cmd+Z
- [ ] Ctrl/Cmd+Shift+Z

## 14. Локализация

- [ ] `cultureKey` = `ru` → русский UI
- [ ] `cultureKey` = `en` → английский UI
- [ ] Нет сырых ключей лексикона в интерфейсе

## 15. Профили

- [ ] `profile` = `minimal` → paragraph, header, list, image
- [ ] `profile` = `blog` → + quote, gallery, embed, delimiter
- [ ] `enabled_tools` = `paragraph,header` → переопределяет профиль
- [ ] Inline tools (marker, link) доступны при любом профиле

## 16. Безопасность

- [ ] Connector без сессии → `{ success: false, message: "Доступ запрещён" }` (HTTP 200)
- [ ] Upload недопустимого типа → ошибка
- [ ] Upload > max_upload_size → ошибка
- [ ] content/save с invalid JSON → validation error
- [ ] content/save без save_document → access denied

## 17. Delete resource

- [ ] `mxeditorjs_content` удалена
- [ ] `mxeditorjs_tv_content` удалены

## 18. Upgrade (если тестируете update)

- [ ] После upgrade `gallery` в available_tools
- [ ] Gallery в профилях default, full, blog
- [ ] `gallery_max_count` настройка создана
