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

import BaseExtension from './Base';
import hashtag from '../plugins/hashtag';
import { REGEX } from '../plugins/hashtag';
import Hashtag from 'editor_weka/components/nodes/Hashtag';
import Suggestion from 'editor_weka/helpers/suggestion';

// eslint-disable-next-line no-unused-vars
import { EditorState } from 'ext_prosemirror/state';

class HashtagExtension extends BaseExtension {
  nodes() {
    return {
      hashtag: {
        schema: {
          group: 'inline',
          inline: true,
          atom: true,
          attrs: {
            text: { default: '' },
          },
          parseDOM: [
            {
              tag: 'span.tui-wekaNodeHashtag',
              getAttrs(dom) {
                try {
                  return {
                    text: dom.textContent.trim().slice(1),
                  };
                } catch (e) {
                  return {};
                }
              },
            },
          ],
          toDOM(node) {
            return [
              'span',
              {
                class: 'tui-wekaNodeHashtag',
              },
              '#' + node.attrs.text,
            ];
          },
        },

        component: Hashtag,
      },
    };
  }

  plugins() {
    return [hashtag(this.editor)];
  }

  /**
   * Apply the hashtag for the text, this should catch the last hash tag in the string when
   * user is missing it without hitting any space or enter.
   *
   * @param {EditorState} state
   * @return {EditorState}
   */
  applyFormatters(state) {
    const suggestion = new Suggestion(this.editor);
    let transaction = state.tr;

    // The node size is the length of nodes plus another two additional nodes at the start
    // and end of the document. For more info see https://prosemirror.net/docs/ref/#model.Node.nodeSize
    state.doc.nodesBetween(0, state.doc.nodeSize - 2, (node, nodePosition) => {
      if (node.isText) {
        // reset regex when we found a text
        REGEX.lastIndex = 0;

        let resolvedPosition = state.doc.resolve(nodePosition),
          match = suggestion.matcher(REGEX, resolvedPosition);

        while (match) {
          transaction = transaction.replaceWith(
            transaction.mapping.map(match.range.from),
            transaction.mapping.map(match.range.to),
            state.schema.node('hashtag', { text: match.text.slice(1) })
          );

          match = suggestion.matcher(REGEX, resolvedPosition);
        }
      }
    });

    if (transaction.docChanged) {
      this.editor.dispatch(transaction);
      return this.editor.state;
    }

    // Nothing changed
    return state;
  }
}

export default opt => new HashtagExtension(opt);
