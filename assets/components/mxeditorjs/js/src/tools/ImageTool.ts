import type { BlockTool, BlockToolConstructorOptions, BlockToolData } from '@editorjs/editorjs';
import {
  fetchMediaBrowse,
  normalizeFileUrlForDisplay,
  renderMediaBrowser,
  type MediaBrowserPayload,
} from './MediaBrowser';

interface ImageData extends BlockToolData {
  file: { url: string; name?: string; size?: number };
  caption: string;
  withBorder: boolean;
  stretched: boolean;
  withBackground: boolean;
  classPreset?: string;
  customClass?: string;
}

interface ImageToolI18n {
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
}

interface ImageToolConfig {
  connectorUrl: string;
  resourceId: number;
  classPresets?: Record<string, string>;
  i18n?: ImageToolI18n;
}

export default class ImageTool implements BlockTool {
  private data: ImageData;
  private config: ImageToolConfig;
  private wrapper: HTMLElement | null = null;
  private api: BlockToolConstructorOptions['api'];

  static get toolbox() {
    return {
      title: 'Image',
      icon: '<svg width="17" height="15" viewBox="0 0 336 276"><path d="M291 150V79c0-19-15-34-34-34H79c-19 0-34 15-34 34v42l67-44 81 72 56-29 42 30zm0 52l-43-30-56 30-81-67-67 42v30c0 19 15 34 34 34h178c17 0 31-13 34-29zM79 0h178c44 0 79 35 79 79v118c0 44-35 79-79 79H79c-44 0-79-35-79-79V79C0 35 35 0 79 0z"/></svg>',
    };
  }

  private t(key: keyof ImageToolI18n): string {
    const v = this.config.i18n?.[key];
    return typeof v === 'string' && v !== '' ? v : this.defaultStrings[key] ?? '';
  }

  private get defaultStrings(): Record<keyof ImageToolI18n, string> {
    return {
      image_upload: 'Upload',
      image_upload_title: 'Upload image from your device',
      image_browse: 'Browse',
      image_browse_title: 'Browse already uploaded images',
      loading: 'Loading...',
      uploading: 'Uploading...',
      root: 'Root',
      root_title: 'Browse from media source root',
      back: '← Back',
      no_files_found: 'No files found',
      caption: 'Caption',
      border: 'Border',
      stretch: 'Stretch',
      background: 'Background',
      style: 'Style:',
      custom_css: 'Custom CSS classes',
    };
  }

  constructor({ data, config, api }: BlockToolConstructorOptions) {
    const file = this.normalizeFile(data);
    this.data = {
      file,
      caption: data?.caption || '',
      withBorder: data?.withBorder ?? false,
      stretched: data?.stretched ?? false,
      withBackground: data?.withBackground ?? false,
      classPreset: data?.classPreset || 'default',
      customClass: data?.customClass || '',
    };
    this.config = config as ImageToolConfig;
    this.api = api;
  }

  private normalizeFile(data: BlockToolData | undefined): { url: string; name?: string; size?: number } {
    if (!data) return { url: '' };
    const f = data.file;
    if (f && typeof f === 'object' && typeof (f as { url?: string }).url === 'string') {
      const url = (f as { url: string }).url?.trim();
      return url ? { url, name: (f as { name?: string }).name, size: (f as { size?: number }).size } : { url: '' };
    }
    if (typeof (data as { url?: string }).url === 'string') {
      const url = (data as { url: string }).url.trim();
      return url ? { url } : { url: '' };
    }
    if (typeof f === 'string' && f.trim()) {
      return { url: f.trim() };
    }
    return { url: '' };
  }

  render(): HTMLElement {
    this.wrapper = document.createElement('div');
    this.wrapper.classList.add('mxeditorjs-image-tool');

    if (this.data.file.url) {
      this.showImage(this.data.file.url, this.data.caption);
    } else {
      this.showUploadButton();
    }

    return this.wrapper;
  }

  private showUploadButton(): void {
    if (!this.wrapper) return;

    this.wrapper.innerHTML = '';

    const container = document.createElement('div');
    container.classList.add('mxeditorjs-image-tool__buttons');

    const uploadBtn = document.createElement('div');
    uploadBtn.classList.add('mxeditorjs-image-tool__select');
    uploadBtn.textContent = this.t('image_upload');
    uploadBtn.title = this.t('image_upload_title');
    uploadBtn.addEventListener('click', () => {
      const input = document.createElement('input');
      input.type = 'file';
      input.accept = 'image/*';
      input.addEventListener('change', () => {
        const file = input.files?.[0];
        if (file) {
          this.uploadImage(file);
        }
      });
      input.click();
    });

    const browseBtn = document.createElement('div');
    browseBtn.classList.add('mxeditorjs-image-tool__select', 'mxeditorjs-image-tool__browse');
    browseBtn.textContent = this.t('image_browse');
    browseBtn.title = this.t('image_browse_title');
    browseBtn.addEventListener('click', () => {
      void this.showBrowser();
    });

    container.appendChild(uploadBtn);
    container.appendChild(browseBtn);
    this.wrapper.appendChild(container);
  }

  private browserStrings() {
    return {
      loading: this.t('loading'),
      root: this.t('root'),
      root_title: this.t('root_title'),
      back: this.t('back'),
      no_files_found: this.t('no_files_found'),
    };
  }

