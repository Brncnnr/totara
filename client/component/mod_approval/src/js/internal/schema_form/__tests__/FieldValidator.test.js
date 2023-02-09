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
import FieldValidator from '../FieldValidator';

describe('FieldValidator', () => {
  it('provides validation functions for fields', () => {
    const field = {
      key: 'training_course_end',
      validations: [
        {
          message:
            'The training end date cannot be before the training start date.',
          name: 'date_compare',
          operation: '>=',
          value: {
            ref: 'training_course_start',
          },
        },
      ],
      required: true,
    };

    const handle = field;
    const fieldValidator = new FieldValidator();
    const validator = fieldValidator.getValidator(field, handle);
    const result = validator(null, { getValue: ({ value }) => value });
    expect(result).toHaveProperty('bits', ['required', 'core']);
  });
});
