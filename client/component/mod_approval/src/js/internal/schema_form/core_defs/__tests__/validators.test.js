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

import validators from '../validators';

describe('date_compare', () => {
  const def = validators['date_compare'];
  it('compares ISO 8601 dates', () => {
    expect(
      def.validate('2021-09-14', { operation: '=', value: '2021-09-14' })
    ).toBeTrue();
    expect(
      def.validate('2021-09-14', { operation: '>', value: '2021-09-15' })
    ).toBeFalse();
  });

  it('ignores time component', () => {
    expect(
      def.validate('2021-09-14T23:00:00Z', {
        operation: '=',
        value: '2021-09-14T01:00:00Z',
      })
    ).toBeTrue();
    expect(
      def.validate('2021-09-14T23:00:00', {
        operation: '=',
        value: '2021-09-14T01:00:00',
      })
    ).toBeTrue();
  });
});
