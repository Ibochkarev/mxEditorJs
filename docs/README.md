# Документация mxEditorJs

Блочный редактор Editor.js для MODX 3. Версия **1.1.0-beta2**.

## Кому что читать

| Роль | Документ | Содержание |
|------|----------|------------|
| Редактор контента | [USER_GUIDE.md](USER_GUIDE.md) | Блоки, медиа, галерея, TV, горячие клавиши |
| Администратор | [CONFIGURATION.md](CONFIGURATION.md) | Профили, Media Source, пресеты, лимиты |
| Разработчик | [DEVELOPER.md](DEVELOPER.md) | Архитектура, сборка, кастомные инструменты |
| Разработчик | [API.md](API.md) | Connector API, PHP-классы, форматы данных |
| Все | [OPERATIONS.md](OPERATIONS.md) | Отладка, синхронизация файлов, типичные проблемы |
| QA | [TESTING.md](TESTING.md) | Чеклист перед релизом |
| Мейнтейнер | [RELEASE.md](RELEASE.md) | Версионирование и transport-пакет |

## Как устроено хранение

mxEditorJs хранит контент в двух слоях:

1. **JSON (источник истины)** — Editor.js OutputData в sidecar-таблицах `mxeditorjs_content` и `mxeditorjs_tv_content`.
2. **HTML (снимок)** — поле `modResource.content` или textarea TV для совместимости с фронтендом.

При сохранении ресурса через форму MODX JSON уходит в sidecar, HTML генерируется на клиенте и попадает в textarea. Connector `content/save` делает то же на сервере через `HtmlRenderer` — для интеграций и API.

## Быстрые ссылки

- [Включить редактор](USER_GUIDE.md#начало-работы)
- [Галерея изображений](USER_GUIDE.md#галерея)
- [Видео и embed](USER_GUIDE.md#видео-и-встраиваемый-контент)
- [TV-поля](USER_GUIDE.md#дополнительные-поля-tv)
- [Краткая справка настроек](CONFIGURATION.md#краткая-справка-для-администраторов)
- [Типичные проблемы](OPERATIONS.md#для-пользователей-и-администраторов)
- [Сборка фронтенда](DEVELOPER.md#сборка-фронтенда)
- [Changelog](../core/components/mxeditorjs/docs/changelog.txt)

## Внешние ресурсы

- [Editor.js](https://editorjs.io/) — документация ядра редактора
- [MODX 3 Documentation](https://docs.modx.com/) — системные настройки, TV, Media Sources
