interface LinkConfig {
  connectorUrl: string;
  classPresets?: Record<string, string>;
  targetOptions?: Record<string, string>;
  relOptions?: Record<string, string>;
}

interface ResourceResult {
  id: number;
  pagetitle: string;
  uri: string;
  url: string;
  published: boolean;
}

interface LinkOptions {
  url: string;
  target: string;
  rel: string;
  classPreset: string;
  customClass: string;
  linkType: 'internal' | 'external' | 'email' | 'anchor' | 'phone';
}

export default class LinkAutocomplete {
  private config: LinkConfig;
  private button: HTMLElement | null = null;
  private api: any;
  private state: boolean = false;
  private searchTimeout: ReturnType<typeof setTimeout> | null = null;

  static get isInline() {
    return true;
  }

  static get title() {
    return 'Link';
  }

  static get sanitize() {
    return {
      a: { href: true, target: true, rel: true, class: true },
    };
  }

  constructor({ api, config }: { api: any; config: LinkConfig }) {
    this.api = api;
    this.config = config;
  }

  render(): HTMLElement {
    this.button = document.createElement('button');
    (this.button as HTMLButtonElement).type = 'button';
    this.button.classList.add('ce-inline-tool');
    this.button.innerHTML =
      '<svg width="15" height="14"><path d="M13.073 1.884c-.632-.598-1.392-.884-2.178-.884-.787 0-1.546.286-2.178.884L6.862 3.74c-.18.17-.36.37-.512.583l1.09 1.03.397-.378 1.855-1.754c.344-.326.78-.502 1.236-.502.457 0 .892.176 1.236.502.344.325.53.754.53 1.208 0 .454-.186.883-.53 1.208l-1.855 1.754-.397.378-.533.503-.397.378c-.344.325-.78.502-1.236.502-.457 0-.892-.177-1.236-.502l-1.09-1.03c-.157.204-.315.406-.478.589l1.136 1.074c.632.598 1.392.884 2.178.884.787 0 1.546-.286 2.178-.884l1.855-1.754c.632-.598.964-1.376.964-2.212 0-.836-.332-1.614-.964-2.212zM7.22 10.047l-1.09-1.03-.397.378-1.855 1.754c-.344.325-.78.502-1.236.502-.457 0-.892-.177-1.236-.502-.344-.326-.53-.754-.53-1.208 0-.455.186-.884.53-1.209l1.855-1.754.397-.378.533-.503.397-.378c.344-.326.78-.502 1.236-.502.457 0 .892.176 1.236.502l1.09 1.03c.157-.204.315-.406.478-.59L7.492 5.086c-.632-.598-1.392-.884-2.178-.884-.787 0-1.546.286-2.178.884L1.28 6.84C.648 7.439.316 8.217.316 9.053c0 .836.332 1.614.964 2.212.632.598 1.392.884 2.178.884.787 0 1.546-.286 2.178-.884l1.855-1.754c.18-.17.36-.37.512-.584l-1.09-1.03-.397.378-.296.28z"/></svg>';

    return this.button;
  }

  surround(range: Range): void {
    if (this.state) {
      this.showEditPopup(range);
      return;
    }

    this.showLinkInput(range);
  }

  checkState(): boolean {
    const anchor = this.api.selection.findParentTag('A');
    this.state = !!anchor;

    if (this.button) {
      this.button.classList.toggle('ce-inline-tool--active', this.state);
    }

    return this.state;
  }

  private detectLinkType(url: string): LinkOptions['linkType'] {
    if (url.startsWith('mailto:')) return 'email';
    if (url.startsWith('tel:')) return 'phone';
    if (url.startsWith('#')) return 'anchor';
    if (url.startsWith('http') && !url.includes(window.location.hostname)) return 'external';
    return 'internal';
  }

