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
 * @module editor_weka
 */

import { Format } from 'tui/editor';
import Weka from 'editor_weka/components/Weka';
import WekaValue from './WekaValue';

export default {
  getComponent() {
    return Weka;
  },

  getProps({
    contextId,
    config,
    fileItemId,
    placeholder,
    compact,
    usageIdentifer,
    extraExtensions,
    disabled,
    ariaLabel,
    ariaLabelledby,
    ariaDescribedby,
    ariaInvalid,
    stickyToolbar,
  }) {
    return {
      fileItemId,
      usageIdentifer,
      extraExtensions,
      options: {
        context_id: contextId,
        extensions: config.extensions,
        files: null,
        repository_data: null,
        showtoolbar: config.showtoolbar,
      },
      placeholder,
      compact,
      disabled,
      ariaLabel,
      ariaLabelledby,
      ariaDescribedby,
      ariaInvalid,
      stickyToolbar,
    };
  },

  /**
   * Convert string to WekaValue.
   *
   * @param {string} content
   * @returns {WekaValue}
   */
  rawToValue(content) {
    if (!content) {
      return WekaValue.empty();
    }
    try {
      return WekaValue.fromDoc(JSON.parse(content));
    } catch (e) {
      console.error('Failed to parse Weka document');
      console.log(content);
      console.error(e);
      return WekaValue.empty();
    }
  },

  /**
   * Convert WekaValue to string.
   *
   * @param {WekaValue} content
   * @returns {string}
   */
  valueToRaw(content) {
    return JSON.stringify(content.getDoc());
  },

  isContentEmpty(value) {
    return value.isEmpty;
  },

  /**
   * @returns {Format}
   */
  getValueFormat() {
    return Format.JSON_EDITOR;
  },

  /**
   * If this editor is picked and we don't have a specified format to use, use
   * this format.
   */
  getPreferredFormat() {
    return Format.JSON_EDITOR;
  },

  forceRecreate: true,

  /**
   * Checks that if the format is supported by the weka editor.
   *
   * @param {Number} format
   * @return {Boolean}
   */
  supportsFormat(format) {
    return Format.JSON_EDITOR === format;
  },
};
