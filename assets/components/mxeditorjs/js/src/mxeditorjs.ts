import EditorJS, { type OutputData } from '@editorjs/editorjs';
import Header from './tools/HeaderTool';
import NestedList from '@editorjs/list';
import Paragraph from './tools/ParagraphTool';
import Delimiter from '@editorjs/delimiter';
import Quote from '@editorjs/quote';
import CodeTool from '@editorjs/code';
import RawTool from '@editorjs/raw';
import Table from '@editorjs/table';
import AttachesTool from './tools/AttachesTool';
import Embed from '@editorjs/embed';
import Marker from '@editorjs/marker';
import InlineCode from '@editorjs/inline-code';
import Underline from '@editorjs/underline';
import Warning from '@editorjs/warning';
import Checklist from './tools/ChecklistTool';
import AlignmentTuneTool from 'editorjs-text-alignment-blocktune';
import Undo from 'editorjs-undo';
import Sortable from 'sortablejs';
import ImageTool from './tools/ImageTool';
import GalleryTool from './tools/GalleryTool';
import LinkAutocomplete from './tools/LinkAutocomplete';

interface PresetsConfig {
  imageClass?: Record<string, string>;
  linkClass?: Record<string, string>;
  linkTarget?: Record<string, string>;
  linkRel?: Record<string, string>;
}

export interface MxEditorJsI18n {
  placeholder?: string;
  upload_failed?: string;
  image_upload?: string;
  image_upload_title?: string;
  image_browse?: string;
  image_browse_title?: string;
  loading?: string;
  uploading?: string;
  root?: string;
  root_title?: string;
  back?: string;
  no_files_found?: string;
  caption?: string;
  border?: string;
  stretch?: string;
  background?: string;
  style?: string;
  custom_css?: string;
  select_file?: string;
  migration_title?: string;
  migration_blocks_count?: string;
  migration_html_size?: string;
  migration_warning_overwrite?: string;
  migration_more_blocks?: string;
  cancel?: string;
  migrate_content?: string;
  tool_image?: string;
  tool_gallery?: string;
  gallery_select_image?: string;
  gallery_browse?: string;
  gallery_browse_title?: string;
}

interface MxEditorJsConfig {
  connectorUrl: string;
  resourceId: number;
  assetsUrl: string;
  tmplvarId?: number;
  profile?: string;
  enabledTools?: string[];
  presets?: PresetsConfig;
  locale?: string;
  i18n?: MxEditorJsI18n;
  editorJsI18n?: { messages?: Record<string, Record<string, string>> };
  /** Max images per gallery block; 0 = unlimited */
  galleryMaxCount?: number;
}

interface MigrationDryRunResult {
  dry_run?: boolean;
  skipped?: boolean;
  reason?: string;
  preview?: { blocks: Array<{ type: string; data: unknown }> };
  blocks_count?: number;
  html_length?: number;
  has_existing?: boolean;
  requires_confirmation?: boolean;
  message?: string;
}

declare global {
  interface Window {
    mxEditorJsConfig?: MxEditorJsConfig;
    MODx?: any;
  }
}

class MxEditorJsApp {
  private editor: EditorJS | null = null;
  private config: MxEditorJsConfig;
  private holderId = 'mxeditorjs-holder';
  private loaded = false;
  private sourcePreviewVisible = false;
  private fullscreenActive = false;
  private sourcePreviewEl: HTMLElement | null = null;
  private toolbarEl: HTMLElement | null = null;
  private wrapperEl: HTMLElement | null = null;
  private textareaId = '';
  private textarea: HTMLTextAreaElement | null = null;
  private cachedHtml = '';
  private cachedJsonString = '';
  private jsonField: HTMLInputElement | null = null;
  private syncTimer: ReturnType<typeof setTimeout> | null = null;
  private handleKeyDown: ((e: KeyboardEvent) => void) | null = null;
  private tmplvarId: number | null = null;
  private instanceHolderId: string = '';

  constructor(config: MxEditorJsConfig) {
    this.config = config;
    this.tmplvarId = config.tmplvarId ?? null;
  }

  async initForElement(elementId: string): Promise<void> {
    this.textareaId = elementId;

    const textarea = document.getElementById(elementId) as HTMLTextAreaElement | null;
    if (!textarea) {
      console.warn(`[mxEditorJs] Element #${elementId} not found in DOM`);
      return;
    }

    this.textarea = textarea;
    this.cachedHtml = textarea.value;

    this.tmplvarId = this.detectTmplvarId(textarea);

    this.instanceHolderId = this.tmplvarId
      ? `mxeditorjs-holder-tv-${this.tmplvarId}`
      : this.holderId;

    this.injectHolder(textarea);
    this.injectToolbar();
    this.createJsonField(textarea);

    try {
      const initialData = await this.loadContent();
      this.createEditor(initialData, textarea);
      this.hookBeforeSubmit();
      this.registerKeyboardShortcuts();
    } catch (err) {
      console.error('[mxEditorJs] Init failed:', err);
      this.restoreFallback(textarea);
    }
  }

