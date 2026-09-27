# Процесс релиза mxEditorJs

Актуальная версия: **1.1.0-beta4**

## Подготовка

### 1. Версия

`_build/config.inc.php`:

```php
'version' => '1.1.0',
'release' => 'beta1',  // beta1, beta2, rc1, pl
```

`package.json`:

```json
"version": "1.1.0-beta2"
```

### 2. Changelog

`core/components/mxeditorjs/docs/changelog.txt` — формат [Keep a Changelog](https://keepachangelog.com/).

```
## [1.1.0-beta1] - YYYY-MM-DD

### Added
- ...

### Changed
- ...

### Fixed
- ...
```

### 3. Зависимости

```bash
cd Extras/mxEditorJs/
npm update
npm audit fix
npm run build
npx tsc --noEmit
```

Проверьте, что `patch-package` применил патч `@editorjs/attaches`.

### 4. Синхронизация

```bash
cp -r Extras/mxEditorJs/core/components/mxeditorjs/ core/components/mxeditorjs/
cp -r Extras/mxEditorJs/assets/components/mxeditorjs/ assets/components/mxeditorjs/
```

### 5. Transport-пакет

```bash
php _build/build.php
```

Результат: `core/packages/mxeditorjs-{VERSION}-{RELEASE}.transport.zip`

---

## Тестирование перед релизом

1. Установка на чистую MODX 3
2. Upgrade с предыдущей версии (проверить resolver gallery)
3. Чеклист [TESTING.md](TESTING.md)
4. `which_element_editor` = Ace — не ломает mxEditorJs
5. Миграция HTML основного контента

---

## Публикация

### Git tag

```bash
git tag v1.1.0-beta2
git push origin v1.1.0-beta2
```

### GitHub Release

Changelog + `.transport.zip` в attachments.

### MODX Package Provider

Загрузка на modx.com или modstore.pro: описание, требования, скриншоты.

---

## Заметки по upgrade

- `'settings' => false` в `_build/config.inc.php` — системные настройки **не перезаписываются** при upgrade
- Новые ключи добавляют **resolvers** (`resolve.settings.php` и др.)
- Таблицы sidecar сохраняются при uninstall (resolver `resolve.tables.php`)

## Metrics

При установке/upgrade resolver `resolver_06_metrics.php` отправляет анонимную статистику на `https://metrics.modx.pro/`. Не содержит данных сайта или пользователей.
