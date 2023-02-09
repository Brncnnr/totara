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

import { isEmpty, v, fieldValidator } from 'tui/validation';
import { langString } from 'tui/i18n';
import WekaValue from 'editor_weka/WekaValue';

describe('isEmpty', () => {
  it('returns true if a value is considered empty', () => {
    expect(isEmpty(true)).toBe(false);
    expect(isEmpty(false)).toBe(true);
    expect(isEmpty(null)).toBe(true);
    expect(isEmpty(undefined)).toBe(true);
    expect(isEmpty(0)).toBe(false);
    expect(isEmpty(1)).toBe(false);
    expect(isEmpty(NaN)).toBe(true);
    expect(isEmpty('hi')).toBe(false);
    expect(isEmpty('0')).toBe(false);
    expect(isEmpty('')).toBe(true);
    expect(isEmpty('    ')).toBe(true);
    expect(isEmpty([1])).toBe(false);
    expect(isEmpty([])).toBe(true);
  });

  it('can check classes and objects __isEmpty method', () => {
    expect(WekaValue.empty().isEmpty).toBeTrue();
    expect(isEmpty(WekaValue.empty())).toBeTrue();
    expect(isEmpty({ __isEmpty: () => true })).toBeTrue();

    const nonEmptyWekaDocument = {
      type: 'doc',
      content: [
        {
          type: 'paragraph',
          content: [{ type: 'text', text: 'Some content' }],
        },
      ],
    };

    const wekaValue = WekaValue.fromDoc(nonEmptyWekaDocument);

    expect(wekaValue.isEmpty).toBeFalse();
    expect(isEmpty(wekaValue)).toBeFalse();
    expect(isEmpty({ __isEmpty: () => false })).toBeFalse();
  });
});

