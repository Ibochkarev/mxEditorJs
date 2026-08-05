# mxEditorJs

Block-style content editor for MODX 3 based on Editor.js.

Version: 1.1.0-beta2

## Overview

mxEditorJs replaces the default resource RTE with a block editor.
Canonical JSON lives in sidecar tables (mxeditorjs_content, mxeditorjs_tv_content).
HTML snapshot goes to modResource.content or TV textarea for frontend output.

## Requirements

- MODX 3.0.3+
- PHP 8.2+

## Features

- 14 block types including Gallery (multi-image, sortable, fit/slider)
- Template Variable support (Textarea + Rich Text)
- Media upload and browse via MODX Media Sources
- Link autocomplete for MODX resources
- HTML-to-JSON migration (main content only)
- Tool profiles: default, minimal, blog, full
- Fullscreen mode and Source preview
- Russian and English localization

## Configuration

System settings namespace: mxeditorjs

Key settings:
- mxeditorjs.enabled
- mxeditorjs.profile / mxeditorjs.enabled_tools
- mxeditorjs.image_mediasource / mxeditorjs.file_mediasource
- mxeditorjs.gallery_max_count

Set which_editor = mxEditorJs in MODX system settings.

## Documentation

Full docs in the docs/ folder of the repository:
- docs/USER_GUIDE.md
- docs/CONFIGURATION.md
- docs/DEVELOPER.md
- docs/API.md

## License

MIT License
