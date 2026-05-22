<?php
/**
 * mxEditorJs Russian lexicon
 *
 * @package mxeditorjs
 */
$_lang['mxeditorjs'] = 'mxEditorJs';
$_lang['mxeditorjs_editor_name'] = 'Editor.js';

$_lang['area_mxeditorjs'] = 'Редактор';
$_lang['area_mxeditorjs_media'] = 'Медиа и загрузки';
$_lang['area_mxeditorjs_presets'] = 'Предустановки';

$_lang['setting_mxeditorjs.enabled'] = 'Включить mxEditorJs';
$_lang['setting_mxeditorjs.enabled_desc'] = 'Включить блочный редактор для поля content ресурса.';
$_lang['setting_mxeditorjs.image_mediasource'] = 'Медиа-источник изображений';
$_lang['setting_mxeditorjs.image_mediasource_desc'] = 'Медиа-источник для загрузки изображений.';
$_lang['setting_mxeditorjs.file_mediasource'] = 'Медиа-источник файлов';
$_lang['setting_mxeditorjs.file_mediasource_desc'] = 'Медиа-источник для загрузки файлов.';
$_lang['setting_mxeditorjs.image_upload_path'] = 'Путь загрузки изображений';
$_lang['setting_mxeditorjs.image_upload_path_desc'] = 'Шаблон пути для загрузки изображений.';
$_lang['setting_mxeditorjs.file_upload_path'] = 'Путь загрузки файлов';
$_lang['setting_mxeditorjs.file_upload_path_desc'] = 'Шаблон пути для загрузки файлов-вложений (блок Attaches). Плейсхолдер {resource_id} заменяется на ID ресурса.';
$_lang['setting_mxeditorjs.allowed_image_types'] = 'Допустимые типы изображений';
$_lang['setting_mxeditorjs.allowed_image_types_desc'] = 'Список допустимых расширений через запятую.';
$_lang['setting_mxeditorjs.max_upload_size'] = 'Максимальный размер загрузки';
$_lang['setting_mxeditorjs.max_upload_size_desc'] = 'Максимальный размер загружаемого файла в байтах.';
$_lang['setting_mxeditorjs.gallery_max_count'] = 'Галерея: максимум изображений';
$_lang['setting_mxeditorjs.gallery_max_count_desc'] = 'Максимум изображений в блоке «Галерея»; 0 — без ограничения.';
$_lang['setting_mxeditorjs.available_tools'] = 'Доступные инструменты';
$_lang['setting_mxeditorjs.available_tools_desc'] = 'Список всех блочных инструментов пакета. Используется как fallback, если профиль пуст, и как whitelist для профиля. Чтобы явно задать набор блоков, используйте mxeditorjs.enabled_tools или JSON в mxeditorjs.profiles.';
$_lang['setting_mxeditorjs.enabled_tools'] = 'Включённые инструменты';
$_lang['setting_mxeditorjs.enabled_tools_desc'] = 'Переопределение профиля: ключи инструментов через запятую. Пусто = инструменты из профиля. Пример: paragraph,header,list,embed,image';
$_lang['setting_mxeditorjs.profile'] = 'Профиль редактора';
$_lang['setting_mxeditorjs.profile_desc'] = 'Имя активного профиля (default, minimal, blog или свой).';
$_lang['setting_mxeditorjs.profiles'] = 'Определения профилей';
$_lang['setting_mxeditorjs.profiles_desc'] = 'JSON с доступными профилями и их инструментами.';
$_lang['setting_mxeditorjs.image_class_presets'] = 'Предустановки классов изображений';
$_lang['setting_mxeditorjs.image_class_presets_desc'] = 'JSON с CSS-классами для изображений. Формат: {"имя":"css_классы"}';
$_lang['setting_mxeditorjs.link_class_presets'] = 'Предустановки классов ссылок';
$_lang['setting_mxeditorjs.link_class_presets_desc'] = 'JSON с CSS-классами для ссылок. Формат: {"имя":"css_классы"}';
$_lang['setting_mxeditorjs.link_target_options'] = 'Опции атрибута target для ссылок';
$_lang['setting_mxeditorjs.link_target_options_desc'] = 'JSON с вариантами target. Формат: {"_blank":"Новое окно"}';
$_lang['setting_mxeditorjs.link_rel_options'] = 'Опции атрибута rel для ссылок';
$_lang['setting_mxeditorjs.link_rel_options_desc'] = 'JSON с вариантами атрибута rel для ссылок.';

