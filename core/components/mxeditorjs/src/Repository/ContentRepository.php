<?php

declare(strict_types=1);

namespace MxEditorJs\Repository;

use MODX\Revolution\modX;

class ContentRepository
{
    private modX $modx;

    public function __construct(modX $modx)
    {
        $this->modx = $modx;
    }

    public function findByResourceId(int $resourceId): ?array
    {
        $obj = $this->modx->getObject('MxEditorJsContent', [
            'resource_id' => $resourceId,
        ]);

        if (!$obj) {
            return null;
        }

        return $obj->toArray();
    }

    public function save(int $resourceId, array $jsonData, int $userId = 0): bool
    {
        $obj = $this->modx->getObject('MxEditorJsContent', [
            'resource_id' => $resourceId,
        ]);

        $isNew = false;
        if (!$obj) {
            $obj = $this->modx->newObject('MxEditorJsContent');
            $obj->set('resource_id', $resourceId);
            $obj->set('created_at', date('Y-m-d H:i:s'));
            $obj->set('created_by', $userId);
            $isNew = true;
        }

        $encoded = json_encode($jsonData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $hash = hash('sha256', $encoded);

        $currentHash = $obj->get('content_hash');
        if (!$isNew && $currentHash === $hash) {
            return true;
        }

        $obj->set('content_json', $encoded);
        $obj->set('content_hash', $hash);
        $obj->set('content_version', ($obj->get('content_version') ?? 0) + 1);
        $obj->set('schema_version', $jsonData['version'] ?? '2.31');
        $obj->set('updated_at', date('Y-m-d H:i:s'));
        $obj->set('updated_by', $userId);

        return $obj->save();
    }

    public function deleteByResourceId(int $resourceId): bool
    {
        $obj = $this->modx->getObject('MxEditorJsContent', [
            'resource_id' => $resourceId,
        ]);

        if (!$obj) {
            return true;
        }

        return $obj->remove();
    }
}
