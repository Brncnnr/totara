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
import { Decoration, DecorationSet } from 'ext_prosemirror/view';

/**
 *
 * @param {String} text
 */
export default function(text) {
  return new Plugin({
    key: new PluginKey('text-placeholder'),
    props: {
      /**
       *
       * @param {Node} doc
       * @returns {boolean|DecorationSet}
       */
      decorations({ doc }) {
        if (!text) {
          return false;
        }

        if (doc.childCount !== 1 || !doc.firstChild.isTextblock) {
          // More than 2 nodes within a doc can be treated as not empty. So we do not go further below.
          return false;
        }

        let decorations = [];
        doc.descendants(
          /**
           *
           * @param {Node} node
           * @param {Number} pos
           */
          (node, pos) => {
            if (node.type.isBlock && node.childCount == 0) {
              decorations.push(
                Decoration.node(pos, pos + node.nodeSize, {
                  class: 'tui-weka__placeholder',
                  'data-placeholder': text,
                })
              );
            }

            return true;
          }
        );

        return DecorationSet.create(doc, decorations);
      },
    },
  });
}
