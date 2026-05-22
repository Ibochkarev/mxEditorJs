<?php
/**
 * mxEditorJs Plugin
 *
 * Registers Editor.js as a rich text editor in MODX 3 manager
 * and injects the editor assets on the resource form.
 *
 * Events: OnRichTextEditorRegister, OnDocFormPrerender, OnBeforeDocFormSave, OnResourceDelete
 *
 * @package mxeditorjs
 * @var \MODX\Revolution\modX $modx
 * @var array $scriptProperties
 */

if ($modx->event->name === 'OnRichTextEditorRegister') {
    $modx->event->output('mxEditorJs');
    return;
}

if ($modx->event->name === 'OnResourceDelete') {
    $resource = $modx->event->params['resource'] ?? null;
    if (!$resource) {
        return;
    }

    $corePath = $modx->getOption(
        'mxeditorjs.core_path',
        null,
        $modx->getOption('core_path') . 'components/mxeditorjs/'
    );

    $modx->addPackage('mxeditorjs', $corePath . 'model/');

    $resourceId = (int)$resource->get('id');

    $obj = $modx->getObject('MxEditorJsContent', [
        'resource_id' => $resourceId,
    ]);

    if ($obj) {
        if (!$obj->remove()) {
            $modx->log(
                modX::LOG_LEVEL_ERROR,
                '[mxEditorJs] Failed to remove sidecar content for resource ' . $resourceId
            );
        }
    }

    $tvContents = $modx->getCollection('MxEditorJsTvContent', [
        'resource_id' => $resourceId,
    ]);

    foreach ($tvContents as $tvContent) {
        if (!$tvContent->remove()) {
            $modx->log(
                modX::LOG_LEVEL_ERROR,
                '[mxEditorJs] Failed to remove TV sidecar content for resource ' . $resourceId
            );
        }
    }

    return;
}

if ($modx->event->name === 'OnBeforeDocFormSave') {
    $editorName = $modx->getOption('which_editor', null, '');
    if ($editorName !== 'mxEditorJs') {
        return;
    }

    $resource = $modx->event->params['resource'] ?? null;
    if (!$resource) {
        return;
    }

    $resourceId = (int)$resource->get('id');
    if ($resourceId <= 0) {
        return;
    }

    $corePath = $modx->getOption(
        'mxeditorjs.core_path',
        null,
        $modx->getOption('core_path') . 'components/mxeditorjs/'
    );

    require_once $corePath . 'bootstrap.php';

    $userId = (int)($modx->user ? $modx->user->get('id') : 0);

    $jsonRaw = $_POST['mxeditorjs_json'] ?? '';
    if (!empty($jsonRaw)) {
        $editorData = json_decode($jsonRaw, true);
        if (is_array($editorData) && !empty($editorData['blocks'])) {
            require_once $corePath . 'src/Repository/ContentRepository.php';
            $repo = new \MxEditorJs\Repository\ContentRepository($modx);
            $repo->save($resourceId, $editorData, $userId);
        }
    }

    foreach ($_POST as $key => $value) {
        if (!preg_match('/^mxeditorjs_tv_(\d+)_json$/', $key, $matches)) {
            continue;
        }

        $tmplvarId = (int)$matches[1];
        if ($tmplvarId <= 0 || empty($value)) {
            continue;
        }

        $tvEditorData = json_decode($value, true);
        if (!is_array($tvEditorData) || empty($tvEditorData['blocks'])) {
            continue;
        }

        require_once $corePath . 'src/Repository/TvContentRepository.php';
        $tvRepo = new \MxEditorJs\Repository\TvContentRepository($modx);
        $tvRepo->save($resourceId, $tmplvarId, $tvEditorData, $userId);
    }

    return;
}

