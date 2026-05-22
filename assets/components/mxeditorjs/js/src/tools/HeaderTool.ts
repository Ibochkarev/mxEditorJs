import Header from '@editorjs/header';

type HeaderData = {
  text?: string;
  level?: number;
};

/**
 * Allows empty headings while editing; sync filters them before persist.
 */
export default class MxHeaderTool extends Header {
  validate(data: HeaderData): boolean {
    const text = typeof data?.text === 'string' ? data.text : '';
    if (text.trim() === '') {
      return true;
    }
    return true;
  }
}
