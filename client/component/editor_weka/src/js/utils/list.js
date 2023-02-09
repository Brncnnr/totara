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

import {
  wrapInList,
  liftListItem,
} from '../lib/prosemirror_schema_list/schema_list';
import { findParentNode } from '../lib/prosemirror_utils/selection';
// eslint-disable-next-line no-unused-vars
import { NodeType } from 'ext_prosemirror/model';
// eslint-disable-next-line no-unused-vars
import { EditorState } from 'ext_prosemirror/state';

function getClosestList(selection, listTypes) {
  return findParentNode(node => listTypes.includes(node.type))(selection);
}

/**
 * Create a command to toggle a list type.
 *
 * @param {NodeType} listType List node type
 * @param {NodeType} itemType List item node type
 * @returns {function}
 */
export function toggleList(listType, itemType, listTypes) {
  const lift = liftListItem(itemType);
  const wrap = wrapInList(listType);

  return (state, dispatch, view) => {
    const { selection } = state;
    const blockRange = selection.$from.blockRange(selection.$to);

    if (!blockRange) {
      return false;
    }

    const closestList = getClosestList(selection, listTypes);

    // range is in closestList (a - b = 0) or direct child of closestList (a - b = 1)
    // it will be in closestList if multiple list items are selected
    // otherwise it will be in a child of closestList
    const inList = closestList && blockRange.depth - closestList.depth <= 1;

    // handle case where we're within a list already
    if (blockRange.depth >= 1 && inList) {
      if (closestList.node.type === listType) {
        // in the requested list type already, lift out (toggle)
        return lift(state, dispatch, view);
      }

      // on other list type, switch list type if possible
      if (listType.validContent(closestList.node.content)) {
        if (dispatch) {
          dispatch(state.tr.setNodeMarkup(closestList.pos, listType));
        }
        return true;
      }
    }

    return wrap(state, dispatch, view);
  };
}

/**
 * Check if the provided list type is currently active.
 *
 * @param {EditorState} state
 * @param {NodeType} listType
 */
export function isListActive(state, listType) {
  const { selection } = state;
  const blockRange = selection.$from.blockRange(selection.$to);

  if (!blockRange) {
    return false;
  }

  const closestList = findParentNode(node => node.type == listType)(selection);

  return (
    blockRange.depth >= 1 &&
    closestList &&
    blockRange.depth - closestList.depth <= 1
  );
}
