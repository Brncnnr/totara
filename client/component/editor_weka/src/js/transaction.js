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

import { Fragment, Slice } from 'ext_prosemirror/model';
import { Step, StepResult } from 'ext_prosemirror/transform';

export class SetAttrsStep extends Step {
  /**
   * @param {number} pos
   * @param {?object} attrs
   */
  constructor(pos, attrs) {
    super();
    this.pos = pos;
    this.attrs = attrs;
  }

  apply(doc) {
    let target = doc.nodeAt(this.pos);
    if (!target) {
      return StepResult.fail('No node at given position');
    }
    let newNode = target.type.create(this.attrs, Fragment.empty, target.marks);
    let slice = new Slice(Fragment.from(newNode), 0, target.isLeaf ? 0 : 1);
    return StepResult.fromReplace(
      doc,
      this.pos,
      this.pos + target.nodeSize,
      slice
    );
  }

  invert(doc) {
    let target = doc.nodeAt(this.pos);
    return new SetAttrsStep(this.pos, target ? target.attrs : null);
  }

  map(mapping) {
    let pos = mapping.mapResult(this.pos, 1);
    return pos.deleted ? null : new SetAttrsStep(pos.pos, this.attrs);
  }

  toJSON() {
    return { stepType: 'setAttrs', pos: this.pos, attrs: this.attrs };
  }

  static fromJSON(schema, json) {
    if (
      typeof json.pos != 'number' ||
      (json.attrs != null && typeof json.attrs != 'object')
    ) {
      throw new RangeError('Invalid input for SetAttrsStep.fromJSON');
    }
    return new SetAttrsStep(json.pos, json.attrs);
  }
}

Step.jsonID('setAttrs', SetAttrsStep);

/**
 * Set the attributes of the node at `pos`.
 *
 * This differs from setNodeMarkup(pos, null, attrs) in that it does not
 * *replace* the node, so when dispatched with (addToHistory: false) it will not
 * prevent the addition of the node being rolled back.
 *
 * @param {number} pos
 * @param {?object} attrs
 * @returns {function}
 */
export const setAttrs = (pos, attrs) => tr => {
  return tr.step(new SetAttrsStep(pos, attrs));
};
