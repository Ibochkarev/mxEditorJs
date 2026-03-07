<?php

declare(strict_types=1);

namespace MxEditorJs\Validator;

/**
 * Validates Editor.js content structure before saving.
 */
class ContentValidator
{
    private const ALLOWED_BLOCK_TYPES = [
        'paragraph',
        'header',
        'list',
        'image',
        'attaches',
        'embed',
        'delimiter',
        'quote',
        'code',
        'raw',
        'table',
        'warning',
        'checklist',
    ];

    private array $errors = [];

    /**
     * Validate Editor.js data structure.
     *
     * @param array $data Editor.js output data
     * @return bool True if valid, false otherwise
     */
    public function validate(array $data): bool
    {
        $this->errors = [];

        if (!isset($data['blocks'])) {
            $this->errors[] = 'Missing required field: blocks';
            return false;
        }

        if (!is_array($data['blocks'])) {
            $this->errors[] = 'Field "blocks" must be an array';
            return false;
        }

        foreach ($data['blocks'] as $index => $block) {
            $this->validateBlock($block, $index);
        }

        return empty($this->errors);
    }

    /**
     * Validate a single block.
     */
    private function validateBlock(mixed $block, int $index): void
    {
        if (!is_array($block)) {
            $this->errors[] = "Block at index {$index} must be an object";
            return;
        }

        if (!isset($block['type'])) {
            $this->errors[] = "Block at index {$index} is missing required field: type";
            return;
        }

        if (!is_string($block['type'])) {
            $this->errors[] = "Block type at index {$index} must be a string";
            return;
        }

        if (!in_array($block['type'], self::ALLOWED_BLOCK_TYPES, true)) {
            $this->errors[] = "Block type '{$block['type']}' at index {$index} is not allowed";
            return;
        }

        if (!isset($block['data'])) {
            $this->errors[] = "Block at index {$index} is missing required field: data";
            return;
        }

        if (!is_array($block['data'])) {
            $this->errors[] = "Block data at index {$index} must be an object";
            return;
        }
    }

    /**
     * Get validation errors.
     *
     * @return array List of error messages
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message.
     *
     * @return string|null First error or null if no errors
     */
    public function getFirstError(): ?string
    {
        return $this->errors[0] ?? null;
    }
}
