<?php
/**
 * Resolves which Editor.js block tools are enabled for the manager.
 *
 * @package mxeditorjs
 */

namespace MxEditorJs\Config;

use MODX\Revolution\modX;

class EditorTools
{
    public const DEFAULT_AVAILABLE = 'paragraph,header,list,checklist,quote,table,code,raw,embed,image,gallery,attaches,delimiter,warning';

    /** @var array<string, array{tools: string[]}> */
    public const PACKAGE_PROFILES = [
        'default' => [
            'tools' => [
                'paragraph', 'header', 'list', 'checklist', 'quote', 'table', 'code', 'raw', 'embed',
                'image', 'gallery', 'attaches', 'delimiter', 'warning',
            ],
        ],
        'minimal' => [
            'tools' => ['paragraph', 'header', 'list', 'image'],
        ],
        'blog' => [
            'tools' => ['paragraph', 'header', 'list', 'quote', 'image', 'gallery', 'embed', 'delimiter'],
        ],
        'full' => [
            'tools' => [
                'paragraph', 'header', 'list', 'checklist', 'quote', 'table', 'code', 'raw', 'embed',
                'image', 'gallery', 'attaches', 'delimiter', 'warning',
            ],
        ],
    ];

    /**
     * @return string[]
     */
    public static function parseList(string $csv): array
    {
        if ($csv === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $csv))));
    }

    /**
     * @param array<string, mixed> $storedProfiles
     *
     * @return string[]
     */
    public static function resolve(modX $modx, string $profileName, array $storedProfiles): array
    {
        $override = trim((string) $modx->getOption('mxeditorjs.enabled_tools', null, ''));
        if ($override !== '') {
            return self::parseList($override);
        }

        $available = self::parseList(
            (string) $modx->getOption('mxeditorjs.available_tools', null, self::DEFAULT_AVAILABLE)
        );
        if ($available === []) {
            $available = self::parseList(self::DEFAULT_AVAILABLE);
        }

        $profileTools = $storedProfiles[$profileName]['tools'] ?? [];
        if (!is_array($profileTools)) {
            $profileTools = [];
        }

        if ($profileTools === []) {
            return $available;
        }

        $enabled = [];
        foreach ($profileTools as $tool) {
            if (!is_string($tool)) {
                continue;
            }
            if (in_array($tool, $available, true)) {
                $enabled[] = $tool;
            }
        }

        // After package upgrades the DB profile JSON may lag behind shipped defaults.
        $packageProfileTools = self::PACKAGE_PROFILES[$profileName]['tools'] ?? null;
        if (is_array($packageProfileTools)) {
            foreach ($packageProfileTools as $tool) {
                if (in_array($tool, $available, true) && !in_array($tool, $enabled, true)) {
                    $enabled[] = $tool;
                }
            }
        }

        return $enabled;
    }

    /**
     * Adds missing shipped profile tools (e.g. gallery after 1.0.3).
     *
     * @param array<string, mixed> $profiles
     *
     * @return array<string, mixed>
     */
    public static function migrateProfiles(array $profiles): array
    {
        foreach (['default', 'full', 'blog'] as $name) {
            if (!isset(self::PACKAGE_PROFILES[$name]['tools'])) {
                continue;
            }

            $current = $profiles[$name]['tools'] ?? [];
            if (!is_array($current)) {
                $current = [];
            }

            foreach (self::PACKAGE_PROFILES[$name]['tools'] as $tool) {
                if (!in_array($tool, $current, true)) {
                    $current[] = $tool;
                }
            }

            $profiles[$name]['tools'] = array_values($current);
        }

        return $profiles;
    }

    /**
     * Ensures available_tools contains gallery after upgrade from pre-1.0.3 builds.
     */
    public static function migrateAvailableTools(string $csv): string
    {
        $tools = self::parseList($csv);
        if ($tools === []) {
            return self::DEFAULT_AVAILABLE;
        }

        if (!in_array('gallery', $tools, true)) {
            $tools[] = 'gallery';
        }

        return implode(',', $tools);
    }
}
