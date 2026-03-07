<?php
/**
 * mxEditorJs Connector
 *
 * Handles content CRUD and media upload operations.
 *
 * @package mxeditorjs
 */

require_once dirname(__DIR__, 3) . '/config.core.php';
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
require_once MODX_CONNECTORS_PATH . 'index.php';

/** @var \MODX\Revolution\modX $modx */
$modx->switchContext('mgr');

$cultureKey = $modx->getOption('cultureKey', null, 'en');
if ($modx->user && $modx->user->get('id')) {
    $sessionKey = $_SESSION['modx.user.session']['cultureKey']
        ?? $_SESSION['cultureKey']
        ?? null;
    if ($sessionKey) {
        $cultureKey = $sessionKey;
        $modx->setOption('cultureKey', $cultureKey);
    }
}
$modx->lexicon->load('mxeditorjs:default');

$corePath = $modx->getOption(
    'mxeditorjs.core_path',
    null,
    $modx->getOption('core_path') . 'components/mxeditorjs/'
);

require_once $corePath . 'bootstrap.php';
require_once $corePath . 'src/Repository/ContentRepository.php';
require_once $corePath . 'src/Repository/TvContentRepository.php';
require_once $corePath . 'src/Validator/ContentValidator.php';
require_once $corePath . 'src/Renderer/HtmlRenderer.php';
require_once $corePath . 'src/Service/MediaUploader.php';
require_once $corePath . 'src/Service/HtmlMigrator.php';

$action = $_REQUEST['action'] ?? '';

header('Content-Type: application/json; charset=utf-8');