  private detectTmplvarId(textarea: HTMLTextAreaElement): number | null {
    const name = textarea.name || '';
    const id = textarea.id || '';

    let match = name.match(/^tv\[(\d+)\]$/);
    if (match) {
      return parseInt(match[1], 10);
    }

    match = name.match(/^tv(\d+)$/);
    if (match) {
      return parseInt(match[1], 10);
    }

    match = id.match(/^tv(\d+)$/);
    if (match) {
      return parseInt(match[1], 10);
    }

    return null;
  }

  private restoreFallback(textarea: HTMLTextAreaElement): void {
    if (this.wrapperEl) {
      this.wrapperEl.remove();
      this.wrapperEl = null;
    }

    if (this.toolbarEl) {
      this.toolbarEl.remove();
      this.toolbarEl = null;
    }

    textarea.style.display = '';
    this.loaded = false;
    this.editor = null;
  }

  private injectHolder(textarea: HTMLTextAreaElement): void {
    const wrapper = document.createElement('div');
    wrapper.className = 'mxeditorjs-wrapper';
    this.wrapperEl = wrapper;

    const holder = document.createElement('div');
    holder.id = this.instanceHolderId;
    holder.className = 'mxeditorjs-container';
    wrapper.appendChild(holder);

    const sourcePreview = document.createElement('pre');
    sourcePreview.className = 'mxeditorjs-source-preview';
    sourcePreview.style.display = 'none';
    this.sourcePreviewEl = sourcePreview;
    wrapper.appendChild(sourcePreview);

    const parent = textarea.parentNode;
    if (parent) {
      parent.insertBefore(wrapper, textarea);
    }

    textarea.style.display = 'none';
  }

  private injectToolbar(): void {
    const toolbar = document.createElement('div');
    toolbar.className = 'mxeditorjs-toolbar';

    const sourceButton = document.createElement('button');
    sourceButton.type = 'button';
    sourceButton.className = 'mxeditorjs-toolbar__button';
    sourceButton.dataset.action = 'source';
    sourceButton.dataset.shortcut = 'Ctrl+U';
    sourceButton.textContent = 'Source';
    sourceButton.title = 'Toggle HTML source preview (Ctrl+U)';
    sourceButton.addEventListener('click', () => this.toggleSourcePreview());

    const fullscreenButton = document.createElement('button');
    fullscreenButton.type = 'button';
    fullscreenButton.className = 'mxeditorjs-toolbar__button';
    fullscreenButton.dataset.action = 'fullscreen';
    fullscreenButton.dataset.shortcut = 'F11';
    fullscreenButton.textContent = 'Fullscreen';
    fullscreenButton.title = 'Toggle fullscreen editing (F11)';
    fullscreenButton.addEventListener('click', () => this.toggleFullscreen());

    toolbar.appendChild(sourceButton);
    toolbar.appendChild(fullscreenButton);
    this.toolbarEl = toolbar;

    const holder = document.getElementById(this.instanceHolderId);
    if (holder?.parentNode) {
      holder.parentNode.insertBefore(toolbar, holder);
    }
  }

  private async loadContent(): Promise<OutputData | undefined> {
    if (this.config.resourceId <= 0) {
      return undefined;
    }

    try {
      const form = new FormData();
      form.append('action', 'content/get');
      form.append('resource_id', String(this.config.resourceId));
      if (this.tmplvarId) {
        form.append('tmplvar_id', String(this.tmplvarId));
      }

      const response = await fetch(this.config.connectorUrl, {
        method: 'POST',
        body: form,
      });

      const result = await response.json();
      if (result.success && result.data?.content_json) {
        return this.normalizeContent(result.data.content_json as OutputData);
      }

      if (result.success && result.data === null) {
        if (!this.tmplvarId) {
          return await this.tryMigrateContent();
        }
        return undefined;
      }
    } catch (e) {
      console.error('[mxEditorJs] Failed to load content:', e);
    }

    return undefined;
  }

