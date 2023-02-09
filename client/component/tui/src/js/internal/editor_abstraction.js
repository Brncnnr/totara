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

import tui from 'tui/tui';
import apollo from 'tui/apollo_client';
// eslint-disable-next-line no-unused-vars
import { EditorInterface } from 'tui/editor';
import textareaFallback from './editor_textarea_fallback';
import configQuery from 'core/graphql/editor';
import { EditorContent, Format } from 'tui/editor';

/**
 *
 * @typedef {Object} StringWrapper - allows a string to be recursively concatenated
 * @property {string} val
 *
 * @param {Object} json
 * @param {StringWrapper} strWrapper
 * @returns {string}
 */
function extractText(json, strWrapper) {
  const paragraphNodes = ['doc', 'blockquote', 'list_item'];

  if (json.text) {
    strWrapper.val += json.text;
  }

  if (json.type === 'hard_break') {
    strWrapper.val += '\n';
  }

  if (Array.isArray(json.content) && json.content.length > 0) {
    json.content.forEach(node => {
      extractText(node, strWrapper);

      if (paragraphNodes.includes(json.type)) {
        strWrapper.val += '\n\n';
      }
    });
  }

  return strWrapper;
}

/**
 * Convert a serialized JSON_EDITOR doc string to PLAIN text string.
 * Strips out rich content
 *
 * @param {string} serialized
 * @returns {string}
 */
export function toPlain(serialized) {
  try {
    const json = JSON.parse(serialized);
    const { val } = extractText(json, { val: '' });
    return val;
  } catch (err) {
    console.error(
      'Failed to parse JSON_EDITOR content. Is it a serialized string?'
    );
    console.log(serialized);
    console.error(err);
    return serialized;
  }
}

/**
 * @typedef {Object} FormatConversionConfig
 * @property {number} from
 * @property {number} to
 *
 * Convert an unsupported EditorContent into the equivalent supported EditorContent
 *
 * @param {EditorContent} value
 * @param {FormatConversionConfig} config
 * @returns {EditorContent}
 */
export function reconcileFormats(value, { from, to }) {
  if (from === Format.JSON_EDITOR && to === Format.PLAIN) {
    return new EditorContent({
      originalFormat: Format.JSON_EDITOR,
      format: Format.PLAIN,
      content: toPlain(value.getContent()),
    });
  }

  // space for other potential format conversions

  return value;
}

/**
 * @typedef {Object} EditorIdentifier
 * @property {string} component
 * @property {string} area
 * @property {number} instanceId
 */

/**
 * Get editor configuration info from the server.
 *
 * @param {object} opts
 * @param {number} opts.format
 * @param {string} opts.variant
 * @param {object} opts.usageIdentifier
 * @params {array} opts.extraExtensions
 * @returns {EditorConfigResult}
 */
export async function getEditorConfig({
  format,
  variant,
  usageIdentifier,
  contextId,
  extraExtensions,
}) {
  const usageId = usageIdentifier;

  const result = await apollo.query({
    query: configQuery,
    variables: {
      framework: 'tui',
      format,
      variant_name: variant,
      context_id: contextId,
      extra_extensions: extraExtensions
        ? JSON.stringify(extraExtensions)
        : undefined,
      usage_identifier: usageId
        ? {
            component: usageId.component,
            area: usageId.area,
            instance_id: usageId.instanceId,
          }
        : null,
    },
  });

  const config = result.data.editor;

  const hasInterface = !!config.js_module;

  return new EditorConfigResult({
    name: config.name,
    interface: hasInterface ? config.js_module : textareaFallback,
    options:
      hasInterface && config.variant.options
        ? JSON.parse(config.variant.options)
        : {},
    contextId: config.context_id,
  });
}

class EditorConfigResult {
  /**
   * @private
   * @param {object} opts
   * @param {(string|EditorInterface)} opts.interface
   */
  constructor(opts) {
    this._name = opts.name;
    this._interface = opts.interface;
    this._options = opts.options;
    this._contextId = opts.contextId;
  }

  /**
   * Get editor interface object/instance.
   *
   * @returns {Promise<EditorInterface>}
   */
  async loadInterface() {
    if (typeof this._interface === 'string') {
      return tui.defaultExport(await tui.import(this._interface));
    } else {
      return this._interface;
    }
  }

  /**
   * @returns {string}
   */
  getName() {
    return this._name;
  }

  /**
   * @returns {object}
   */
  getEditorOptions() {
    return this._options;
  }

  /**
   * @returns {?number}
   */
  getContextId() {
    return this._contextId;
  }
}
