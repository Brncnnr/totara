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

import { Schema } from 'ext_prosemirror/model';

// essential nodes
const nodes = {
  paragraph: {
    group: 'block',
    content: 'inline*',
    attrs: { align: { default: undefined } },
    parseDOM: [{ tag: 'p' }],
    toDOM(node) {
      let alignment = node.attrs.align;

      const props = {};

      if (alignment) {
        props.style = `text-align: ${alignment};`;
      }

      return ['p', props, 0];
    },
  },

  text: {
    group: 'inline',
  },

  unknown_block: {
    group: 'block',
    toDOM: () => ['div', { class: 'tui-editor_weka-unknownBlock' }],
    attrs: {
      type: {},
      attrs: { default: undefined },
      content: { default: undefined },
    },
  },

  unknown_inline: {
    group: 'inline',
    inline: true,
    toDOM: () => ['span', { class: 'tui-editor_weka-unknownInline' }],
    attrs: {
      type: {},
      attrs: { default: undefined },
      content: { default: undefined },
    },
  },
};

const marks = {
  unknown: {
    attrs: {
      type: {},
      attrs: { default: undefined },
    },
    toDOM: () => ['span', 0],
    inclusive: false,
  },
};

export function createSchema({ nodes: extraNodes, marks: extraMarks }) {
  const hasRootblockNodes = Object.values(extraNodes).some(
    x => x.group === 'rootblock'
  );

  return new Schema({
    nodes: {
      doc: {
        // The document can contain block or rootblock nodes as direct children.
        // - `block` nodes can appear anywhere in the document that allows block
        //   nodes.
        // - `rootblock` nodes can only appear as direct children of `doc`.
        // - `paragraph`s are block nodes, but are explicitly specified here for
        //   clarity, as the first node found in the content spec is used when a
        //   block must be created -- such as in the case of an empty document.
        content:
          '(paragraph | block' +
          (hasRootblockNodes ? ' | rootblock' : '') +
          ')+',
      },
      ...nodes,
      ...extraNodes,
    },
    marks: { ...marks, ...extraMarks },
  });
}
