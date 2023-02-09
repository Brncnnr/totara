/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Arshad Anwer <arshad.anwer@totaralearning.com>
 * @module weka_notification_placeholder
 */

import { debounce } from 'tui/util';
import { Plugin, PluginKey } from 'ext_prosemirror/state';
import Suggestion from 'editor_weka/helpers/suggestion';
import PlaceholderSuggestion from 'weka_notification_placeholder/components/suggestion/Placeholder';

export const REGEX = new RegExp(`\\[([a-z_:]+]?)?$`, 'ig');

/**
 *
 * @param {Editor} editor
 * @param {String} resolverClassName
 * @return {Plugin}
 */
export default function(editor, resolverClassName) {
  const key = new PluginKey('placeholders');
  let suggestion = new Suggestion(editor);

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
          } else if (!view.editable) {
            // Editor is disabled, do not apply anything.
            return;
          }

          // remove [ when passing value to state/component
          const ammendedText = text.slice(1);

          suggestion.showList({
            view,
            component: {
              name: 'totara_notification_placeholder',
              component: PlaceholderSuggestion,
              attrs: (key, label) => {
                return {
                  key: key,
                  label: label,
                };
              },
              props: {
                resolverClassName: resolverClassName,
                contextId: editor.identifier.contextId,
                pattern: ammendedText,
              },
            },
            state: {
              text: ammendedText,
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
        REGEX.lastIndex = 0;
        return suggestion.apply(transaction, oldState, REGEX);
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
