/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * Totara Enterprise Extensions is provided only to Totara
 * Learning Solutions LTD's customers and partners, pursuant to
 * the terms and conditions of a separate agreement with Totara
 * Learning Solutions LTD or its affiliate.
 *
 * If you do not have an agreement with Totara Learning Solutions
 * LTD, you may not access, use, modify, or distribute this software.
 * Please contact [licensing@totaralearning.com] for more information.
 *
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module tui
 */

/**
 * @typedef {Object} EditorInterface
 * @property {*} getComponent Get component to render editor with.
 * @property {(opts: {
 *  contextId: ?number,
 *  config: object,
 *  format: number,
 *  fileItemId: ?number,
 *  placeholder: ?string,
 *  compact: boolean,
 *  usageIdentifier: ?{component: string, area: string}
 *  extraExtensions: ?Array<{name: string, options?: object}>
 *  disabled: boolean,
 *  ariaLabel: ?string,
 *  ariaLabelledby: ?string,
 *  ariaDescribedby: ?string,
 *  ariaInvalid: ?string,
 * }) => object} getProps Get props to pass to editor component.
 * @property {(content: string, format: number) => any} rawToValue
 *   Convert raw (serialzed) value to something the component can understand
 * @property {(value: any, format: number) => string} valueToRaw
 *   Convert editor-specific value to serialized string.
 * @property {(value: any) => boolean} isContentEmpty
 *   Check if editor-specific content is empty.
 * @property {() => Format} getPreferredFormat
 *   If this editor is picked and we don't have a specified format to use, use this format.
 * @property {(format: Format) => boolean} supportsFormat
 *   Check if the Editor supports a format.
 * @property {(value: any) => Format} getValueFormat
 *   Get the value's format
 * @property {boolean} forceRecreate
 *   Forcibly recreate the editor if its options (aside from content) change.
 *   Defaults to false. If the editor can't handle having *all* of its props
 *   updated at runtime this should be set to true.
 */

export class EditorContent {
  /**
   * @param {object} opts
   * @param {Format} opts.format Format of the content.
   * @param {string} opts.content Content value.
   * @param {number} opts.fileItemId Draft ID, required for file uploads to function.
   */
  constructor({ format = null, content = null, fileItemId = null } = {}) {
    this.format = format;
    this._originalFormat = format;
    this._content = content;
    this.fileItemId = fileItemId;

    /** @type {EditorInterface} */
    this._nativeEditor = null;
    /**
     * Native value - once you start editing, this this will be populated and
     * `_content` will be null.
     *
     * Content will be re-serialized on demand as this may be expensive.
     */
    this._nativeValue = null;
    /** @type {Map<EditorInterface, any>} */
    this._nativeMap = null;

    // prevent this from becoming reactive
    // (reactivity is unneccesary as it is externally immutable)
    Object.preventExtensions(this);
  }

  /**
   * Create a new EditorContent based off this one with an updated native value.
   *
   * @internal
   * @param {EditorInterface} editor
   * @param {*} value
   * @returns {EditorContent}
   */
  _updateNativeValue(editor, value) {
    const inst = new EditorContent({
      format: editor.getValueFormat(value),
      fileItemId: this.fileItemId,
    });
    inst._nativeEditor = editor;
    inst._nativeValue = value;
    inst._originalFormat = this._originalFormat;
    return inst;
  }

  /**
   * Get native value to pass to editor.
   *
   * @internal
   * @param {EditorInterface} editor
   * @returns {*}
   */
  _getNativeValue(editor) {
    // fast path for single editor (standard case)
    if (!this._nativeEditor) {
      this._nativeEditor = editor;
      this._nativeValue = editor.rawToValue(this._content, this.format);
    }

    if (this._nativeEditor && this._nativeEditor == editor) {
      return this._nativeValue;
    }

    // different editor (very rare case)
    if (!this._nativeMap) {
      this._nativeMap = new Map();
    }

    if (!this._nativeMap.has(editor)) {
      this._nativeMap.set(
        editor,
        editor.rawToValue(this.getContent(), this.format)
      );
    }

    return this._nativeMap.get(editor);
  }

  /**
   * Serialize editor content to a string.
   *
   * @returns {string}
   */
  getContent() {
    if (this._content) {
      return this._content;
    }
    if (this._nativeEditor && this._nativeValue) {
      this._content = this._nativeEditor.valueToRaw(
        this._nativeValue,
        this.format
      );
      return this._content;
    }
    return null;
  }

  /**
   * @typedef {Object} EditorContentJSON
   * @property {?number} draft_id
   * @property {number} format
   * @property {object} content
   */

  /**
   * Allows EditorContent to be stringified for sending over the wire
   * or displayed in development tools.
   *
   * @returns EditorContentJSON
   */
  toJSON() {
    return {
      draft_id: this.fileItemId,
      format: this.format,
      content: this.getContent(),
    };
  }

  /**
   * Get if there is any content.
   *
   * @returns {boolean}
   */
  get isEmpty() {
    // If we have a native value available, check that way rather than
    // inspecting content (typically more performant, especially for Weka as we
    // are skipping the serialization).
    if (this._nativeEditor && this._nativeValue) {
      return this._nativeEditor.isContentEmpty(this._nativeValue);
    }

    const content = this.getContent();
    if (this.format === Format.JSON_EDITOR) {
      return isEmptyJsonEditor(content);
    } else {
      return !content;
    }
  }
}

/**
 * Check if some JSON_EDITOR content is empty or not.
 *
 * @param {string} content
 * @returns {boolean}
 */
function isEmptyJsonEditor(content) {
  if (!content) {
    return true;
  }
  let doc = null;
  try {
    doc = JSON.parse(content);
  } catch (e) {
    /* doc is null */
  }
  if (!doc) {
    return true;
  }

  // no nodes in document
  if (childCount(doc) === 0) {
    return true;
  }
  // only an empty paragraph
  if (childCount(doc) === 1) {
    const child = doc.content[0];
    return child.type == 'paragraph' && childCount(child) === 0;
  }
  return false;
}

function childCount(node) {
  /* istanbul ignore next */
  if (!node) return 0;
  if (!node.content) return 0;
  return node.content.length;
}

/**
 * @readonly
 * @enum {number}
 */
export const Format = {
  MOODLE: 0,
  HTML: 1,
  PLAIN: 2,
  /** @deprecated */
  WIKI: 3,
  MARKDOWN: 4,
  JSON_EDITOR: 5,
};