describe('built-in validators', () => {
  test('required', () => {
    const i = v.required();
    expect(i.validate()).toBe(false);
    expect(i.validate('')).toBe(false);
    expect(i.validate('    ')).toBe(false);
    expect(i.validate(NaN)).toBe(false);
    expect(i.validate(null)).toBe(false);
    expect(i.validate(undefined)).toBe(false);
    expect(i.validate(false)).toBe(false);
    expect(i.validate('hi')).toBe(true);
    expect(i.validate(0)).toBe(true);
    expect(i.validate([])).toBe(false);
    expect(i.validate({})).toBe(true);
    expect(i.validate('0')).toBe(true);
  });

  test('email', () => {
    const i = v.email();
    expect(i.validate('foo')).toBe(false);
    expect(i.validate('a@b.com')).toBe(true);
    expect(i.validate('@b')).toBe(false);
  });

  test('number', () => {
    const i = v.number();
    expect(i.validate(0)).toBe(true);
    expect(i.validate(1)).toBe(true);
    expect(i.validate(1.1)).toBe(true);
    expect(i.validate('0')).toBe(true);
    expect(i.validate('1')).toBe(true);
    expect(i.validate('1,000')).toBe(false);
    expect(i.validate('1.1')).toBe(true);
    expect(i.validate('$1')).toBe(false);
    expect(i.validate('.')).toBe(false);
    expect(i.validate('hello')).toBe(false);
  });

  test('integer', () => {
    const i = v.integer();
    expect(i.validate(0)).toBe(true);
    expect(i.validate(1)).toBe(true);
    expect(i.validate(1.1)).toBe(false);
    expect(i.validate('0')).toBe(true);
    expect(i.validate('1')).toBe(true);
    expect(i.validate('1,000')).toBe(false);
    expect(i.validate('1.1')).toBe(false);
    expect(i.validate('$1')).toBe(false);
    expect(i.validate('.')).toBe(false);
    expect(i.validate('hello')).toBe(false);
  });

  test('minLength', () => {
    const i = v.minLength(3);
    expect(i.validate('')).toBe(false);
    expect(i.validate('aa')).toBe(false);
    expect(i.validate('aaa')).toBe(true);
    expect(i.validate('aaaa')).toBe(true);
  });

  test('maxLength', () => {
    const i = v.maxLength(3);
    expect(i.validate('')).toBe(true);
    expect(i.validate('aa')).toBe(true);
    expect(i.validate('aaa')).toBe(true);
    expect(i.validate('aaaa')).toBe(false);
  });

  test('min', () => {
    const i = v.min(3);
    expect(i.validate(2)).toBe(false);
    expect(i.validate(3)).toBe(true);
    expect(i.validate(4)).toBe(true);
    expect(i.validate('2')).toBe(false);
    expect(i.validate('3')).toBe(true);
    expect(i.validate('4')).toBe(true);
  });

  test('max', () => {
    const i = v.max(3);
    expect(i.validate(2)).toBe(true);
    expect(i.validate(3)).toBe(true);
    expect(i.validate(4)).toBe(false);
    expect(i.validate('2')).toBe(true);
    expect(i.validate('3')).toBe(true);
    expect(i.validate('4')).toBe(false);
  });

  test('min for range end', () => {
    // Start and end are equal and in range.
    let i = v.minForRangeEnd(5, 5, 0, 10, '');
    expect(i.validate(5)).toBe(true);

    // Start and end are equal and set to the absolute max.
    i = v.minForRangeEnd(10, 10, 0, 10, '');
    expect(i.validate(10)).toBe(true);

    // Start and end are equal and set to the absolute min.
    i = v.minForRangeEnd(0, 0, 0, 10, '');
    expect(i.validate(0)).toBe(true);

    // Start is less than end and both are in range.
    i = v.minForRangeEnd(10, 5, 0, 10, '');
    expect(i.validate(10)).toBe(true);

    // End is less than start and both are in range.
    i = v.minForRangeEnd(9, 8, 0, 10, 'range backwards');
    expect(i.validate(8)).toBe(false);
    expect(i.message(8)).toStrictEqual('range backwards');

    // Start and end are less than than absolute min..
    i = v.minForRangeEnd(-10, -1, 0, 10, '');
    expect(i.validate(-1)).toBe(false);
    expect(i.message(-1)).toStrictEqual(
      langString('validation_invalid_min', 'totara_core', { min: 0 })
    );

    // Start and end are less than than absolute min and backwards.
    i = v.minForRangeEnd(-1, -10, 0, 10, 'range backwards');
    expect(i.validate(-10)).toBe(false);
    expect(i.message(-10)).toStrictEqual('range backwards');

    // Absolute min is used when no start value is supplied.
    i = v.minForRangeEnd(-1, null, 0, 10, '');
    expect(i.validate(-1)).toBe(false);
    expect(i.message(-1)).toStrictEqual(
      langString('validation_invalid_min', 'totara_core', { min: 0 })
    );
  });

  test('max for range start', () => {
    // Start and end are equal and in range.
    let i = v.maxForRangeStart(5, 5, 0, 10, '');
    expect(i.validate(5)).toBe(true);

    // Start and end are equal and set to the absolute max.
    i = v.maxForRangeStart(10, 10, 0, 10, '');
    expect(i.validate(10)).toBe(true);

    // Start and end are equal and set to the absolute min.
    i = v.maxForRangeStart(0, 0, 0, 10, '');
    expect(i.validate(0)).toBe(true);

    // Start is less than end and both are in range.
    i = v.maxForRangeStart(5, 10, 0, 10, '');
    expect(i.validate(5)).toBe(true);

    // Start is more than start and both are in range.
    i = v.maxForRangeStart(9, 8, 0, 10, 'range backwards');
    expect(i.validate(9)).toBe(false);
    expect(i.message(9)).toStrictEqual('range backwards');

    // Start and end are more than than absolute max.
    i = v.maxForRangeStart(11, 11, 0, 10, '');
    expect(i.validate(11)).toBe(false);
    expect(i.message(11)).toStrictEqual(
      langString('validation_invalid_max', 'totara_core', { max: 10 })
    );

    // Start and end are more than than absolute max and backwards.
    i = v.maxForRangeStart(12, 11, 0, 10, 'range backwards');
    expect(i.validate(12)).toBe(false);
    expect(i.message(12)).toStrictEqual('range backwards');

    // Absolute max is used when no end value is supplied.
    i = v.maxForRangeStart(11, null, 0, 10, '');
    expect(i.validate(11)).toBe(false);
    expect(i.message(11)).toStrictEqual(
      langString('validation_invalid_max', 'totara_core', { max: 10 })
    );
  });
});

describe('fieldValidator', () => {
  it('creates a validator function for a field', () => {
    const i = fieldValidator(v => [v.required(), v.min(3)]);
    expect(i('7')).toBe(undefined);
    expect(i('')).toStrictEqual(langString('required', 'core'));
    expect(i('2')).toStrictEqual(
      langString('validation_invalid_min', 'totara_core', { min: 3 })
    );
  });
});
