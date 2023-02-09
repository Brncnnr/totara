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
 * @module editor_weka
 */

export const originalDoc = {
  type: 'doc',
  content: [
    {
      type: 'paragraph',
      content: [
        { type: 'text', text: 'hi ' },
        { type: 'text', text: 'fred', marks: [{ type: 'strong' }] },
        { type: 'foo', attrs: { a: 1 } },
      ],
    },
    { type: 'bar', attrs: { a: 1 } },
  ],
};

export const rewrittenDoc = {
  type: 'doc',
  content: [
    {
      type: 'paragraph',
      content: [
        { type: 'text', text: 'hi ' },
        {
          type: 'text',
          text: 'fred',
          marks: [{ type: 'unknown', attrs: { type: 'strong' } }],
        },
        { type: 'unknown_inline', attrs: { type: 'foo', attrs: { a: 1 } } },
      ],
    },
    { type: 'unknown_block', attrs: { type: 'bar', attrs: { a: 1 } } },
  ],
};
