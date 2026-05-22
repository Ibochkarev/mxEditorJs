import AttachesTool from '@editorjs/attaches';

type AttachesData = {
  file?: Record<string, unknown>;
  title?: string;
};

/**
 * Allows empty attachment blocks while editing; sync filters them before persist.
 */
export default class MxAttachesTool extends AttachesTool {
  validate(data: AttachesData): boolean {
    const file = data?.file;
    if (!file || Object.keys(file).length === 0) {
      return true;
    }

    const url = file.url;
    return typeof url === 'string' && url.trim() !== '';
  }
}
