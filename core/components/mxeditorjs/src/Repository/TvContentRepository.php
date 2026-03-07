<?php

declare(strict_types=1);

namespace MxEditorJs\Repository;

use MODX\Revolution\modX;

class TvContentRepository
{
    private modX $modx;

    public function __construct(modX $modx)
    {
        $this->modx = $modx;
    }

    public function findByResourceAndTv(int $resourceId, int $tmplvarId): ?array
    {
        $obj = $this->modx->getObject('MxEditorJsTvContent', [
            'resource_id' => $resourceId,
            'tmplvar_id' => $tmplvarId,
        ]);

        if (!$obj) {
            return null;
        }

        return $obj->toArray();
    }

    public function save(int $resourceId, int $tmplvarId, array $jsonData, int $userId = 0): bool
    {
        $obj = $this->modx->getObject('MxEditorJsTvContent', [
            'resource_id' => $resourceId,
            'tmplvar_id' => $tmplvarId,
        ]);

        $isNew = false;
        if (!$obj) {
            $obj = $this->modx->newObject('MxEditorJsTvContent');
            $obj->set('resource_id', $resourceId);
            $obj->set('tmplvar_id', $tmplvarId);
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

    public function deleteByResourceAndTv(int $resourceId, int $tmplvarId): bool
    {
        $obj = $this->modx->getObject('MxEditorJsTvContent', [
            'resource_id' => $resourceId,
            'tmplvar_id' => $tmplvarId,
        ]);

        if (!$obj) {
            return true;
        }

        return $obj->remove();
    }

    public function deleteByResourceId(int $resourceId): bool
    {
        $collection = $this->modx->getCollection('MxEditorJsTvContent', [
            'resource_id' => $resourceId,
        ]);

        foreach ($collection as $obj) {
            $obj->remove();
        }

        return true;
    }
}
