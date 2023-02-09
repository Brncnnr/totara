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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @module editor_weka
 */

import { Plugin, PluginKey } from 'ext_prosemirror/state';
import HashtagSuggestion from 'editor_weka/components/suggestion/Hashtag';
import Suggestion from '../helpers/suggestion';
import { debounce } from 'tui/util';

export const REGEX = new RegExp(`#[^#\\s]+$`, 'g');

/**
 *
 * @param editor
 *
 * @return {Plugin}
 */
export default function(editor) {
  const key = new PluginKey('hashtags');
  let suggestion = new Suggestion(editor);

  return new Plugin({
    key: key,

    view() {
      return {
        /**
         *
         * @param {EditorView} view
         */
        update: debounce(async view => {
          const { text, active, range } = this.key.getState(view.state);
          suggestion.destroyInstance();

          if (!text || !active) {
            return;
          }

          await suggestion.showList({
            view,
            component: {
              name: 'hashtag',
              component: HashtagSuggestion,
              attrs: (id, name) => {
                return { text: name };
              },
            },
            state: {
              text: text.slice(1),
              active,
              range,
            },
          });
        }, 500),
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
       * @param {Transaction} transaction
       * @param {Object} oldState
       * @return {Object}
       */
      apply(transaction, oldState) {
        // Reset REGEX so that we can start at the start of the string.
        REGEX.lastIndex = 0;
        return suggestion.apply(transaction, oldState, REGEX);
      },
    },

    props: {
      /**
       *
       * @param {EditorView} view
       * @param {Keyboardevent} event
       *
       * @return {Boolean}
       */
      handleKeyDown(view, event) {
        const { active, text, range } = this.getState(view.state);
        if (!active) {
          return false;
        }

        const validKeys = [
          ' ',
          'Tab',
          'Enter',
          'PageUp',
          'PageDown',
          'Home',
          'End',
          'Escape',
          'Spacebar', // For ie11
          'Esc',
        ];

        if (validKeys.includes(event.key)) {
          editor.execute((state, dispatch) => {
            dispatch(
              state.tr.replaceWith(
                range.from,
                range.to,
                state.schema.node('hashtag', { text: text.slice(1) })
              )
            );
            event.stopPropagation();
            return true;
          });
        }

        return false;
      },

      /**
       *
       * @param {EditorView} view
       */
      handleClick(view) {
        const { active, text, range } = this.getState(view.state);
        if (!active) {
          return false;
        }

        editor.execute((state, dispatch) => {
          const node = state.schema.node('hashtag', { text: text.slice(1) });

          dispatch(state.tr.replaceWith(range.from, range.to, node));
        });

        return true;
      },
    },
  });
}