  /**
   * Normalizes block data to match expected tool formats.
   * Fixes image blocks with non-standard structure (e.g. url at top level).
   */
  private normalizeContent(data: OutputData): OutputData {
    if (!data?.blocks?.length) return data;

    const blocks = data.blocks.filter((block) => {
      const d = block.data as Record<string, unknown> | undefined;
      if (!d) {
        return block.type === 'delimiter';
      }

      if (block.type === 'paragraph') {
        block.data = {
          text: typeof d.text === 'string' ? d.text : '',
        };
        return true;
      }

      if (block.type === 'header') {
        const level = typeof d.level === 'number' ? d.level : Number(d.level);
        block.data = {
          text: typeof d.text === 'string' ? d.text : '',
          level: Number.isFinite(level) && level >= 1 && level <= 6 ? level : 2,
        };
        return true;
      }

      if (block.type === 'checklist') {
        const rawItems = Array.isArray(d.items) ? d.items : [];
        const items = rawItems.map((item) => {
          const entry = item && typeof item === 'object' ? (item as Record<string, unknown>) : {};
          return {
            text: typeof entry.text === 'string' ? entry.text : '',
            checked: entry.checked === true,
          };
        });
        block.data = { items: items.length > 0 ? items : [{ text: '', checked: false }] };
        return true;
      }

      if (block.type === 'list') {
        block.data = {
          style: d.style === 'ordered' ? 'ordered' : 'unordered',
          items: Array.isArray(d.items) ? d.items : [],
        };
        return true;
      }

      if (block.type === 'quote') {
        block.data = {
          text: typeof d.text === 'string' ? d.text : '',
          caption: typeof d.caption === 'string' ? d.caption : '',
          alignment: typeof d.alignment === 'string' ? d.alignment : 'left',
        };
        return true;
      }

      if (block.type === 'warning') {
        block.data = {
          title: typeof d.title === 'string' ? d.title : '',
          message: typeof d.message === 'string' ? d.message : '',
        };
        return true;
      }

      if (block.type === 'code') {
        block.data = {
          code: typeof d.code === 'string' ? d.code : '',
        };
        return true;
      }

      if (block.type === 'raw') {
        block.data = {
          html: typeof d.html === 'string' ? d.html : '',
        };
        return true;
      }

      if (block.type === 'table') {
        block.data = {
          withHeadings: d.withHeadings === true,
          content: Array.isArray(d.content) ? d.content : [],
        };
        return true;
      }

      if (block.type === 'embed') {
        if (typeof d.embed !== 'string' || !d.embed.trim()) {
          return false;
        }
        block.data = {
          service: typeof d.service === 'string' ? d.service : '',
          source: typeof d.source === 'string' ? d.source : '',
          embed: d.embed.trim(),
          width: typeof d.width === 'number' ? d.width : undefined,
          height: typeof d.height === 'number' ? d.height : undefined,
          caption: typeof d.caption === 'string' ? d.caption : '',
        };
        return true;
      }

      if (block.type === 'image') {
        const url =
          (d.file as { url?: string })?.url ??
          (typeof d.url === 'string' ? d.url : null) ??
          (typeof d.file === 'string' ? d.file : null);

        if (!url || typeof url !== 'string' || !url.trim()) {
          console.warn('[mxEditorJs] Image block skipped: no valid URL');
          return false;
        }

        block.data = {
          file: {
            url: url.trim(),
            name: (d.file as { name?: string })?.name,
            size: (d.file as { size?: number })?.size,
          },
          caption: d.caption ?? '',
          withBorder: d.withBorder ?? false,
          stretched: d.stretched ?? false,
          withBackground: d.withBackground ?? false,
        };
        return true;
      }

      if (block.type === 'attaches') {
        const file = (d.file as Record<string, unknown> | undefined) ?? {};
        const url =
          (typeof file.url === 'string' ? file.url : null) ??
          (typeof d.url === 'string' ? d.url : null);

        if (!url || !url.trim()) {
          return false;
        }

        block.data = {
          file: {
            url: url.trim(),
            name: typeof file.name === 'string' ? file.name : undefined,
            size: typeof file.size === 'number' ? file.size : undefined,
            extension: typeof file.extension === 'string' ? file.extension : undefined,
          },
          title: typeof d.title === 'string' ? d.title : '',
        };
        return true;
      }

      if (block.type === 'gallery') {
        const rawFiles = Array.isArray(d.files) ? d.files : [];
        const files: Array<{ url: string; name?: string; size?: number }> = [];
        for (const item of rawFiles) {
          if (!item || typeof item !== 'object') {
            continue;
          }
          const entry = item as Record<string, unknown>;
          const url =
            (typeof entry.url === 'string' ? entry.url : null) ??
            (typeof (entry.file as { url?: string } | undefined)?.url === 'string'
              ? (entry.file as { url: string }).url
              : null);
          if (!url || !url.trim()) {
            continue;
          }
          files.push({
            url: url.trim(),
            name: typeof entry.name === 'string' ? entry.name : undefined,
            size: typeof entry.size === 'number' ? entry.size : undefined,
          });
        }

        if (files.length === 0) {
          return false;
        }

        block.data = {
          files,
          caption: typeof d.caption === 'string' ? d.caption : '',
          style: d.style === 'slider' ? 'slider' : 'fit',
        };
        return true;
      }

      return true;
    });

    return { ...data, blocks };
  }

  private async tryMigrateContent(): Promise<OutputData | undefined> {
    try {
      const dryRunResult = await this.migrationDryRun();

      if (!dryRunResult) {
        return undefined;
      }

      if (dryRunResult.skipped) {
        return undefined;
      }

      if (dryRunResult.dry_run && dryRunResult.preview) {
        const confirmed = await this.showMigrationPreview(dryRunResult);
        if (!confirmed) {
          return undefined;
        }
      }

      return await this.executeMigration(!!dryRunResult.has_existing);
    } catch (e) {
      console.error('[mxEditorJs] Migration failed:', e);
    }
    return undefined;
  }