if ($modx->event->name === 'OnDocFormPrerender') {
    if (!$modx->getOption('mxeditorjs.enabled', null, true)) {
        return;
    }

    if (!$modx->getOption('use_editor', null, true)) {
        return;
    }

    $editorName = $modx->getOption('which_editor', null, '');
    if ($editorName !== 'mxEditorJs') {
        return;
    }

    if (!$modx->controller || empty($modx->controller->resourceArray)) {
        return;
    }

    $assetsUrl = $modx->getOption(
        'mxeditorjs.assets_url',
        null,
        $modx->getOption('assets_url') . 'components/mxeditorjs/'
    );

    $connectorUrl = $assetsUrl . 'connector.php';
    $resourceId = (int)($modx->controller->resourceArray['id'] ?? 0);

    $jsPath = $modx->getOption('assets_path') . 'components/mxeditorjs/js/mxeditorjs.js';
    $version = file_exists($jsPath) ? filemtime($jsPath) : time();

    $profileName = $modx->getOption('mxeditorjs.profile', null, 'default');
    $profilesJson = $modx->getOption('mxeditorjs.profiles', null, '{}');
    $profiles = is_string($profilesJson) ? json_decode($profilesJson, true) : [];
    $profiles = is_array($profiles) ? $profiles : [];

    require_once $modx->getOption('core_path') . 'components/mxeditorjs/src/Config/EditorTools.php';
    $enabledTools = \MxEditorJs\Config\EditorTools::resolve($modx, (string) $profileName, $profiles);

    $imageClassPresets = json_decode(
        $modx->getOption('mxeditorjs.image_class_presets', null, '{}'),
        true
    ) ?: [];
    $linkClassPresets = json_decode(
        $modx->getOption('mxeditorjs.link_class_presets', null, '{}'),
        true
    ) ?: [];
    $linkTargetOptions = json_decode(
        $modx->getOption('mxeditorjs.link_target_options', null, '{}'),
        true
    ) ?: [];
    $linkRelOptions = json_decode(
        $modx->getOption('mxeditorjs.link_rel_options', null, '{}'),
        true
    ) ?: [];

    $galleryMaxCount = (int)$modx->getOption('mxeditorjs.gallery_max_count', null, 0);

    $modx->lexicon->load('mxeditorjs:default');
    $cultureKey = $modx->getOption('cultureKey', null, 'en');
    $i18nKeys = [
        'placeholder', 'upload_failed', 'image_upload', 'image_upload_title', 'image_browse', 'image_browse_title',
        'loading', 'uploading', 'root', 'root_title', 'back', 'no_files_found', 'caption', 'border', 'stretch',
        'background', 'style', 'custom_css', 'select_file', 'migration_title', 'migration_blocks_count',
        'migration_html_size', 'migration_warning_overwrite', 'migration_more_blocks', 'cancel', 'migrate_content',
        'tool_image', 'tool_gallery', 'gallery_select_image', 'gallery_browse', 'gallery_browse_title',
    ];
    $i18n = [];
    foreach ($i18nKeys as $key) {
        $i18n[$key] = $modx->lexicon('mxeditorjs_' . $key);
    }

    $galleryToolMessages = [
        'Select an Image' => $modx->lexicon('mxeditorjs_gallery_i18n_select'),
        'Delete' => $modx->lexicon('mxeditorjs_gallery_i18n_delete'),
        'Gallery caption' => $modx->lexicon('mxeditorjs_gallery_i18n_caption'),
        'Fit' => $modx->lexicon('mxeditorjs_gallery_i18n_fit'),
        'Slider' => $modx->lexicon('mxeditorjs_gallery_i18n_slider'),
        "Couldn\u{2019}t upload image. Please try another." => $modx->lexicon('mxeditorjs_gallery_i18n_upload_error'),
    ];

    $editorJsMessages = [
        'toolNames' => [
            'Image' => $modx->lexicon('mxeditorjs_tool_image'),
            'Gallery' => $modx->lexicon('mxeditorjs_tool_gallery'),
        ],
        'tools' => [
            'gallery' => $galleryToolMessages,
        ],
    ];

    $config = json_encode([
        'connectorUrl' => $connectorUrl,
        'resourceId' => $resourceId,
        'assetsUrl' => $assetsUrl,
        'profile' => $profileName,
        'enabledTools' => $enabledTools,
        'galleryMaxCount' => $galleryMaxCount,
        'presets' => [
            'imageClass' => $imageClassPresets,
            'linkClass' => $linkClassPresets,
            'linkTarget' => $linkTargetOptions,
            'linkRel' => $linkRelOptions,
        ],
        'locale' => $cultureKey,
        'i18n' => $i18n,
        'editorJsI18n' => ['messages' => $editorJsMessages],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $modx->controller->addCss($assetsUrl . 'css/mxeditorjs.css?v=' . $version);
    $modx->controller->addCss($assetsUrl . 'css/gallery-front.css?v=' . $version);

    $modx->controller->addHtml(
        '<script>window.mxEditorJsConfig = ' . $config . ';</script>'
    );

    $modx->controller->addLastJavascript($assetsUrl . 'js/mxeditorjs.js?v=' . $version);

}