  private showLinkInput(range: Range, existingAnchor?: HTMLAnchorElement): void {
    const wrapper = document.createElement('div');
    wrapper.classList.add('mxeditorjs-link-popup');

    const existing: Partial<LinkOptions> = {};
    if (existingAnchor) {
      existing.url = existingAnchor.href;
      existing.target = existingAnchor.target || '_self';
      existing.rel = existingAnchor.rel || '';
      existing.customClass = existingAnchor.className || '';
      existing.linkType = this.detectLinkType(existingAnchor.href);
    }

    const linkTypeRow = this.createTypeSelector(existing.linkType || 'internal');
    wrapper.appendChild(linkTypeRow);

    const urlRow = document.createElement('div');
    urlRow.classList.add('mxeditorjs-link-row');
    const urlLabel = document.createElement('label');
    urlLabel.textContent = 'URL:';
    const urlInput = document.createElement('input');
    urlInput.type = 'text';
    urlInput.placeholder = 'URL or search resource...';
    urlInput.classList.add('mxeditorjs-link-input__field');
    urlInput.value = existing.url || '';
    urlRow.appendChild(urlLabel);
    urlRow.appendChild(urlInput);
    wrapper.appendChild(urlRow);

    const dropdown = document.createElement('div');
    dropdown.classList.add('mxeditorjs-link-input__dropdown');
    dropdown.style.display = 'none';
    wrapper.appendChild(dropdown);

    const targetRow = this.createTargetSelector(existing.target || '_self');
    wrapper.appendChild(targetRow);

    const relRow = this.createRelSelector(existing.rel || '');
    wrapper.appendChild(relRow);

    const classRow = this.createClassSelector(existing.customClass || '');
    wrapper.appendChild(classRow);

    const buttonRow = document.createElement('div');
    buttonRow.classList.add('mxeditorjs-link-buttons');

    const saveBtn = document.createElement('button');
    saveBtn.type = 'button';
    saveBtn.textContent = existingAnchor ? 'Update' : 'Insert';
    saveBtn.classList.add('mxeditorjs-link-btn', 'mxeditorjs-link-btn--primary');

    const cancelBtn = document.createElement('button');
    cancelBtn.type = 'button';
    cancelBtn.textContent = 'Cancel';
    cancelBtn.classList.add('mxeditorjs-link-btn');

    if (existingAnchor) {
      const removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.textContent = 'Remove';
      removeBtn.classList.add('mxeditorjs-link-btn', 'mxeditorjs-link-btn--danger');
      removeBtn.addEventListener('click', () => {
        this.unwrap(range);
        this.removePopup(wrapper);
      });
      buttonRow.appendChild(removeBtn);
    }

    buttonRow.appendChild(cancelBtn);
    buttonRow.appendChild(saveBtn);
    wrapper.appendChild(buttonRow);

    urlInput.addEventListener('input', () => {
      this.onSearchInput(urlInput.value, dropdown, range, wrapper);
    });

    urlInput.addEventListener('keydown', (e: KeyboardEvent) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        this.saveLink(wrapper, range, existingAnchor);
      }
      if (e.key === 'Escape') {
        this.removePopup(wrapper);
      }
    });

    saveBtn.addEventListener('click', () => {
      this.saveLink(wrapper, range, existingAnchor);
    });

    cancelBtn.addEventListener('click', () => {
      this.removePopup(wrapper);
    });

    const rect = range.getBoundingClientRect();
    wrapper.style.position = 'fixed';
    wrapper.style.left = Math.max(10, rect.left - 100) + 'px';
    wrapper.style.top = rect.bottom + 8 + 'px';
    wrapper.style.zIndex = '10000';

    document.body.appendChild(wrapper);
    urlInput.focus();

    const closeHandler = (e: MouseEvent) => {
      if (!wrapper.contains(e.target as Node) && e.target !== this.button) {
        this.removePopup(wrapper);
        document.removeEventListener('mousedown', closeHandler);
      }
    };
    setTimeout(() => document.addEventListener('mousedown', closeHandler), 100);
  }

  private showEditPopup(range: Range): void {
    const anchor = this.api.selection.findParentTag('A') as HTMLAnchorElement;
    if (anchor) {
      this.api.selection.expandToTag(anchor);
      const newRange = window.getSelection()?.getRangeAt(0);
      if (newRange) {
        this.showLinkInput(newRange, anchor);
      }
    }
  }

  private createTypeSelector(current: LinkOptions['linkType']): HTMLElement {
    const row = document.createElement('div');
    row.classList.add('mxeditorjs-link-row', 'mxeditorjs-link-types');

    const types: { value: LinkOptions['linkType']; label: string; icon: string }[] = [
      { value: 'internal', label: 'Page', icon: '📄' },
      { value: 'external', label: 'External', icon: '🔗' },
      { value: 'email', label: 'Email', icon: '✉️' },
      { value: 'anchor', label: 'Anchor', icon: '#' },
      { value: 'phone', label: 'Phone', icon: '📞' },
    ];

    types.forEach(type => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.classList.add('mxeditorjs-link-type-btn');
      btn.dataset.type = type.value;
      btn.title = type.label;
      btn.textContent = type.icon;
      if (type.value === current) {
        btn.classList.add('mxeditorjs-link-type-btn--active');
      }
      btn.addEventListener('click', () => {
        row.querySelectorAll('.mxeditorjs-link-type-btn').forEach(b =>
          b.classList.remove('mxeditorjs-link-type-btn--active')
        );
        btn.classList.add('mxeditorjs-link-type-btn--active');
        this.updateUrlPlaceholder(row.closest('.mxeditorjs-link-popup')!, type.value);
      });
      row.appendChild(btn);
    });

    return row;
  }

  private updateUrlPlaceholder(wrapper: Element, type: LinkOptions['linkType']): void {
    const input = wrapper.querySelector('.mxeditorjs-link-input__field') as HTMLInputElement;
    if (!input) return;

    const placeholders: Record<LinkOptions['linkType'], string> = {
      internal: 'Search page or enter URL...',
      external: 'https://example.com',
      email: 'email@example.com',
      anchor: '#section-id',
      phone: '+1234567890',
    };
    input.placeholder = placeholders[type];
  }

  private createTargetSelector(current: string): HTMLElement {
    const row = document.createElement('div');
    row.classList.add('mxeditorjs-link-row');

    const label = document.createElement('label');
    label.textContent = 'Target:';

    const select = document.createElement('select');
    select.classList.add('mxeditorjs-link-select');
    select.dataset.field = 'target';

    const options = this.config.targetOptions || {
      '_self': 'Same window',
      '_blank': 'New window',
    };

    Object.entries(options).forEach(([value, text]) => {
      const opt = document.createElement('option');
      opt.value = value;
      opt.textContent = text;
      if (value === current) opt.selected = true;
      select.appendChild(opt);
    });

    row.appendChild(label);
    row.appendChild(select);
    return row;
  }

  private createRelSelector(current: string): HTMLElement {
    const row = document.createElement('div');
    row.classList.add('mxeditorjs-link-row');

    const label = document.createElement('label');
    label.textContent = 'Rel:';

    const select = document.createElement('select');
    select.classList.add('mxeditorjs-link-select');
    select.dataset.field = 'rel';

    const options = this.config.relOptions || {
      '': 'None',
      'nofollow': 'nofollow',
      'noopener noreferrer': 'noopener noreferrer',
    };

    Object.entries(options).forEach(([value, text]) => {
      const opt = document.createElement('option');
      opt.value = value;
      opt.textContent = text;
      if (value === current) opt.selected = true;
      select.appendChild(opt);
    });

    row.appendChild(label);
    row.appendChild(select);
    return row;
  }

  private createClassSelector(current: string): HTMLElement {
    const row = document.createElement('div');
    row.classList.add('mxeditorjs-link-row');

    const label = document.createElement('label');
    label.textContent = 'Style:';

    const presets = this.config.classPresets;
    if (presets && Object.keys(presets).length > 0) {
      const select = document.createElement('select');
      select.classList.add('mxeditorjs-link-select');
      select.dataset.field = 'classPreset';

      Object.entries(presets).forEach(([key, value]) => {
        const opt = document.createElement('option');
        opt.value = key;
        opt.textContent = key;
        opt.title = value || '(no classes)';
        if (current && value === current) opt.selected = true;
        select.appendChild(opt);
      });

      row.appendChild(label);
      row.appendChild(select);

      const customLabel = document.createElement('label');
      customLabel.textContent = 'Custom:';
      const customInput = document.createElement('input');
      customInput.type = 'text';
      customInput.placeholder = 'CSS classes';
      customInput.classList.add('mxeditorjs-link-custom-class');
      customInput.dataset.field = 'customClass';
      customInput.value = current || '';

      row.appendChild(customLabel);
      row.appendChild(customInput);
    } else {
      const input = document.createElement('input');
      input.type = 'text';
      input.placeholder = 'CSS classes';
      input.classList.add('mxeditorjs-link-custom-class');
      input.dataset.field = 'customClass';
      input.value = current || '';

      row.appendChild(label);
      row.appendChild(input);
    }

    return row;
  }

  private saveLink(wrapper: HTMLElement, range: Range, existingAnchor?: HTMLAnchorElement): void {
    const urlInput = wrapper.querySelector('.mxeditorjs-link-input__field') as HTMLInputElement;
    const targetSelect = wrapper.querySelector('[data-field="target"]') as HTMLSelectElement;
    const relSelect = wrapper.querySelector('[data-field="rel"]') as HTMLSelectElement;
    const classPresetSelect = wrapper.querySelector('[data-field="classPreset"]') as HTMLSelectElement;
    const customClassInput = wrapper.querySelector('[data-field="customClass"]') as HTMLInputElement;
    const activeTypeBtn = wrapper.querySelector('.mxeditorjs-link-type-btn--active') as HTMLElement;

    let url = urlInput?.value.trim() || '';
    const target = targetSelect?.value || '_self';
    const rel = relSelect?.value || '';
    const linkType = (activeTypeBtn?.dataset.type as LinkOptions['linkType']) || 'internal';

    if (!url) {
      this.removePopup(wrapper);
      return;
    }

    if (linkType === 'email' && !url.startsWith('mailto:')) {
      url = 'mailto:' + url;
    } else if (linkType === 'phone' && !url.startsWith('tel:')) {
      url = 'tel:' + url.replace(/[^\d+]/g, '');
    } else if (linkType === 'anchor' && !url.startsWith('#')) {
      url = '#' + url;
    }

    let cssClass = '';
    if (classPresetSelect && this.config.classPresets) {
      const presetName = classPresetSelect.value;
      cssClass = this.config.classPresets[presetName] || '';
    }
    if (customClassInput?.value) {
      cssClass = cssClass ? `${cssClass} ${customClassInput.value}` : customClassInput.value;
    }

    if (existingAnchor) {
      existingAnchor.href = url;
      existingAnchor.target = target;
      existingAnchor.rel = rel;
      existingAnchor.className = cssClass;
    } else {
      this.wrapLink(range, url, target, rel, cssClass);
    }

    this.removePopup(wrapper);
  }

  private onSearchInput(query: string, dropdown: HTMLElement, range: Range, wrapper: HTMLElement): void {
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout);
    }

    const activeTypeBtn = wrapper.querySelector('.mxeditorjs-link-type-btn--active') as HTMLElement;
    const linkType = activeTypeBtn?.dataset.type || 'internal';

    if (linkType !== 'internal') {
      dropdown.style.display = 'none';
      return;
    }

    if (query.length < 2) {
      dropdown.style.display = 'none';
      return;
    }

    if (query.startsWith('http://') || query.startsWith('https://') || query.startsWith('/') || query.startsWith('mailto:') || query.startsWith('#')) {
      dropdown.style.display = 'none';
      return;
    }

    this.searchTimeout = setTimeout(() => this.searchResources(query, dropdown, range, wrapper), 300);
  }

  private async searchResources(query: string, dropdown: HTMLElement, range: Range, wrapper: HTMLElement): Promise<void> {
    const form = new FormData();
    form.append('action', 'link/search');
    form.append('query', query);

    try {
      const response = await fetch(this.config.connectorUrl, {
        method: 'POST',
        body: form,
      });

      const result = await response.json();
      if (!result.success || !result.data?.length) {
        dropdown.style.display = 'none';
        return;
      }

      dropdown.innerHTML = '';
      dropdown.style.display = 'block';

      result.data.forEach((res: ResourceResult) => {
        const item = document.createElement('div');
        item.classList.add('mxeditorjs-link-input__item');

        const title = document.createElement('span');
        title.classList.add('mxeditorjs-link-input__title');
        title.textContent = res.pagetitle;

        const id = document.createElement('span');
        id.classList.add('mxeditorjs-link-input__id');
        id.textContent = `#${res.id}`;

        const uri = document.createElement('span');
        uri.classList.add('mxeditorjs-link-input__uri');
        uri.textContent = res.uri || '/';

        item.appendChild(title);
        item.appendChild(id);
        item.appendChild(uri);

        item.addEventListener('click', () => {
          const urlInput = wrapper.querySelector('.mxeditorjs-link-input__field') as HTMLInputElement;
          if (urlInput) {
            urlInput.value = res.url;
          }
          dropdown.style.display = 'none';
        });

        dropdown.appendChild(item);
      });
    } catch (e) {
      dropdown.style.display = 'none';
      console.error('[mxEditorJs] Link search error:', e);
    }
  }

  private wrapLink(range: Range, url: string, target: string, rel: string, cssClass: string): void {
    const selectedText = range.extractContents();
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.appendChild(selectedText);

    if (target && target !== '_self') {
      anchor.target = target;
    }
    if (rel) {
      anchor.rel = rel;
    }
    if (cssClass) {
      anchor.className = cssClass;
    }

    range.insertNode(anchor);
    this.api.selection.expandToTag(anchor);
  }

  private unwrap(range: Range): void {
    const anchor = this.api.selection.findParentTag('A');
    if (anchor) {
      this.api.selection.expandToTag(anchor);
      const text = document.createTextNode(anchor.textContent || '');
      anchor.parentNode?.replaceChild(text, anchor);
    }
  }

  private removePopup(wrapper: HTMLElement): void {
    wrapper.remove();
  }
}