  private async showBrowser(path?: string): Promise<void> {
    if (!this.wrapper) return;

    this.wrapper.innerHTML = `<div class="mxeditorjs-image-tool__loading">${this.t('loading')}</div>`;

    try {
      const data = await fetchMediaBrowse(this.config.connectorUrl, this.config.resourceId, path);
      this.renderBrowserView(data);
    } catch (e) {
      this.showUploadButton();
      console.error('[mxEditorJs] Browse error:', e);
    }
  }

  private renderBrowserView(data: MediaBrowserPayload): void {
    if (!this.wrapper) return;

    this.wrapper.innerHTML = '';
    const mount = document.createElement('div');
    this.wrapper.appendChild(mount);

    renderMediaBrowser(mount, data, this.browserStrings(), {
      onRoot: () => void this.showBrowser('__root__'),
      onBack: () => void this.showBrowser(data.parentPath ?? ''),
      onFolder: (folderPath) => void this.showBrowser(folderPath),
      onSelectFile: (file, storedUrl) => {
        this.data.file = { url: storedUrl, name: file.name, size: file.size };
        this.showImage(storedUrl, '');
      },
      onClose: () => this.showUploadButton(),
    });
  }

  private async uploadImage(file: File): Promise<void> {
    if (!this.wrapper) return;

    this.wrapper.innerHTML = `<div class="mxeditorjs-image-tool__loading">${this.t('uploading')}</div>`;

    const form = new FormData();
    form.append('action', 'media/upload');
    form.append('resource_id', String(this.config.resourceId));
    form.append('image', file);

    try {
      const response = await fetch(this.config.connectorUrl, {
        method: 'POST',
        body: form,
      });

      const result = await response.json();
      if (result.success && result.file?.url) {
        this.data.file = result.file;
        this.showImage(result.file.url, '');
      } else {
        this.showUploadButton();
        console.error('[mxEditorJs] Upload failed:', result.message);
      }
    } catch (e) {
      this.showUploadButton();
      console.error('[mxEditorJs] Upload error:', e);
    }
  }

  private showImage(url: string, caption: string): void {
    if (!this.wrapper) return;

    this.wrapper.innerHTML = '';

    const img = document.createElement('img');
    img.src = normalizeFileUrlForDisplay(url);
    img.alt = this.data.caption ? this.data.caption.replace(/<[^>]*>/g, '') : '';
    img.classList.add('mxeditorjs-image-tool__image');
    this.wrapper.appendChild(img);

    const captionEl = document.createElement('div');
    captionEl.classList.add('mxeditorjs-image-tool__caption');
    captionEl.contentEditable = 'true';
    captionEl.dataset.placeholder = this.t('caption');
    captionEl.innerHTML = caption;
    this.wrapper.appendChild(captionEl);
  }

  save(): ImageData {
    const captionEl = this.wrapper?.querySelector('.mxeditorjs-image-tool__caption');
    return {
      file: this.data.file,
      caption: captionEl?.innerHTML || '',
      withBorder: this.data.withBorder,
      stretched: this.data.stretched,
      withBackground: this.data.withBackground,
      classPreset: this.data.classPreset,
      customClass: this.data.customClass,
    };
  }

  static get sanitize() {
    return {
      caption: { b: true, i: true, a: true, em: true, strong: true },
    };
  }

  renderSettings() {
    const wrapper = document.createElement('div');
    wrapper.classList.add('mxeditorjs-image-settings');

    const toggleSettings = [
      { name: 'withBorder', icon: '⊡', label: this.t('border') },
      { name: 'stretched', icon: '⇔', label: this.t('stretch') },
      { name: 'withBackground', icon: '▧', label: this.t('background') },
    ];

    toggleSettings.forEach((setting) => {
      const button = document.createElement('div');
      button.classList.add('cdx-settings-button');
      if (this.data[setting.name as keyof ImageData]) {
        button.classList.add('cdx-settings-button--active');
      }
      button.innerHTML = setting.icon;
      button.title = setting.label;
      button.addEventListener('click', () => {
        (this.data as unknown as Record<string, boolean>)[setting.name] = !(this.data as unknown as Record<string, boolean>)[setting.name];
        button.classList.toggle('cdx-settings-button--active');
      });
      wrapper.appendChild(button);
    });

    const presets = this.config.classPresets;
    if (presets && Object.keys(presets).length > 0) {
      const presetContainer = document.createElement('div');
      presetContainer.classList.add('mxeditorjs-preset-selector');

      const label = document.createElement('span');
      label.textContent = this.t('style');
      label.classList.add('mxeditorjs-preset-label');
      presetContainer.appendChild(label);

      const select = document.createElement('select');
      select.classList.add('mxeditorjs-preset-select');

      Object.entries(presets).forEach(([key]) => {
        const option = document.createElement('option');
        option.value = key;
        option.textContent = key;
        option.title = presets[key] || '(no classes)';
        if (this.data.classPreset === key) {
          option.selected = true;
        }
        select.appendChild(option);
      });

      select.addEventListener('change', () => {
        this.data.classPreset = select.value;
      });

      presetContainer.appendChild(select);
      wrapper.appendChild(presetContainer);

      const customInput = document.createElement('input');
      customInput.type = 'text';
      customInput.placeholder = this.t('custom_css');
      customInput.classList.add('mxeditorjs-custom-class-input');
      customInput.value = this.data.customClass || '';
      customInput.addEventListener('input', () => {
        this.data.customClass = customInput.value;
      });
      wrapper.appendChild(customInput);
    }

    return wrapper;
  }

  validate(data: ImageData): boolean {
    const file = data?.file;
    if (!file || Object.keys(file).length === 0) {
      return true;
    }
    const url = file.url;
    return typeof url === 'string' && url.trim() !== '';
  }
}