$_lang['mxeditorjs_error_save'] = 'Ошибка сохранения контента.';
$_lang['mxeditorjs_error_load'] = 'Ошибка загрузки контента.';
$_lang['mxeditorjs_error_validation'] = 'Ошибка валидации контента.';
$_lang['mxeditorjs_error_upload'] = 'Ошибка загрузки файла.';
$_lang['mxeditorjs_error_permission'] = 'Доступ запрещён.';
$_lang['mxeditorjs_error_migration'] = 'Ошибка миграции контента.';
$_lang['mxeditorjs_error_init'] = 'Ошибка инициализации редактора.';
$_lang['mxeditorjs_error_resource_not_found'] = 'Ресурс не найден.';
$_lang['mxeditorjs_error_access_denied'] = 'У вас нет прав на редактирование этого ресурса.';

/* Editor UI */
$_lang['mxeditorjs_placeholder'] = 'Начните вводить текст...';
$_lang['mxeditorjs_upload_failed'] = 'Ошибка загрузки.';
$_lang['mxeditorjs_image_upload'] = 'Загрузить';
$_lang['mxeditorjs_image_upload_title'] = 'Загрузить изображение с устройства';
$_lang['mxeditorjs_image_browse'] = 'Обзор';
$_lang['mxeditorjs_image_browse_title'] = 'Выбрать из уже загруженных';
$_lang['mxeditorjs_loading'] = 'Загрузка...';
$_lang['mxeditorjs_uploading'] = 'Отправка...';
$_lang['mxeditorjs_root'] = 'Корень';
$_lang['mxeditorjs_root_title'] = 'Обзор с корня медиа-источника';
$_lang['mxeditorjs_back'] = '← Назад';
$_lang['mxeditorjs_no_files_found'] = 'Файлы не найдены';
$_lang['mxeditorjs_caption'] = 'Подпись';
$_lang['mxeditorjs_border'] = 'Рамка';
$_lang['mxeditorjs_stretch'] = 'Растянуть';
$_lang['mxeditorjs_background'] = 'Фон';
$_lang['mxeditorjs_style'] = 'Стиль:';
$_lang['mxeditorjs_custom_css'] = 'Свои CSS-классы';
$_lang['mxeditorjs_select_file'] = 'Выберите файл для загрузки';
$_lang['mxeditorjs_migration_title'] = 'Превью миграции HTML → Editor.js';
$_lang['mxeditorjs_migration_blocks_count'] = 'Блоков будет создано:';
$_lang['mxeditorjs_migration_html_size'] = 'Размер HTML:';
$_lang['mxeditorjs_migration_warning_overwrite'] = 'Текущее содержимое редактора будет перезаписано';
$_lang['mxeditorjs_migration_more_blocks'] = '...и ещё {{count}} блоков';
$_lang['mxeditorjs_cancel'] = 'Отмена';
$_lang['mxeditorjs_migrate_content'] = 'Выполнить миграцию';
$_lang['mxeditorjs_tool_image'] = 'Изображение';
$_lang['mxeditorjs_tool_gallery'] = 'Галерея';
$_lang['mxeditorjs_gallery_select_image'] = 'Выберите изображение';
$_lang['mxeditorjs_gallery_browse'] = 'Обзор';
$_lang['mxeditorjs_gallery_browse_title'] = 'Выбрать из уже загруженных';
$_lang['mxeditorjs_gallery_i18n_select'] = 'Выберите изображение';
$_lang['mxeditorjs_gallery_i18n_delete'] = 'Удалить';
$_lang['mxeditorjs_gallery_i18n_caption'] = 'Подпись галереи';
$_lang['mxeditorjs_gallery_i18n_fit'] = 'Сетка';
$_lang['mxeditorjs_gallery_i18n_slider'] = 'Слайдер';
$_lang['mxeditorjs_gallery_i18n_upload_error'] = 'Не удалось загрузить изображение. Попробуйте другое.';
