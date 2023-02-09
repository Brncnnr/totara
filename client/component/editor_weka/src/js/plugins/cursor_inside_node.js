/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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

import { Plugin, PluginKey } from 'ext_prosemirror/state';

function getParentViewDescs(viewDesc) {
  const list = [];
  while (viewDesc) {
    list.push(viewDesc);
    viewDesc = viewDesc.parent;
  }
  return list;
}

/**
 * Create a plugin to notify NodeViews when the cursor is inside them.
 *
 * @returns {Plugin}
 */
export default function() {
  const key = new PluginKey('cursor-inside-node');

  return new Plugin({
    key,

    state: {
      init() {
        return 0;
      },

      apply(tr, val) {
        return val + 1;
      },
    },

    view() {
      return {
        update(view, oldState) {
          const oldSelectionDom = view.domAtPos(oldState.selection.head);
          const oldCursorInside = getParentViewDescs(
            oldSelectionDom.node.pmViewDesc
          );

          const selectionDom = view.domAtPos(view.state.selection.head);
          const currentCursorInside = getParentViewDescs(
            selectionDom.node.pmViewDesc
          );

          if (key.getState(view.state) === 1) {
            // first transaction
            currentCursorInside.forEach(x => {
              if (x.spec && x.spec.setCursorInside) {
                x.spec.setCursorInside(true);
              }
            });
          } else {
            oldCursorInside.forEach(x => {
              if (
                !currentCursorInside.includes(x) &&
                x.spec &&
                x.spec.setCursorInside
              ) {
                x.spec.setCursorInside(false);
              }
            });

            currentCursorInside.forEach(x => {
              if (
                !oldCursorInside.includes(x) &&
                x.spec &&
                x.spec.setCursorInside
              ) {
                x.spec.setCursorInside(true);
              }
            });
          }
        },
      };
    },
  });
}