  private async migrationDryRun(): Promise<MigrationDryRunResult | undefined> {
    const form = new FormData();
    form.append('action', 'content/migrate');
    form.append('resource_id', String(this.config.resourceId));
    form.append('dry_run', 'true');

    const response = await fetch(this.config.connectorUrl, {
      method: 'POST',
      body: form,
    });

    const result = await response.json();
    if (result.success && result.data) {
      return result.data as MigrationDryRunResult;
    }
    return undefined;
  }

  private async showMigrationPreview(dryRunResult: MigrationDryRunResult): Promise<boolean> {
    const t = this.config.i18n;
    const migrationTitle = t?.migration_title ?? 'HTML → Editor.js Migration Preview';
    const blocksLabel = t?.migration_blocks_count ?? 'Blocks to create:';
    const sizeLabel = t?.migration_html_size ?? 'HTML size:';
    const overwriteWarn = t?.migration_warning_overwrite ?? 'Existing editor content will be overwritten';
    const moreBlocksTpl = t?.migration_more_blocks ?? '...and {{count}} more blocks';
    const cancelLabel = t?.cancel ?? 'Cancel';
    const migrateLabel = t?.migrate_content ?? 'Migrate Content';

    return new Promise((resolve) => {
      const overlay = document.createElement('div');
      overlay.classList.add('mxeditorjs-migration-overlay');

      const modal = document.createElement('div');
      modal.classList.add('mxeditorjs-migration-modal');

      const title = document.createElement('h3');
      title.classList.add('mxeditorjs-migration-title');
      title.textContent = migrationTitle;
      modal.appendChild(title);

      const info = document.createElement('div');
      info.classList.add('mxeditorjs-migration-info');
      info.innerHTML = `
        <p><strong>${blocksLabel}</strong> ${dryRunResult.blocks_count}</p>
        <p><strong>${sizeLabel}</strong> ${Math.round((dryRunResult.html_length || 0) / 1024 * 10) / 10} KB</p>
        ${dryRunResult.has_existing ? `<p class="mxeditorjs-migration-warning">⚠️ ${overwriteWarn}</p>` : ''}
      `;
      modal.appendChild(info);

      const preview = document.createElement('div');
      preview.classList.add('mxeditorjs-migration-preview');

      const blocks = dryRunResult.preview?.blocks || [];
      blocks.slice(0, 5).forEach((block) => {
        const blockEl = document.createElement('div');
        blockEl.classList.add('mxeditorjs-migration-block');
        blockEl.innerHTML = `<span class="mxeditorjs-migration-block-type">${block.type}</span>`;
        preview.appendChild(blockEl);
      });

      if (blocks.length > 5) {
        const more = document.createElement('div');
        more.classList.add('mxeditorjs-migration-more');
        more.textContent = moreBlocksTpl.replace('{{count}}', String(blocks.length - 5));
        preview.appendChild(more);
      }

      modal.appendChild(preview);

      const buttons = document.createElement('div');
      buttons.classList.add('mxeditorjs-migration-buttons');

      const cancelBtn = document.createElement('button');
      cancelBtn.type = 'button';
      cancelBtn.classList.add('mxeditorjs-migration-btn');
      cancelBtn.textContent = cancelLabel;
      cancelBtn.addEventListener('click', () => {
        overlay.remove();
        resolve(false);
      });

      const confirmBtn = document.createElement('button');
      confirmBtn.type = 'button';
      confirmBtn.classList.add('mxeditorjs-migration-btn', 'mxeditorjs-migration-btn--primary');
      confirmBtn.textContent = migrateLabel;
      confirmBtn.addEventListener('click', () => {
        overlay.remove();
        resolve(true);
      });

      buttons.appendChild(cancelBtn);
      buttons.appendChild(confirmBtn);
      modal.appendChild(buttons);

      overlay.appendChild(modal);
      document.body.appendChild(overlay);
    });
  }

  private async executeMigration(hasExisting: boolean): Promise<OutputData | undefined> {
    const form = new FormData();
    form.append('action', 'content/migrate');
    form.append('resource_id', String(this.config.resourceId));
    form.append('confirmed', 'true');
    if (hasExisting) {
      form.append('force', 'true');
    }

    const response = await fetch(this.config.connectorUrl, {
      method: 'POST',
      body: form,
    });

    const result = await response.json();
    if (result.success && result.data?.migrated) {
      return await this.fetchContent();
    }
    return undefined;
  }

  private async fetchContent(): Promise<OutputData | undefined> {
    const form = new FormData();
    form.append('action', 'content/get');
    form.append('resource_id', String(this.config.resourceId));

    const response = await fetch(this.config.connectorUrl, {
      method: 'POST',
      body: form,
    });

    const result = await response.json();
    if (result.success && result.data?.content_json) {
      return this.normalizeContent(result.data.content_json as OutputData);
    }
    return undefined;
  }

