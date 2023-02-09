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

const { getBasicViewData } = require('../print_view');

describe('getBasicViewData', () => {
  it('applies rules before formatting', async () => {
    const schema = {
      fields: [
        {
          key: 'test',
          label: 'Request Status',
          type: 'select_one',
          rules: [
            {
              test: { key: 'other', condition: '=', value: 'A' },
              set: {
                attrs: {
                  choices: [
                    { key: null, label: 'Select one' },
                    { key: 'Y', label: 'Yes' },
                    { key: 'N', label: 'No' },
                  ],
                },
              },
            },
          ],
        },
      ],
    };

    expect(
      (await getBasicViewData({ schema, values: { test: 'Y', other: 'Z' } }))
        .schema.fields[0].resolved.valueText
    ).toEqual('');

    expect(
      (await getBasicViewData({ schema, values: { test: 'Y', other: 'A' } }))
        .schema.fields[0].resolved.valueText
    ).toEqual('Yes');
  });
});
