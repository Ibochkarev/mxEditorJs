declare module '@kiberpro/editorjs-gallery' {
  import type { BlockToolConstructorOptions } from '@editorjs/editorjs';

  export default class ImageGallery {
    constructor(opts: BlockToolConstructorOptions);
    render(): HTMLElement;
    rendered(): void;
    save(): {
      files: Array<{ url: string; name?: string; size?: number }>;
      caption?: string;
      style?: string;
    };
    validate(data: { files?: unknown[] }): boolean;
    renderSettings(): HTMLElement;
    static get toolbox(): { title: string; icon: string };
    static get isReadOnlySupported(): boolean;
  }
}
