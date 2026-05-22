import type { BlockToolConstructorOptions } from '@editorjs/editorjs';
import ImageGallery from '@kiberpro/editorjs-gallery';
import { fetchMediaBrowse, renderMediaBrowser } from './MediaBrowser';

export interface GalleryToolMxI18n {
  gallery_browse?: string;
  gallery_browse_title?: string;
  loading?: string;
  root?: string;
  root_title?: string;
  back?: string;
  no_files_found?: string;
  gallery_select_image?: string;
}

export interface GalleryToolMxConfig {
  connectorUrl: string;
  resourceId: number;
  sortableJs: typeof import('sortablejs').default;
  maxElementCount?: number;
  uploader?: {
    uploadByFile: (file: File) => Promise<{ success: number; file: { url: string; name?: string; size?: number } }>;
  };
  buttonContent?: string;
  mxI18n?: GalleryToolMxI18n;
}

type GalleryInstance = ImageGallery & {
  appendImage: (file: { url: string; name?: string; size?: number }) => void;
  readOnly?: boolean;
  ui: {
    nodes: {
      wrapper: HTMLElement;
      container: HTMLElement;
      controls: HTMLElement;
      fileButton: HTMLElement;
    };
  };
};

export default class MxGalleryTool extends ImageGallery {
  private mxConnectorUrl: string;
  private mxResourceId: number;
  private mxI18n: GalleryToolMxI18n;

  static get toolbox() {
    return {
      title: 'Gallery',
      icon: '<svg width="17" height="15" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><rect width="14" height="14" x="5" y="5" stroke="currentColor" stroke-width="2" rx="4" fill="none"/><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" fill="none" d="M5.14 15.32 8.69 11.57a1.5 1.5 0 0 1 2.28 0l4.14 4.43m-1.81-1.93 1.43-1.53a1.5 1.5 0 0 1 2.28 0l1.27 1.43"/><circle cx="13.8" cy="9.3" r="0.6" fill="currentColor"/></svg>',
    };
  }

  constructor(opts: BlockToolConstructorOptions) {
    const cfg = opts.config as GalleryToolMxConfig & Record<string, unknown>;
    const sortableJs = cfg.sortableJs;
    const maxElementCount =
      typeof cfg.maxElementCount === 'number' && cfg.maxElementCount > 0 ? cfg.maxElementCount : undefined;

    super({
      ...opts,
      config: {
        sortableJs,
        maxElementCount,
        uploader: cfg.uploader,
        buttonContent: cfg.buttonContent ?? cfg.mxI18n?.gallery_select_image ?? '',
      },
    });

    this.mxConnectorUrl = cfg.connectorUrl;
    this.mxResourceId = cfg.resourceId;
    this.mxI18n = cfg.mxI18n ?? {};
  }

  rendered(): void {
    super.rendered();
    this.injectBrowseButton();
  }

  private injectBrowseButton(): void {
    const self = this as unknown as GalleryInstance;
    if (self.readOnly) {
      return;
    }
    const controls = self.ui?.nodes?.controls;
    const fileBtn = self.ui?.nodes?.fileButton;
    if (!controls || !fileBtn || controls.querySelector('.mxeditorjs-gallery-tool__browse')) {
      return;
    }

    const browseBtn = document.createElement('button');
    browseBtn.type = 'button';
    browseBtn.className = 'cdx-button mxeditorjs-gallery-tool__browse';
    browseBtn.textContent = this.mxI18n.gallery_browse ?? 'Browse';
    browseBtn.title = this.mxI18n.gallery_browse_title ?? '';
    browseBtn.addEventListener('click', () => void this.openBrowse());
    fileBtn.after(browseBtn);
  }

  private browserStrings() {
    return {
      loading: this.mxI18n.loading,
      root: this.mxI18n.root,
      root_title: this.mxI18n.root_title,
      back: this.mxI18n.back,
      no_files_found: this.mxI18n.no_files_found,
    };
  }

  private async openBrowse(): Promise<void> {
    const self = this as unknown as GalleryInstance;
    const wrapper = self.ui?.nodes?.wrapper;
    const container = self.ui?.nodes?.container;
    if (!wrapper || !container) {
      return;
    }

    const host = document.createElement('div');
    host.className = 'mxeditorjs-gallery-browser-host';
    wrapper.appendChild(host);
    container.style.display = 'none';

    const cleanup = (): void => {
      host.remove();
      container.style.display = '';
    };

    const navigate = async (path?: string): Promise<void> => {
      host.innerHTML = `<div class="mxeditorjs-image-tool__loading">${this.mxI18n.loading ?? 'Loading...'}</div>`;
      try {
        const data = await fetchMediaBrowse(this.mxConnectorUrl, this.mxResourceId, path);
        renderMediaBrowser(host, data, this.browserStrings(), {
          onRoot: () => void navigate('__root__'),
          onBack: () => void navigate(data.parentPath ?? ''),
          onFolder: (folderPath) => void navigate(folderPath),
          onSelectFile: (file, storedUrl) => {
            self.appendImage({
              url: storedUrl,
              name: file.name,
              size: file.size,
            });
            cleanup();
          },
          onClose: cleanup,
        });
      } catch (e) {
        console.error('[mxEditorJs] Gallery browse error:', e);
        cleanup();
      }
    };

    await navigate();
  }

  validate(data: { files?: unknown[] }): boolean {
    if (!Array.isArray(data.files) || data.files.length === 0) {
      return true;
    }

    return data.files.every((file) => {
      if (!file || typeof file !== 'object') {
        return false;
      }
      const url = (file as { url?: string }).url;
      return typeof url === 'string' && url.trim() !== '';
    });
  }
}
