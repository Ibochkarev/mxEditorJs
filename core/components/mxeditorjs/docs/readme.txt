# mxEditorJs

Block-style content editor for MODX 3 based on Editor.js.

## Overview

mxEditorJs replaces the default resource content RTE with a modern block editor.
Content is stored as canonical JSON in a sidecar table with an HTML snapshot
rendered to `modResource.content` for frontend compatibility.

## Requirements

- MODX 3.0.3+
- PHP 8.2+

## Features

- Block-based editing with Editor.js
- Paragraph, Header, List, Quote, Code, Table, Image, Delimiter blocks
- Smart internal linking with resource autocomplete
- Media upload integration with MODX Media Sources
- HTML-to-JSON migration for existing content
- Fullscreen editing mode
- Source preview (read-only HTML view)

## Installation

Install via MODX Package Manager or manually place files and run the resolver.

## Configuration

System settings are available under the `mxeditorjs` namespace:

- `mxeditorjs.enabled` — Enable/disable the editor
- `mxeditorjs.image_mediasource` — Media source ID for images
- `mxeditorjs.image_upload_path` — Upload path pattern
- `mxeditorjs.allowed_image_types` — Allowed image extensions
- `mxeditorjs.max_upload_size` — Maximum file size in bytes

## Documentation

See PRD-mxEditorJs.md and TechSpec-mxEditorJs.md in the project root.

## License

MIT License
