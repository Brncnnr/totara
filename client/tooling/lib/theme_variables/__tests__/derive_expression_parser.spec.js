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
 * @module tui
 */

const { parseDerive } = require('../derive_expression_parser');

describe('parseDerive', () => {
  it('parses derive expressions', () => {
    expect(
      parseDerive(
        'scale(var(--color-state), lightness: -10%, other-thing: ab(cc))'
      )
    ).toEqual({
      type: 'call',
      name: 'scale',
      args: [
        {
          type: 'var-ref',
          name: 'color-state',
        },
      ],
      namedArgs: {
        lightness: { type: 'percent', value: -10 },
        'other-thing': {
          type: 'call',
          name: 'ab',
          args: [{ type: 'atom', name: 'cc' }],
        },
      },
    });
  });
});
