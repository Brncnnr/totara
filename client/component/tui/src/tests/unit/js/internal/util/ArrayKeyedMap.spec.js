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

import ArrayKeyedMap from '../../../../../js/internal/util/ArrayKeyedMap';

describe('ArrayKeyedMap', () => {
  let map;
  beforeEach(() => {
    map = new ArrayKeyedMap();
  });

  it('allows you to get and set items', () => {
    expect(map.get(['a', 'b', 3])).toBe(undefined);
    map.set(['a', 'b', 3], 12);
    map.set(['a', 'b', 3, 4], 13);
    map.set(['a', 'b', 4], 14);
    expect(map.get(['a', 'b', 3])).toBe(12);
    expect(map.get(['a', 'b', 3, 4])).toBe(13);
    expect(map.get(['a', 'b', 4])).toBe(14);
    map.set(['a', 'b', 3], 'w');
    expect(map.get(['a', 'b', 3])).toBe('w');
  });

  it('can check whether an item exists', () => {
    expect(map.has([1])).toBeFalse();
    map.set([1], 2);
    expect(map.has([1])).toBeTrue();
    expect(map.delete([1])).toBeTrue();
    expect(map.delete([9])).toBeFalse();
    expect(map.has([1])).toBeFalse();
  });

  it('compares arrays on a per item basis, using SameValueZero', () => {
    const obj = {};
    map.set(['a', 'b'], 1);
    map.set([obj], 13);
    map.set([], 14);
    map.set([NaN], 15);
    map.set([-0], 16);
    expect(map.get(['a', 'b'])).toBe(1);
    expect(map.get([obj])).toBe(13);
    expect(map.get([])).toBe(14);
    expect(map.get([NaN])).toBe(15);
    expect(map.get([-0])).toBe(16);
  });

  it('only allows arrays as values', () => {
    map.set(['a', 'b', 3], 2);
    expect(() => map.set(null, 1)).toThrow();
    expect(() => map.set(undefined, 1)).toThrow();
    expect(() => map.set({}, 1)).toThrow();
    expect(() => map.set(3, 1)).toThrow();
    expect(() => map.set('hello', 1)).toThrow();
    expect(map.get(['a', 'b', 3])).toBe(2);
    expect(() => map.get('hello')).toThrow();
  });
});
