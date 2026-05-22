/**
 * Shared MODX Media Source browser for image tools (Image, Gallery).
 */

export interface MediaBrowserFile {
  name: string;
  url: string;
  size: number;
  modified: number;
  isImage: boolean;
}

export interface MediaBrowserFolder {
  name: string;
  path: string;
}

export interface MediaBrowserPayload {
  files: MediaBrowserFile[];
  folders: MediaBrowserFolder[];
  path: string;
  parentPath: string | null;
}

export interface MediaBrowserStrings {
  loading?: string;
  root?: string;
  root_title?: string;
  back?: string;
  no_files_found?: string;
}

export function normalizeFileUrlForDisplay(url: string): string {
  if (!url) return url;
  if (/^https?:\/\//i.test(url)) return url;
  try {
    return new URL(url, window.location.origin).href;
  } catch {
    return url;
  }
}

export async function fetchMediaBrowse(
  connectorUrl: string,
  resourceId: number,
  path?: string
): Promise<MediaBrowserPayload> {
  const form = new FormData();
  form.append('action', 'media/browse');
  form.append('resource_id', String(resourceId));
  form.append('type', 'image');
  if (path) {
    form.append('path', path);
  }

  const response = await fetch(connectorUrl, {
    method: 'POST',
    body: form,
  });

  const result = await response.json();
  if (!result.success) {
    throw new Error(result.message || 'Browse failed');
  }
  return result.data as MediaBrowserPayload;
}

export interface RenderMediaBrowserHandlers {
  onRoot: () => void;
  onBack: () => void;
  onFolder: (folderPath: string) => void;
  /** Store relative/API URL in saved JSON; use display URL for thumbnails */
  onSelectFile: (file: MediaBrowserFile, storedUrl: string, displayUrl: string) => void;
  onClose: () => void;
}

const css = {
  browser: 'mxeditorjs-image-browser',
  header: 'mxeditorjs-image-browser__header',
  root: 'mxeditorjs-image-browser__root',
  back: 'mxeditorjs-image-browser__back',
  path: 'mxeditorjs-image-browser__path',
  close: 'mxeditorjs-image-browser__close',
  grid: 'mxeditorjs-image-browser__grid',
  item: 'mxeditorjs-image-browser__item',
  folder: 'mxeditorjs-image-browser__folder',
  file: 'mxeditorjs-image-browser__file',
  thumb: 'mxeditorjs-image-browser__thumb',
  icon: 'mxeditorjs-image-browser__icon',
  name: 'mxeditorjs-image-browser__name',
  empty: 'mxeditorjs-image-browser__empty',
};

export function renderMediaBrowser(
  mount: HTMLElement,
  data: MediaBrowserPayload,
  strings: MediaBrowserStrings,
  handlers: RenderMediaBrowserHandlers
): void {
  mount.innerHTML = '';

  const browser = document.createElement('div');
  browser.classList.add(css.browser);

  const header = document.createElement('div');
  header.classList.add(css.header);

  const rootBtn = document.createElement('button');
  rootBtn.type = 'button';
  rootBtn.classList.add(css.root);
  rootBtn.textContent = strings.root ?? 'Root';
  rootBtn.title = strings.root_title ?? '';
  rootBtn.addEventListener('click', () => handlers.onRoot());
  header.appendChild(rootBtn);

  if (data.parentPath != null) {
    const backBtn = document.createElement('button');
    backBtn.type = 'button';
    backBtn.classList.add(css.back);
    backBtn.textContent = strings.back ?? '← Back';
    backBtn.addEventListener('click', () => handlers.onBack());
    header.appendChild(backBtn);
  }

  const pathSpan = document.createElement('span');
  pathSpan.classList.add(css.path);
  pathSpan.textContent = data.path || '/';
  pathSpan.title = data.path || '/';
  header.appendChild(pathSpan);

  const closeBtn = document.createElement('button');
  closeBtn.type = 'button';
  closeBtn.classList.add(css.close);
  closeBtn.textContent = '×';
  closeBtn.addEventListener('click', () => handlers.onClose());
  header.appendChild(closeBtn);

  browser.appendChild(header);

  const grid = document.createElement('div');
  grid.classList.add(css.grid);

  data.folders.forEach((folder) => {
    const item = document.createElement('div');
    item.classList.add(css.item, css.folder);
    const icon = document.createElement('span');
    icon.classList.add(css.icon);
    icon.textContent = '📁';
    const nameEl = document.createElement('span');
    nameEl.classList.add(css.name);
    nameEl.textContent = folder.name;
    item.appendChild(icon);
    item.appendChild(nameEl);
    item.addEventListener('click', () => handlers.onFolder(folder.path));
    grid.appendChild(item);
  });

  data.files.forEach((file) => {
    const item = document.createElement('div');
    item.classList.add(css.item, css.file);

    const displayUrl = normalizeFileUrlForDisplay(file.url);
    const storedUrl = file.url;

    if (file.isImage) {
      const img = document.createElement('img');
      img.loading = 'lazy';
      img.src = displayUrl;
      img.alt = file.name;
      img.classList.add(css.thumb);
      img.addEventListener('error', () => {
        img.style.display = 'none';
        const fallback = document.createElement('span');
        fallback.classList.add(css.icon);
        fallback.setAttribute('aria-hidden', 'true');
        fallback.textContent = '🖼';
        item.insertBefore(fallback, item.querySelector(`.${css.name}`));
      });
      item.appendChild(img);
    } else {
      const icon = document.createElement('span');
      icon.classList.add(css.icon);
      icon.textContent = '📄';
      item.appendChild(icon);
    }

    const name = document.createElement('span');
    name.classList.add(css.name);
    name.textContent = file.name;
    name.title = file.name;
    item.appendChild(name);

    item.addEventListener('click', () => handlers.onSelectFile(file, storedUrl, displayUrl));

    grid.appendChild(item);
  });

  if (data.files.length === 0 && data.folders.length === 0) {
    const empty = document.createElement('div');
    empty.classList.add(css.empty);
    empty.textContent = strings.no_files_found ?? 'No files found';
    grid.appendChild(empty);
  }

  browser.appendChild(grid);
  mount.appendChild(browser);
}