  private async uploadAttachFile(file: File): Promise<{ success: number; file: { url: string; name?: string; size?: number } }> {
    const form = new FormData();
    form.append('action', 'media/uploadFile');
    form.append('resource_id', String(this.config.resourceId));
    form.append('file', file);

    const response = await fetch(this.config.connectorUrl, {
      method: 'POST',
      body: form,
    });

    const result = await response.json();
    if (result.success && result.file?.url) {
      return { success: 1, file: result.file };
    }
    throw new Error(result.message || this.config.i18n?.upload_failed || 'Upload failed');
  }

  private async uploadGalleryImage(file: File): Promise<{ success: number; file: { url: string; name?: string; size?: number } }> {
    const form = new FormData();
    form.append('action', 'media/upload');
    form.append('resource_id', String(this.config.resourceId));
    form.append('image', file);

    const response = await fetch(this.config.connectorUrl, {
      method: 'POST',
      body: form,
    });

    const result = await response.json();
    if (result.success && result.file?.url) {
      return { success: 1, file: result.file };
    }
    throw new Error(result.message || this.config.i18n?.upload_failed || 'Upload failed');
  }

  private buildTools(): Record<string, any> {
    const allTools: Record<string, any> = {
      header: {
        class: Header as any,
        config: { levels: [2, 3, 4, 5], defaultLevel: 2 },
        inlineToolbar: true,
        tunes: ['alignmentTune'],
      },
      list: {
        class: NestedList as any,
        inlineToolbar: true,
        tunes: ['alignmentTune'],
      },
      paragraph: {
        class: Paragraph as any,
        inlineToolbar: true,
        config: { preserveBlank: true },
        tunes: ['alignmentTune'],
      },
      image: {
        class: ImageTool as any,
        config: {
          connectorUrl: this.config.connectorUrl,
          resourceId: this.config.resourceId,
          classPresets: this.config.presets?.imageClass,
          i18n: this.config.i18n,
        },
      },
      gallery: {
        class: GalleryTool as any,
        config: {
          connectorUrl: this.config.connectorUrl,
          resourceId: this.config.resourceId,
          sortableJs: Sortable,
          maxElementCount:
            typeof this.config.galleryMaxCount === 'number' && this.config.galleryMaxCount > 0
              ? this.config.galleryMaxCount
              : undefined,
          uploader: {
            uploadByFile: (file: File) => this.uploadGalleryImage(file),
          },
          buttonContent: this.config.i18n?.gallery_select_image ?? '',
          mxI18n: {
            gallery_browse: this.config.i18n?.gallery_browse,
            gallery_browse_title: this.config.i18n?.gallery_browse_title,
            loading: this.config.i18n?.loading,
            root: this.config.i18n?.root,
            root_title: this.config.i18n?.root_title,
            back: this.config.i18n?.back,
            no_files_found: this.config.i18n?.no_files_found,
            gallery_select_image: this.config.i18n?.gallery_select_image,
          },
        },
      },
      attaches: {
        class: AttachesTool as any,
        config: {
          uploader: {
            uploadByFile: (file: File) => this.uploadAttachFile(file),
          },
          buttonText: this.config.i18n?.select_file ?? 'Select file to upload',
        },
      },
      embed: {
        class: Embed as any,
        config: {
          services: {
            youtube: true,
            vimeo: true,
            twitter: true,
            instagram: true,
            codepen: true,
            rutube: {
              regex: /https?:\/\/rutube\.ru\/video\/([a-zA-Z0-9]+)\/?/,
              embedUrl: 'https://rutube.ru/play/embed/<%= remote_id %>',
              html: '<iframe style="width:100%;" height="320" frameborder="0" allowfullscreen></iframe>',
              height: 320,
              width: 580,
            },
          },
        },
      },
      delimiter: { class: Delimiter as any },
      quote: {
        class: Quote as any,
        inlineToolbar: true,
        tunes: ['alignmentTune'],
        config: {
          quotePlaceholder: 'Enter a quote',
          captionPlaceholder: 'Quote author',
        },
      },
      code: { class: CodeTool as any },
      raw: { class: RawTool as any },
      table: {
        class: Table as any,
        inlineToolbar: true,
        config: { rows: 2, cols: 3 },
      },
      marker: { class: Marker as any },
      inlineCode: { class: InlineCode as any },
      underline: { class: Underline as any },
      linkAutocomplete: {
        class: LinkAutocomplete as any,
        config: {
          connectorUrl: this.config.connectorUrl,
          classPresets: this.config.presets?.linkClass,
          targetOptions: this.config.presets?.linkTarget,
          relOptions: this.config.presets?.linkRel,
        },
      },
      warning: {
        class: Warning as any,
        inlineToolbar: true,
        config: {
          titlePlaceholder: 'Title',
          messagePlaceholder: 'Message',
        },
      },
      checklist: {
        class: Checklist as any,
        inlineToolbar: true,
      },
      alignmentTune: {
        class: AlignmentTuneTool as any,
        config: { default: 'left' },
      },
    };

    const enabledTools = this.config.enabledTools;
    if (!enabledTools || enabledTools.length === 0) {
      return allTools;
    }

    const inlineTools = ['marker', 'inlineCode', 'underline', 'linkAutocomplete'];
    const tunes = ['alignmentTune'];
    const alwaysEnabled = [...inlineTools, ...tunes];

    const filteredTools: Record<string, any> = {};
    for (const toolName of Object.keys(allTools)) {
      if (enabledTools.includes(toolName) || alwaysEnabled.includes(toolName)) {
        filteredTools[toolName] = allTools[toolName];
      }
    }

    return filteredTools;
  }

