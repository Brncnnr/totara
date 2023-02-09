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

import { debounce } from 'tui/util';
import { Plugin, PluginKey } from 'ext_prosemirror/state';
import Suggestion from '../helpers/suggestion';
import UserSuggestion from 'editor_weka/components/suggestion/User';

/**
 *
 * @param editor
 *
 * @return {Plugin}
 */
export default function(editor) {
  const key = new PluginKey('mentions');
  let suggestion = new Suggestion(editor);
  const regex = new RegExp(`@[^@\\s]+$`, 'g');

  return new Plugin({
    key: key,

    view() {
      return {
        /**
         *
         * @param {EditorView} view
         */
        update: debounce(view => {
          const { text, active, range } = this.key.getState(view.state);
          suggestion.destroyInstance();

          if (!text || !active) {
            return;
          }

          suggestion.showList({
            view,
            component: {
              name: 'mention',
              component: UserSuggestion,
              attrs: (id, name) => {
                return {
                  id: id,
                  display: name,
                };
              },
            },
            state: {
              text: text.slice(1),
              active,
              range,
            },
          });
        }, 250),
      };
    },

    state: {
      init() {
        return {
          active: false,
          range: {},
          text: null,
        };
      },

      /**
       *
       * @param {Transaction} transaction
       * @param {Object} oldState
       *
       * @return {Object}
       */
      apply(transaction, oldState) {
        // Reset last index in order to perform the regex again at the start of the string.
        regex.lastIndex = 0;
        return suggestion.apply(transaction, oldState, regex);
      },
    },

    props: {
      /**
       *
       * @param {EditorView} view
       * @param {KeyboardEvent} event
       */
      handleKeyDown(view, event) {
        if (event.key === 'Escape' || event.key === 'Esc') {
          const { active } = this.getState(view.state);
          if (!active) {
            return false;
          }

          suggestion.destroyInstance();
          view.focus();
          event.stopPropagation();

          // Returning true to stop the the propagation in the parent editor.
          return true;
        }
      },
    },
  });
}