if (!$modx->user || !$modx->user->get('id')) {
    echo json_encode([
        'success' => false,
        'message' => $modx->lexicon('mxeditorjs_error_permission'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$modx->user->get('id');

/**
 * Assert user has access to the resource.
 * Returns the resource object on success, exits with JSON error on failure.
 */
function assertResourceAccess(\MODX\Revolution\modX $modx, int $resourceId, bool $requireSave = false): ?\MODX\Revolution\modResource
{
    if ($resourceId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => $modx->lexicon('mxeditorjs_error_resource_not_found'),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $resource = $modx->getObject(\MODX\Revolution\modResource::class, $resourceId);
    if (!$resource) {
        echo json_encode([
            'success' => false,
            'message' => $modx->lexicon('mxeditorjs_error_resource_not_found'),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($requireSave && !$modx->hasPermission('save_document')) {
        echo json_encode([
            'success' => false,
            'message' => $modx->lexicon('mxeditorjs_error_access_denied'),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    return $resource;
}

switch ($action) {
    case 'content/get':
        handleContentGet($modx, $corePath);
        break;

    case 'content/save':
        handleContentSave($modx, $corePath, $userId);
        break;

    case 'media/upload':
        handleMediaUpload($modx, $corePath);
        break;

    case 'media/uploadFile':
        handleMediaUploadFile($modx, $corePath);
        break;

    case 'link/search':
        handleLinkSearch($modx);
        break;

    case 'media/browse':
        handleMediaBrowse($modx, $corePath);
        break;

    case 'content/migrate':
        handleContentMigrate($modx, $corePath, $userId);
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Unknown action: ' . $action,
        ], JSON_UNESCAPED_UNICODE);
}

function handleContentGet(\MODX\Revolution\modX $modx, string $corePath): void
{
    $resourceId = (int)($_REQUEST['resource_id'] ?? 0);
    $tmplvarId = (int)($_REQUEST['tmplvar_id'] ?? 0);
    assertResourceAccess($modx, $resourceId, false);

    if ($tmplvarId > 0) {
        $repo = new \MxEditorJs\Repository\TvContentRepository($modx);
        $row = $repo->findByResourceAndTv($resourceId, $tmplvarId);
    } else {
        $repo = new \MxEditorJs\Repository\ContentRepository($modx);
        $row = $repo->findByResourceId($resourceId);
    }

    if (!$row) {
        echo json_encode([
            'success' => true,
            'data' => null,
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    $json = $row['content_json'];
    $decoded = is_string($json) ? json_decode($json, true) : $json;

    echo json_encode([
        'success' => true,
        'data' => [
            'content_json' => $decoded,
            'content_version' => $row['content_version'] ?? 1,
        ],
    ], JSON_UNESCAPED_UNICODE);
}

function handleContentSave(\MODX\Revolution\modX $modx, string $corePath, int $userId): void
{
    $resourceId = (int)($_REQUEST['resource_id'] ?? 0);
    $tmplvarId = (int)($_REQUEST['tmplvar_id'] ?? 0);
    $resource = assertResourceAccess($modx, $resourceId, true);

    $contentRaw = $_REQUEST['content_json'] ?? '';
    $editorData = is_string($contentRaw) ? json_decode($contentRaw, true) : $contentRaw;

    if (!is_array($editorData)) {
        echo json_encode([
            'success' => false,
            'message' => $modx->lexicon('mxeditorjs_error_validation'),
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    $validator = new \MxEditorJs\Validator\ContentValidator();

    if (!$validator->validate($editorData)) {
        echo json_encode([
            'success' => false,
            'message' => $modx->lexicon('mxeditorjs_error_validation') . ': ' . $validator->getFirstError(),
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    $renderer = new \MxEditorJs\Renderer\HtmlRenderer();
    $htmlSnapshot = $renderer->render($editorData);

    if ($tmplvarId > 0) {
        $repo = new \MxEditorJs\Repository\TvContentRepository($modx);
        $saved = $repo->save($resourceId, $tmplvarId, $editorData, $userId);
    } else {
        $repo = new \MxEditorJs\Repository\ContentRepository($modx);
        $saved = $repo->save($resourceId, $editorData, $userId);

        if ($saved) {
            $resource->set('content', $htmlSnapshot);
            $resource->save();
        }
    }

    if (!$saved) {
        echo json_encode([
            'success' => false,
            'message' => $modx->lexicon('mxeditorjs_error_save'),
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'html' => $htmlSnapshot,
        ],
    ], JSON_UNESCAPED_UNICODE);
}

function handleMediaUpload(\MODX\Revolution\modX $modx, string $corePath): void
{
    $resourceId = (int)($_REQUEST['resource_id'] ?? 0);
    assertResourceAccess($modx, $resourceId, true);

    $file = $_FILES['image'] ?? null;

    if (!$file) {
        echo json_encode([
            'success' => 0,
            'message' => $modx->lexicon('mxeditorjs_error_upload'),
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    try {
        $uploader = new \MxEditorJs\Service\MediaUploader($modx);
        $result = $uploader->upload($file, $resourceId);
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (\Throwable $e) {
        $modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[mxEditorJs] Upload error: ' . $e->getMessage());
        echo json_encode([
            'success' => 0,
            'message' => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE);
    }
}

function handleMediaUploadFile(\MODX\Revolution\modX $modx, string $corePath): void
{
    $resourceId = (int)($_REQUEST['resource_id'] ?? 0);
    assertResourceAccess($modx, $resourceId, true);

    $file = $_FILES['file'] ?? null;

    if (!$file) {
        echo json_encode([
            'success' => 0,
            'message' => $modx->lexicon('mxeditorjs_error_upload'),
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    try {
        $uploader = new \MxEditorJs\Service\MediaUploader($modx);
        $result = $uploader->uploadFile($file, $resourceId);
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (\Throwable $e) {
        $modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[mxEditorJs] File upload error: ' . $e->getMessage());
        echo json_encode([
            'success' => 0,
            'message' => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE);
    }
}

function handleMediaBrowse(\MODX\Revolution\modX $modx, string $corePath): void
{
    $resourceId = (int)($_REQUEST['resource_id'] ?? 0);
    $type = $_REQUEST['type'] ?? 'image';
    $path = $_REQUEST['path'] ?? '';

    try {
        $uploader = new \MxEditorJs\Service\MediaUploader($modx);
        $files = $uploader->browse($resourceId, $type, $path);
        echo json_encode([
            'success' => true,
            'data' => $files,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (\Throwable $e) {
        $modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[mxEditorJs] Browse error: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'data' => [],
        ], JSON_UNESCAPED_UNICODE);
    }
}

function handleLinkSearch(\MODX\Revolution\modX $modx): void
{
    $query = trim($_REQUEST['query'] ?? '');

    if (mb_strlen($query) < 2) {
        echo json_encode([
            'success' => true,
            'data' => [],
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    $limit = min((int)($_REQUEST['limit'] ?? 10), 30);

    $c = $modx->newQuery(\MODX\Revolution\modResource::class);
    $c->where([
        'pagetitle:LIKE' => '%' . $query . '%',
        'OR:longtitle:LIKE' => '%' . $query . '%',
        'OR:id:=' => is_numeric($query) ? (int)$query : 0,
    ]);
    $c->where(['deleted' => false]);
    $c->select(['id', 'pagetitle', 'longtitle', 'uri', 'published', 'context_key']);
    $c->sortby('pagetitle', 'ASC');
    $c->limit($limit);

    $resources = $modx->getIterator(\MODX\Revolution\modResource::class, $c);
    $results = [];

    foreach ($resources as $resource) {
        $results[] = [
            'id' => $resource->get('id'),
            'pagetitle' => $resource->get('pagetitle'),
            'longtitle' => $resource->get('longtitle'),
            'uri' => $resource->get('uri'),
            'published' => (bool)$resource->get('published'),
            'context_key' => $resource->get('context_key'),
            'url' => $modx->makeUrl($resource->get('id'), '', '', 'full'),
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $results,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function handleContentMigrate(\MODX\Revolution\modX $modx, string $corePath, int $userId): void
{
    $resourceId = (int)($_REQUEST['resource_id'] ?? 0);
    $dryRun = filter_var($_REQUEST['dry_run'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $confirmed = filter_var($_REQUEST['confirmed'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $forceOverwrite = filter_var($_REQUEST['force'] ?? false, FILTER_VALIDATE_BOOLEAN);

    $resource = assertResourceAccess($modx, $resourceId, !$dryRun);

    $repo = new \MxEditorJs\Repository\ContentRepository($modx);
    $existing = $repo->findByResourceId($resourceId);

    if ($existing && !$forceOverwrite && !$dryRun) {
        echo json_encode([
            'success' => true,
            'data' => [
                'skipped' => true,
                'reason' => 'sidecar_exists',
                'requires_confirmation' => true,
                'message' => 'Editor content already exists. Use force=true to overwrite.',
            ],
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    $htmlContent = $resource->get('content');
    if (empty(trim($htmlContent))) {
        echo json_encode([
            'success' => true,
            'data' => [
                'skipped' => true,
                'reason' => 'empty_content',
                'message' => 'Resource content is empty',
            ],
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    $migrator = new \MxEditorJs\Service\HtmlMigrator();

    try {
        $editorData = $migrator->convert($htmlContent);

        if ($dryRun) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'dry_run' => true,
                    'preview' => $editorData,
                    'blocks_count' => count($editorData['blocks'] ?? []),
                    'html_length' => strlen($htmlContent),
                    'has_existing' => $existing !== null,
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        if (!$confirmed && $existing) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'requires_confirmation' => true,
                    'preview' => $editorData,
                    'blocks_count' => count($editorData['blocks'] ?? []),
                    'message' => 'Migration will overwrite existing content. Send confirmed=true to proceed.',
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        $saved = $repo->save($resourceId, $editorData, $userId);

        if (!$saved) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to save migrated content',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'migrated' => true,
                'blocks_count' => count($editorData['blocks'] ?? []),
                'overwritten' => $existing !== null,
            ],
        ], JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        $modx->log(\MODX\Revolution\modX::LOG_LEVEL_ERROR, '[mxEditorJs] Migration error: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Migration failed: ' . $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE);
    }
}
