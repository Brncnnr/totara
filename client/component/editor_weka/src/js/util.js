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

import { ReplaceStep, ReplaceAroundStep } from 'ext_prosemirror/transform';

/**
 * Get the range of a mark from a single position.
 *
 * @param {ResolvedPos} $pos ResolvedPos at/inside the node with the mark.
 * @param {MarkType} type Type of the mark.
 * @returns {({from: number, to: number}|false)}
 */
export function getMarkRange($pos, type) {
  // find the actual text node containing at our $pos
  const start = resolvedPosChild($pos);

  if (!start.node) {
    return false;
  }

  // get the mark to match against - we do this rather than just checking type
  // so that marks with different attrs are not counted as the same
  const mark = start.node.marks.find(mark => mark.type === type);
  if (!mark) {
    return false;
  }

  // index of text node within parent
  let index = $pos.index();
  // start of parent plus text node's offset within parent
  let fromPos = $pos.start() + start.offset;
  // to get the end of the text node we just need to add the size
  let toPos = fromPos + start.node.nodeSize;

  const from = findMarkEnd($pos.parent, mark, index, fromPos, -1);
  const to = findMarkEnd($pos.parent, mark, index, toPos, 1);

  return { from, to };
}

/**
 * If a node range covers all of the node's content, expand it to include the node itself.
 *
 * @param {ResolvedRange} range
 * @returns {ResolvedRange}
 */
export function expandContentResolvedRange(range) {
  if (
    range &&
    // can't expand beyond doc bounds
    range.$from.parent != range.$from.doc &&
    range.$from.sameParent(range.$to) &&
    range.$from.parentOffset === 0 &&
    range.$to.parentOffset === range.$to.parent.nodeSize - 2
  ) {
    const doc = range.$from.doc;
    return new ResolvedRange(
      doc.resolve(range.$from.pos - 1),
      doc.resolve(range.$to.pos + 1)
    );
  }
  return range;
}

/**
 * Get mark instance at position.
 *
 * @param {ResolvedPos} $pos
 * @param {MarkType} type
 * @returns {Mark}
 */
export function getMark($pos, type) {
  const info = resolvedPosChild($pos);
  return info.node.marks.find(mark => mark.type === type);
}

export class ResolvedRange {
  constructor($from, $to) {
    this.$from = $from;
    this.$to = $to;
  }

  static resolve(doc, range) {
    return new ResolvedRange(doc.resolve(range.from), doc.resolve(range.to));
  }
}

/**
 * Get the exact node a ResolvedPos is pointing to.
 *
 * If it is a text node that will be returned, otherwise this will be the same as $pos.parent.
 *
 * @private
 * @param {ResolvedPos} $pos
 * @return {{node: Node, index: number, offset: number}}
 */
function resolvedPosChild($pos) {
  return $pos.parent.childAfter($pos.parentOffset);
}

/**
 * Keep advancing pos until we no longer have the specified mark.
 *
 * @private
 * @param {Node} parent Node containing the text nodes.
 * @param {Mark} mark Mark instance to match against.
 * @param {number} index Index of starting child with mark.
 * @param {number} pos Global position at edge of starting child.
 * @param {(-1|1)} direction Direction to search in. -1: backwards, 1: forwards
 */
function findMarkEnd(parent, mark, index, pos, direction) {
  const max = parent.childCount - 1;
  // as long as the next text node has the mark keep advancing the pos
  for (;;) {
    index += direction;
    if (index < 0 || index > max) {
      break;
    }
    const child = parent.child(index);
    if (!mark.isInSet(child.marks)) {
      break;
    }
    pos += parent.child(index).nodeSize * direction;
  }

  return pos;
}

/**
 * If the last step in the trasaction was an insert, return the position of the
 * new node.
 *
 * @param {import('ext_prosemirror/state').Transaction} tr
 * @returns {number|null}
 */
export function getInsertedPos(tr) {
  const step = tr.steps[tr.steps.length - 1];
  return step instanceof ReplaceStep || step instanceof ReplaceAroundStep
    ? step.to + step.slice.openStart
    : null;
}