  private createEditor(data: OutputData | undefined, textarea: HTMLTextAreaElement): void {
    const t = this.config.i18n;
    this.editor = new EditorJS({
      holder: this.instanceHolderId,
      data: data,
      tools: this.buildTools(),
      placeholder: t?.placeholder ?? 'Start writing...',
      i18n: this.config.editorJsI18n ?? undefined,
      onReady: () => {
        if (this.editor) {
          new Undo({ editor: this.editor });
        }
        this.loaded = true;
        this.scheduleSyncToTextarea();
      },
      onChange: () => {
        this.scheduleSyncToTextarea();
      },
    });
  }

  private scheduleSyncToTextarea(): void {
    if (this.syncTimer) {
      clearTimeout(this.syncTimer);
    }

    this.syncTimer = setTimeout(() => {
      this.syncToTextarea();
    }, 500);
  }

  private async syncToTextarea(): Promise<void> {
    if (!this.editor || !this.loaded || !this.textarea) {
      return;
    }

    try {
      const outputData = await this.editor.save();
      outputData.blocks = outputData.blocks.filter((b) => this.isBlockNonEmpty(b));
      const html = this.renderPreviewHtml(outputData);
      this.cachedHtml = html;
      this.cachedJsonString = JSON.stringify(outputData);
      this.textarea.value = html;

      if (this.jsonField) {
        this.jsonField.value = this.cachedJsonString;
      }
    } catch (err) {
      console.error('[mxEditorJs] Sync error:', err);
    }
  }

  private createJsonField(textarea: HTMLTextAreaElement): void {
    const field = document.createElement('input');
    field.type = 'hidden';

    if (this.tmplvarId) {
      field.id = `mxeditorjs-json-field-tv-${this.tmplvarId}`;
      field.name = `mxeditorjs_tv_${this.tmplvarId}_json`;
    } else {
      field.id = 'mxeditorjs-json-field';
      field.name = 'mxeditorjs_json';
    }
    field.value = '';

    const form = textarea.closest('form');
    if (form) {
      form.appendChild(field);
    }

    this.jsonField = field;
  }

  private toggleFullscreen(): void {
    this.fullscreenActive = !this.fullscreenActive;

    if (this.wrapperEl) {
      this.wrapperEl.classList.toggle('mxeditorjs-wrapper--fullscreen', this.fullscreenActive);
    }

    document.body.classList.toggle('mxeditorjs-body-fullscreen', this.fullscreenActive);

    const button = this.toolbarEl?.querySelector('[data-action="fullscreen"]');
    if (button) {
      button.classList.toggle('mxeditorjs-toolbar__button--active', this.fullscreenActive);
      button.textContent = this.fullscreenActive ? 'Exit Fullscreen' : 'Fullscreen';
    }
  }

  private registerKeyboardShortcuts(): void {
    if (this.handleKeyDown) {
      document.removeEventListener('keydown', this.handleKeyDown);
    }

    this.handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && this.fullscreenActive) {
        e.preventDefault();
        this.toggleFullscreen();
        return;
      }

