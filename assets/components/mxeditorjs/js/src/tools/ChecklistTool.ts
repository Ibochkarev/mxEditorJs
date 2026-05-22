import Checklist from '@editorjs/checklist';

type ChecklistItem = {
  text?: string;
  checked?: boolean;
};

type ChecklistData = {
  items?: ChecklistItem[];
};

/**
 * Allows empty checklist while editing; sync filters it before persist.
 */
export default class MxChecklistTool extends Checklist {
  validate(data: ChecklistData): boolean {
    if (!Array.isArray(data?.items) || data.items.length === 0) {
      return true;
    }
    return data.items.every((item) => item && typeof item === 'object');
  }
}
