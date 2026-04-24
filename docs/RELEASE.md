# Процесс релиза mxEditorJs

## Подготовка

### 1. Обновите версию

Отредактируйте файл `_build/config.inc.php`:

```php
'version' => '1.0.1',
'release' => 'beta2',  // beta1, rc1, pl (production)
```

Обновите `package.json`:

```json
"version": "1.0.1"
```

### 2. Обновите changelog

Файл: `core/components/mxeditorjs/docs/changelog.txt`

```
mxEditorJs 1.0.1 (YYYY-MM-DD)
====================================
- Feature: описание
- Fix: описание
```

### 3. Соберите фронтенд

```bash
cd Extras/mxEditorJs/
npm run build
```

Проверьте, что `assets/components/mxeditorjs/js/mxeditorjs.js` обновился.

### 4. Синхронизируйте файлы

```bash
# PHP
cp -r Extras/mxEditorJs/core/components/mxeditorjs/ core/components/mxeditorjs/

# JS + CSS
cp -r Extras/mxEditorJs/assets/components/mxeditorjs/ assets/components/mxeditorjs/
```

### 5. Соберите транспортный пакет

```bash
cd Extras/mxEditorJs/
php _build/build.php
```

Результат: `core/packages/mxeditorjs-{VERSION}-{RELEASE}.transport.zip`

## Тестирование перед релизом

1. Установите пакет на чистую инстанцию MODX 3
2. Пройдите чеклист из [TESTING.md](TESTING.md)
3. Проверьте работу с `which_element_editor` = `Ace` (не должно мешать)
4. Проверьте миграцию HTML-контента

## Публикация

### GitHub Release

1. Создайте тег: `git tag v1.0.1-beta2` (или актуальная версия)
2. Загрузите тег: `git push origin v1.0.1-beta2`
3. Создайте Release на GitHub с changelog и прикрепите `.transport.zip`

### MODX Package Provider

1. Загрузите `.transport.zip` на modx.com или modstore.pro
2. Заполните описание, требования, скриншоты