      if (e.key === 'F11' && !e.ctrlKey && !e.metaKey && !e.altKey) {
        e.preventDefault();
        this.toggleFullscreen();
        return;
      }

      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'u' && !e.shiftKey) {
        e.preventDefault();
        this.toggleSourcePreview();
        return;
      }
    };

    document.addEventListener('keydown', this.handleKeyDown);
  }

  private unregisterKeyboardShortcuts(): void {
    if (this.handleKeyDown) {
      document.removeEventListener('keydown', this.handleKeyDown);
      this.handleKeyDown = null;
    }
  }

  private async toggleSourcePreview(): Promise<void> {
    if (!this.sourcePreviewEl) return;

    this.sourcePreviewVisible = !this.sourcePreviewVisible;

    if (this.sourcePreviewVisible && this.editor) {
      const outputData = await this.editor.save();
      this.sourcePreviewEl.textContent = this.renderPreviewHtml(outputData);
      this.sourcePreviewEl.style.display = 'block';

      const holder = document.getElementById(this.instanceHolderId);
      if (holder) holder.style.display = 'none';
    } else {
      this.sourcePreviewEl.style.display = 'none';

      const holder = document.getElementById(this.instanceHolderId);
      if (holder) holder.style.display = '';
    }

    const button = this.toolbarEl?.querySelector('[data-action="source"]');
    if (button) {
      button.classList.toggle('mxeditorjs-toolbar__button--active', this.sourcePreviewVisible);
    }
  }

  private getBlockAlignment(block: { tunes?: Record<string, { alignment?: string }> }): string {
    const alignment = block.tunes?.alignmentTune?.alignment;
    return alignment === 'center' || alignment === 'right' ? alignment : 'left';
  }

  private wrapWithAlignment(html: string, alignment: string): string {
    if (alignment === 'left') return html;
    return `<div style="text-align: ${alignment}">${html}</div>`;
  }

  private renderPreviewHtml(data: OutputData): string {
    const blocks = data.blocks || [];
    return blocks.map((block) => {
      const d = block.data || {};
      const alignment = this.getBlockAlignment(block);
      let html: string;
      switch (block.type) {
        case 'paragraph':
          html = `<p>${d.text || ''}</p>`;
          break;
        case 'header':
          html = `<h${d.level || 2}>${d.text || ''}</h${d.level || 2}>`;
          break;
        case 'list': {
          const tag = d.style === 'ordered' ? 'ol' : d.style === 'checklist' ? 'ul' : 'ul';
          const items = (d.items || [])
            .map((item: any) => {
              const content = typeof item === 'string' ? item : item.content || '';
              const checked = item?.meta?.checked;
              const checkbox = d.style === 'checklist' ? `<input type="checkbox" disabled ${checked ? 'checked' : ''}> ` : '';
              return `<li>${checkbox}${content}</li>`;
            })
            .join('');
          html = `<${tag} class="${d.style === 'checklist' ? 'mxeditorjs-checklist' : ''}">${items}</${tag}>`;
          break;
        }
        case 'image': {
          const url = d.file?.url || '';
          const caption = d.caption || '';
          html = `<figure><img src="${url}" alt="${caption.replace(/<[^>]*>/g, '')}" loading="lazy">`;
          if (caption) html += `<figcaption>${caption}</figcaption>`;
          html += '</figure>';
          break;
        }
        case 'gallery': {
          const files = Array.isArray(d.files) ? d.files : [];
          const caption = typeof d.caption === 'string' ? d.caption : '';
          const style = d.style === 'slider' ? 'slider' : 'fit';
          const imgs = files
            .filter((f: { url?: string }) => f && typeof f.url === 'string' && f.url.trim() !== '')
            .map((f: { url: string }) => `<img src="${this.escapeHtml(f.url.trim())}" alt="" loading="lazy">`)
            .join('');
          html = `<figure class="mxeditorjs-gallery mxeditorjs-gallery--${style}"><div class="mxeditorjs-gallery__track">${imgs}</div>`;
          if (caption.trim()) {
            html += `<figcaption>${caption}</figcaption>`;
          }
          html += '</figure>';
          break;
        }
        case 'attaches': {
          const file = d.file || {};
          const url = file.url || '';
          const title = d.title || file.name || 'Download';
          html = !url ? '' : `<p><a href="${this.escapeHtml(url)}" download>${this.escapeHtml(title)}</a></p>`;
          break;
        }
        case 'embed': {
          const embed = d.embed || '';
          html = !embed ? '' : `<div class="mxeditorjs-embed"><iframe src="${this.escapeHtml(embed)}" frameborder="0" allowfullscreen loading="lazy"></iframe></div>`;
          break;
        }
        case 'warning': {
          const title = d.title || '';
          const message = d.message || '';
          html = `<div class="mxeditorjs-warning"><strong>${this.escapeHtml(title)}</strong><p>${this.escapeHtml(message)}</p></div>`;
          break;
        }
        case 'checklist': {
          const items = d.items || [];
          html = '<ul class="mxeditorjs-checklist">' + items
            .map((item: { text?: string; checked?: boolean }) => {
              const text = item.text || '';
              const checked = item.checked ? ' checked' : '';
              return `<li><input type="checkbox" disabled${checked}> ${this.escapeHtml(text)}</li>`;
            })
            .join('') + '</ul>';
          break;
        }
        case 'delimiter':
          html = '<hr>';
          break;
        case 'quote': {
          html = `<blockquote>${d.text || ''}`;
          if (d.caption) html += `<cite>${d.caption}</cite>`;
          html += '</blockquote>';
          break;
        }
        case 'code':
          html = `<pre><code>${this.escapeHtml(d.code || '')}</code></pre>`;
          break;
        case 'raw':
          html = d.html || '';
          break;
        case 'table': {
          const rows = d.content || [];
          html = '<table>';
          rows.forEach((row: string[], i: number) => {
            const tag = d.withHeadings && i === 0 ? 'th' : 'td';
            html += '<tr>' + row.map((cell: string) => `<${tag}>${cell}</${tag}>`).join('') + '</tr>';
          });
          html += '</table>';
          break;
        }
        default:
          html = `<!-- unknown block: ${block.type} -->`;
      }
      const alignmentBlocks = ['paragraph', 'header', 'list', 'quote'];
      return alignmentBlocks.includes(block.type) ? this.wrapWithAlignment(html, alignment) : html;
    }).join('\n');
  }

  private isBlockNonEmpty(block: { type: string; data?: Record<string, any> }): boolean {
    const d = block.data || {};
    switch (block.type) {
      case 'paragraph':
      case 'header':
        return !!d.text?.trim();
      case 'list':
        return Array.isArray(d.items) && d.items.length > 0;
      case 'image':
        return !!d.file?.url;
      case 'gallery':
        return Array.isArray(d.files) && d.files.some((f: { url?: string }) => String(f?.url ?? '').trim() !== '');
      case 'attaches':
        return !!d.file?.url;
      case 'embed':
        return !!d.embed;
      case 'warning':
        return !!(d.title?.trim() || d.message?.trim());
      case 'checklist':
        return Array.isArray(d.items) && d.items.length > 0;
      case 'code':
        return !!d.code?.trim();
      case 'raw':
        return !!d.html?.trim();
      case 'quote':
        return !!d.text?.trim();
      case 'table':
        return Array.isArray(d.content) && d.content.length > 0;
      case 'delimiter':
        return true;
      default:
        return true;
    }
  }

  private escapeHtml(text: string): string {
    return text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  private hookBeforeSubmit(): void {
    const Ext = (window as any).Ext;
    if (!Ext) return;

    const panel = Ext.getCmp('modx-panel-resource');
    if (!panel) return;

    const origBeforeSubmit = panel.beforeSubmit;
    const self = this;
    panel.beforeSubmit = function (o: any) {
      if (self.jsonField && self.cachedJsonString) {
        self.jsonField.value = self.cachedJsonString;
      }
      return origBeforeSubmit.call(panel, o);
    };
  }

  destroy(): void {
    if (this.syncTimer) {
      clearTimeout(this.syncTimer);
      this.syncTimer = null;
    }

    if (this.fullscreenActive) {
      this.toggleFullscreen();
    }

    if (this.editor) {
      this.editor.destroy();
      this.editor = null;
    }

    if (this.wrapperEl) {
      this.wrapperEl.remove();
      this.wrapperEl = null;
    }

    if (this.toolbarEl) {
      this.toolbarEl.remove();
      this.toolbarEl = null;
    }

    this.sourcePreviewEl = null;

    if (this.jsonField) {
      this.jsonField.remove();
      this.jsonField = null;
    }

    if (this.textarea) {
      this.textarea.style.display = '';
      this.textarea = null;
    }

    this.loaded = false;
    this.unregisterKeyboardShortcuts();
  }
}

