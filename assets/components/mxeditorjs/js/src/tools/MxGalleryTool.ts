import type { API, BlockTool, BlockToolConstructorOptions } from '@editorjs/editorjs';

export type MxGalleryBlockMode = 'ids' | 'collection';

export interface MxGalleryBlockData {
  mode: MxGalleryBlockMode;
  ids: number[];
  collectionId: number | null;
  collectionName?: string;
  labels?: string[];
  thumbs?: string[];
}

export interface MxGalleryToolI18n {
  tool_title?: string;
  choose_media?: string;
  choose_collection?: string;
  change?: string;
  clear?: string;
  empty?: string;
  collection_prefix?: string;
  no_collections?: string;
  loading?: string;
}

export interface MxGalleryToolConfig {
  enabled?: boolean;
  connectorUrl?: string;
  pickerUrl?: string;
  authToken?: string;
  i18n?: MxGalleryToolI18n;
}

interface CollectionRow {
  id?: number | string;
  name?: string;
  alias?: string;
}

function normalizeIds(raw: unknown): number[] {
  if (!Array.isArray(raw)) {
    return [];
  }
  return raw
    .map((value) => parseInt(String(value), 10))
    .filter((id) => id > 0);
}

function normalizeData(raw: Partial<MxGalleryBlockData> | undefined): MxGalleryBlockData {
  const mode: MxGalleryBlockMode = raw?.mode === 'collection' ? 'collection' : 'ids';
  const collectionId =
    typeof raw?.collectionId === 'number' && raw.collectionId > 0 ? raw.collectionId : null;
  return {
    mode,
    ids: normalizeIds(raw?.ids),
    collectionId,
    collectionName: typeof raw?.collectionName === 'string' ? raw.collectionName : undefined,
    labels: Array.isArray(raw?.labels) ? raw.labels.map(String) : undefined,
    thumbs: Array.isArray(raw?.thumbs) ? raw.thumbs.map(String) : undefined,
  };
}

export function buildMxGallerySnippet(data: MxGalleryBlockData): string {
  if (data.mode === 'collection' && data.collectionId && data.collectionId > 0) {
    return `[[!mxGallery? &collection=\`${data.collectionId}\` &picture=\`1\`]]`;
  }
  const ids = normalizeIds(data.ids);
  if (ids.length === 0) {
    return '';
  }
  if (ids.length === 1) {
    return `[[!mxGallery? &id=\`${ids[0]}\` &picture=\`1\`]]`;
  }
  return `[[!mxGallery? &ids=\`${ids.join(',')}\` &picture=\`1\` &sort=\`selection\`]]`;
}

function itemThumb(item: Record<string, unknown> | null | undefined): string {
  if (!item || typeof item !== 'object') {
    return '';
  }
  const meta = (item.metadata as Record<string, unknown> | undefined) || {};
  if (typeof meta.mgr_thumb_url === 'string' && meta.mgr_thumb_url !== '') {
    return meta.mgr_thumb_url;
  }
  if (typeof meta.thumbnail_url === 'string' && meta.thumbnail_url !== '') {
    return meta.thumbnail_url;
  }
  if (item.type === 'image' && typeof item.url === 'string') {
    return item.url;
  }
  return '';
}

function itemLabel(item: Record<string, unknown> | null | undefined, id: number): string {
  if (item && typeof item === 'object') {
    if (typeof item.name === 'string' && item.name.trim() !== '') {
      return item.name.trim();
    }
    if (typeof item.title === 'string' && item.title.trim() !== '') {
      return item.title.trim();
    }
  }
  return `#${id}`;
}

function clearElement(el: HTMLElement): void {
  while (el.firstChild) {
    el.removeChild(el.firstChild);
  }
}

