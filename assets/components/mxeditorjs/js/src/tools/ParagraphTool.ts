import type { BlockToolConstructorOptions } from '@editorjs/editorjs';
import Paragraph from '@editorjs/paragraph';

type ParagraphData = {
  text?: string;
};

/**
 * Paragraph with preserveBlank and safe validate (missing text must not fail save).
 */
export default class MxParagraphTool extends Paragraph {
  private readonly allowBlank: boolean;

  constructor(opts: BlockToolConstructorOptions) {
    super({
      ...opts,
      config: {
        ...(opts.config as Record<string, unknown> | undefined),
        preserveBlank: true,
      },
    });
    const cfg = opts.config as { preserveBlank?: boolean } | undefined;
    this.allowBlank = cfg?.preserveBlank !== false;
  }

  validate(data: ParagraphData): boolean {
    const text = typeof data?.text === 'string' ? data.text : '';
    if (text.trim() === '') {
      return this.allowBlank;
    }
    return true;
  }
}