const activeInstances: Map<string, MxEditorJsApp> = new Map();

/**
 * Нормализует аргумент loadRTE/unloadRTE в массив ID.
 * MODX может передавать строку (через запятую), массив или один элемент.
 */
function normalizeRteElements(elements: unknown): string[] {
  if (elements == null) {
    return [];
  }
  if (typeof elements === 'string') {
    return elements.split(',').map((id) => id.trim()).filter(Boolean);
  }
  if (Array.isArray(elements)) {
    return elements
      .map((item) => (typeof item === 'string' ? item : (item as { id?: string })?.id))
      .filter((id): id is string => typeof id === 'string' && id.length > 0);
  }
  if (typeof elements === 'object' && elements !== null && 'id' in elements) {
    const id = (elements as { id: string }).id;
    return typeof id === 'string' && id ? [id] : [];
  }
  return [];
}

function registerRteHooks(): void {
  const config = window.mxEditorJsConfig;
  if (!config || !window.MODx) {
    return;
  }

  window.MODx.loadRTE = (elements: unknown) => {
    const ids = normalizeRteElements(elements);
    if (ids.length === 0) {
      return;
    }

    for (const elementId of ids) {
      const existing = activeInstances.get(elementId);
      if (existing) {
        existing.destroy();
        activeInstances.delete(elementId);
      }
      const app = new MxEditorJsApp(config);
      app.initForElement(elementId);
      activeInstances.set(elementId, app);
    }
  };

  window.MODx.unloadRTE = (elements: unknown) => {
    const ids = normalizeRteElements(elements);
    if (ids.length > 0) {
      ids.forEach((id) => {
        const app = activeInstances.get(id);
        if (app) {
          app.destroy();
          activeInstances.delete(id);
        }
      });
    } else {
      activeInstances.forEach((app) => app.destroy());
      activeInstances.clear();
    }
  };
}

registerRteHooks();

function initTvRichTextFields(): void {
  if (!window.MODx?.loadRTE) return;

  const tvAreas = document.querySelectorAll<HTMLTextAreaElement>('textarea.modx-richtext');
  tvAreas.forEach((el) => {
    if (el.id && el.id !== 'ta' && !el.dataset.mxeditorjsInit) {
      el.dataset.mxeditorjsInit = '1';
      window.MODx!.loadRTE(el.id);
    }
  });
}

function startTvObserver(): void {
  initTvRichTextFields();

  const tvObserver = new MutationObserver(() => {
    initTvRichTextFields();
  });
  tvObserver.observe(document.body, { childList: true, subtree: true });
}

if (document.body) {
  startTvObserver();
} else {
  document.addEventListener('DOMContentLoaded', startTvObserver);
}

export { MxEditorJsApp };
