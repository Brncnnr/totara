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
import { ResolvedPos } from 'ext_prosemirror/model';

/**
 *
 * @param {ResolvedPos} $position
 * @param {Array} emojis
 * @return {{range: {from: *, to: *}, text: string}|null}
 */
function matcher($position, emojis) {
  if (!$position || !($position instanceof ResolvedPos)) {
    return null;
  }

  const text = $position.doc.textBetween(
    $position.start(),
    $position.end(),
    '\0',
    '\0'
  );

  // Empty string, no point to return the matcher.
  if (text === '') {
    return null;
  }

  // Leave when no emojis.
  if (!emojis) {
    return null;
  }

  let match, regex;
  for (let i = 0; i < emojis.length; ++i) {
    let emoji = emojis[i];

    // Skip empty patterns.
    if (emoji.pattern === '') {
      continue;
    }

    regex = new RegExp(emoji.pattern, 'g');
    while ((match = regex.exec(text))) {
      // Confirm that first character is a space or the beginning of the line.
      let beforeCharacters = match.input.slice(
        Math.max(0, match.index - 1),
        match.index
      );

      // If the before characters are not an empty string, but any other characters, then we skip this functionality.
      if (!/^[\s\0]?$/.test(beforeCharacters)) {
        continue;
      }

      // The absolute position of the match in the document
      let from = match.index + $position.start(),
        to = from + match[0].length;

      // If the position is located within matched substring, return that range.
      if (from < $position.pos && to >= $position.pos) {
        return {
          range: {
            from,
            to,
          },
          text: match[0],
          emoji: emoji,
        };
      }
    }
  }

  return null;
}

/**
 *
 * @return {Plugin}
 */
export default function(editor, emojis, charArray) {
  const key = new PluginKey('emojis');
  let apply = false;

  return new Plugin({
    key,

    appendTransaction(txns, oldState, state) {
      const { text, active, range, emoji } = key.getState(state);
      if (!text || !active) {
        return null;
      }

      const node = state.schema.node('emoji', {
        id: emoji.id,
        shortcode: emoji.shortcode,
      });

      return state.tr.replaceWith(range.from, range.to, node);
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
        const { selection } = transaction;

        let next = Object.assign({}, oldState);
        next.active = false;

        if (!apply) {
          return next;
        }

        if (selection.from === selection.to) {
          const match = matcher(selection.$from, emojis);

          if (match) {
            next.active = true;
            next.range = match.range;
            next.text = match.text;
            next.emoji = match.emoji;
          }
        }

        if (!next.active) {
          next.range = {};
          next.text = null;
        }

        return next;
      },
    },

    props: {
      /**
       *
       * @param {EditorView} view
       * @param {KeyboardEvent} event
       */
      handleKeyDown(view, event) {
        apply = charArray.find(char => char === event.key);
      },
    },
  });
}
