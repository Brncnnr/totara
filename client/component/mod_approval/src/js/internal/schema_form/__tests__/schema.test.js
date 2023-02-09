/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @module mod_approval
 */

const { getAllLayoutCells, applyRules } = require('../schema');

test('getAllLayoutCells', () => {
  const schema = {
    print_layout: {
      sections: [
        {
          rows: [[{ id: 1 }, { id: 2 }, { id: 3 }]],
        },
        {
          section: '?',
          rows: [[{ id: 4 }]],
        },
        {
          rows: [
            [
              {
                type: 'column',
                id: 5,
                rows: [
                  [{ id: 6 }, { type: 'column', id: 7, rows: [[{ id: 8 }]] }],
                ],
              },
            ],
          ],
        },
      ],
    },
  };
  expect(getAllLayoutCells(schema).map(x => x && x.id)).toEqual([
    1,
    2,
    3,
    4,
    5,
    6,
    7,
    8,
  ]);
});

test('applyRules', () => {
  const field = {
    a: 0,
    rules: [
      { test: { key: 't', condition: '>', value: 1 }, set: { a: 1, h: 1 } },
      { test: { key: 't', condition: '>', value: 2 }, set: { b: 2, h: 2 } },
      { test: { key: 't', condition: '>', value: 200 }, set: { c: 3, h: 3 } },
    ],
  };

  expect(applyRules(field, () => 20)).toEqual({ a: 1, b: 2, h: 2 });
});
