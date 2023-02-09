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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @module editor_weka
 */

import { Plugin, PluginKey } from 'ext_prosemirror/state';

const has = Object.prototype.hasOwnProperty;
export const pluginKey = new PluginKey('attachment');

/**
 *
 * @param {{
 *   onDrop: Function|null,
 *   onKeyDown: Function|null,
 * }} props
 *
 * @return {{
 *   onKeyDown: Function,
 *   onDrop: Function,
 * }}
 */
function parseProps(props) {
  // Note, this is intentional for more spaces to add onDragFilesEnter and onDragFilesLeave event.
  const fns = ['onDrop', 'onKeyDown'];
  let rtn = {};
  fns.forEach(
    /**
     *
     * @param {String} fnName
     */
    fnName => {
      if (!has.call(props, fnName) || typeof props[fnName] !== 'function') {
        rtn[fnName] = () => false;
      } else {
        rtn[fnName] = props[fnName];
      }
    }
  );

  return rtn;
}

/**
 *
 * @param {{
 *   onDrop: Function|null,
 *   onKeyDown: Function|null,
 * }} props
 */
export function attachment(props) {
  const { onDrop, onKeyDown } = parseProps(props);

  return new Plugin({
    key: pluginKey,
    state: {
      init() {
        return {};
      },

      apply() {
        return {};
      },
    },

    props: {
      handleDOMEvents: {
        /**
         *
         * @param {EditorView} view
         * @param {Event} event
         * @return {Boolean}
         */
        drop(view, event) {
          return onDrop({ view, event });
        },
      },

      /**
       *
       * @param {EditorView} view
       * @param {event} event
       */
      handleKeyDown(view, event) {
        return onKeyDown({ view, event });
      },
    },
  });
}