export default class MxGalleryBlockTool implements BlockTool {
  static get toolbox() {
    return {
      title: 'mxGallery',
      icon: '<svg width="17" height="15" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="8.5" cy="10" r="1.5" fill="currentColor"/><path d="M3 16l4.5-4.5a2 2 0 0 1 2.8 0L21 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    };
  }

  static get isReadOnlySupported() {
    return true;
  }

  private api: API;
  private readOnly: boolean;
  private config: MxGalleryToolConfig;
  private data: MxGalleryBlockData;
  private wrapper: HTMLElement | null = null;
  private pickerWindow: Window | null = null;
  private messageHandler: ((event: MessageEvent) => void) | null = null;

  constructor({ data, api, config, readOnly }: BlockToolConstructorOptions) {
    this.api = api;
    this.readOnly = !!readOnly;
    this.config = (config as MxGalleryToolConfig) || {};
    this.data = normalizeData(data as Partial<MxGalleryBlockData>);
  }

  render(): HTMLElement {
    this.wrapper = document.createElement('div');
    this.wrapper.className = 'mxeditorjs-mxgallery';
    this.redraw();
    return this.wrapper;
  }

  save(): MxGalleryBlockData {
    return {
      mode: this.data.mode,
      ids: [...this.data.ids],
      collectionId: this.data.collectionId,
      collectionName: this.data.collectionName,
      labels: this.data.labels ? [...this.data.labels] : undefined,
      thumbs: this.data.thumbs ? [...this.data.thumbs] : undefined,
    };
  }

  validate(data: MxGalleryBlockData): boolean {
    const normalized = normalizeData(data);
    if (normalized.mode === 'collection') {
      return normalized.collectionId !== null && normalized.collectionId > 0;
    }
    return normalized.ids.length > 0;
  }

  destroy(): void {
    this.cleanupMessageHandler();
    if (this.pickerWindow && !this.pickerWindow.closed) {
      this.pickerWindow.close();
    }
  }

  private t(key: keyof MxGalleryToolI18n, fallback: string): string {
    return this.config.i18n?.[key] || fallback;
  }

  private redraw(): void {
    if (!this.wrapper) {
      return;
    }
    clearElement(this.wrapper);

    const summary = document.createElement('div');
    summary.className = 'mxeditorjs-mxgallery__summary';

    if (this.data.mode === 'collection' && this.data.collectionId) {
      const name =
        this.data.collectionName ||
        `${this.t('collection_prefix', 'Collection')} #${this.data.collectionId}`;
      summary.textContent = name;
    } else if (this.data.ids.length > 0) {
      const thumbs = this.data.thumbs || [];
      const labels = this.data.labels || [];
      const preview = document.createElement('div');
      preview.className = 'mxeditorjs-mxgallery__thumbs';
      this.data.ids.slice(0, 6).forEach((id, index) => {
        const thumb = thumbs[index];
        if (thumb) {
          const img = document.createElement('img');
          img.src = thumb;
          img.alt = labels[index] || `#${id}`;
          img.loading = 'lazy';
          preview.appendChild(img);
        } else {
          const chip = document.createElement('span');
          chip.className = 'mxeditorjs-mxgallery__chip';
          chip.textContent = labels[index] || `#${id}`;
          preview.appendChild(chip);
        }
      });
      if (this.data.ids.length > 6) {
        const more = document.createElement('span');
        more.className = 'mxeditorjs-mxgallery__more';
        more.textContent = `+${this.data.ids.length - 6}`;
        preview.appendChild(more);
      }
      summary.appendChild(preview);
      const caption = document.createElement('div');
      caption.className = 'mxeditorjs-mxgallery__caption';
      caption.textContent =
        this.data.ids.length === 1
          ? labels[0] || `#${this.data.ids[0]}`
          : `${this.data.ids.length} media`;
      summary.appendChild(caption);
    } else {
      summary.classList.add('mxeditorjs-mxgallery__summary--empty');
      summary.textContent = this.t('empty', 'No mxGallery media selected');
    }

    this.wrapper.appendChild(summary);

    if (this.readOnly || this.config.enabled === false) {
      return;
    }

    const actions = document.createElement('div');
    actions.className = 'mxeditorjs-mxgallery__actions';

    const mediaBtn = document.createElement('button');
    mediaBtn.type = 'button';
    mediaBtn.className = 'cdx-button';
    mediaBtn.textContent = this.data.ids.length
      ? this.t('change', 'Change')
      : this.t('choose_media', 'Choose media');
    mediaBtn.addEventListener('click', () => this.openMediaPicker());
    actions.appendChild(mediaBtn);

    const collectionBtn = document.createElement('button');
    collectionBtn.type = 'button';
    collectionBtn.className = 'cdx-button';
    collectionBtn.textContent = this.t('choose_collection', 'Choose collection');
    collectionBtn.addEventListener('click', () => void this.openCollectionPicker());
    actions.appendChild(collectionBtn);

    if (this.data.ids.length > 0 || this.data.collectionId) {
      const clearBtn = document.createElement('button');
      clearBtn.type = 'button';
      clearBtn.className = 'cdx-button';
      clearBtn.textContent = this.t('clear', 'Clear');
      clearBtn.addEventListener('click', () => {
        this.data = normalizeData({});
        this.redraw();
      });
      actions.appendChild(clearBtn);
    }

    this.wrapper.appendChild(actions);
  }

  private cleanupMessageHandler(): void {
    if (this.messageHandler) {
      window.removeEventListener('message', this.messageHandler);
      this.messageHandler = null;
    }
  }

  private openMediaPicker(): void {
    const pickerUrl = this.config.pickerUrl;
    if (!pickerUrl) {
      return;
    }
    this.cleanupMessageHandler();
    let url = pickerUrl + (pickerUrl.includes('?') ? '&' : '?') + 'tv=1';
    if (this.data.ids.length > 0) {
      url += `&selected=${encodeURIComponent(this.data.ids.join(','))}`;
    }

    this.messageHandler = (event: MessageEvent) => {
      if (event.origin !== window.location.origin) {
        return;
      }
      const payload = event.data;
      if (!payload || payload.type !== 'mxgallery:select') {
        return;
      }
      const ids = normalizeIds(payload.ids);
      const items = Array.isArray(payload.items) ? payload.items : [];
      this.data = {
        mode: 'ids',
        ids,
        collectionId: null,
        labels: ids.map((id, index) => itemLabel(items[index], id)),
        thumbs: ids.map((_, index) => itemThumb(items[index])),
      };
      this.cleanupMessageHandler();
      if (this.pickerWindow && !this.pickerWindow.closed) {
        this.pickerWindow.close();
      }
      this.pickerWindow = null;
      this.redraw();
    };
    window.addEventListener('message', this.messageHandler);
    this.pickerWindow = window.open(
      url,
      'mxgallery-editorjs-picker',
      'width=1200,height=800,menubar=no,toolbar=no,location=no,status=no,resizable=yes,scrollbars=yes',
    );
  }

  private authHeaders(): Record<string, string> {
    const token = this.config.authToken || '';
    return token ? { MODAUTH: token } : {};
  }

  private async openCollectionPicker(): Promise<void> {
    const connectorUrl = this.config.connectorUrl;
    if (!connectorUrl) {
      return;
    }

    const body = new URLSearchParams();
    body.set('action', 'mgr/collection/list');
    if (this.config.authToken) {
      body.set('HTTP_MODAUTH', this.config.authToken);
    }

    let collections: CollectionRow[] = [];
    try {
      const response = await fetch(connectorUrl, {
        method: 'POST',
        headers: this.authHeaders(),
        body,
        credentials: 'same-origin',
      });
      const json = await response.json();
      const list = json?.object?.collections;
      collections = Array.isArray(list) ? list : [];
    } catch (error) {
      console.error('[mxEditorJs] mxGallery collections failed', error);
      return;
    }

    if (collections.length === 0) {
      window.alert(this.t('no_collections', 'No collections yet'));
      return;
    }

    const labels = collections.map((row) => {
      const id = parseInt(String(row.id ?? 0), 10);
      const name = row.name || row.alias || `#${id}`;
      return `${name} (#${id})`;
    });
    const choice = window.prompt(
      this.t('choose_collection', 'Choose collection') + '\n' + labels.join('\n'),
      String(this.data.collectionId || collections[0]?.id || ''),
    );
    if (choice === null) {
      return;
    }
    const collectionId = parseInt(choice, 10);
    const match = collections.find((row) => parseInt(String(row.id ?? 0), 10) === collectionId);
    if (!match || !(collectionId > 0)) {
      return;
    }
    this.data = {
      mode: 'collection',
      ids: [],
      collectionId,
      collectionName: match.name || match.alias || `Collection #${collectionId}`,
    };
    this.redraw();
  }
}
