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
 * @author Simon Tegg <simon.tegg@totaralearning.com>
 * @module mod_approval
 */
import { makeFieldValidator, requiredValidator } from '../validation';

describe('makeFieldValidator', () => {
  it('creates a validator', () => {
    const validationsSpecs = [
      requiredValidator,
      {
        message: 'The training start date should be a future date.',
        name: 'date_compare',
        operation: '>',
        value: '2035-01-01',
      },
    ];

    const context = { getValue: ({ value }) => value };
    const fieldValidator = makeFieldValidator(validationsSpecs);
    const dateValid = '2036-01-02';
    const dateInvalid = '2034-01-02';

    const res1 = fieldValidator(null, context);
    const res2 = fieldValidator(dateInvalid, context);
    const res3 = fieldValidator(dateValid, context);
    expect(res1).toHaveProperty('bits', ['required', 'core']);
    expect(res2).toBe(validationsSpecs[1].message);
    expect(res3).toBe(undefined);
  });
});
