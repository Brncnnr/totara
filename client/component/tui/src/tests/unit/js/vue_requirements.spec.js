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

import { makeScanner, getInheritedOption } from 'tui/vue_requirements';
import Vue from 'vue';

describe('makeScanner', () => {
  it('calls options.extract and mergeSubresult to get values from component and subcomponents', () => {
    const scan = makeScanner({
      extract(comp) {
        return comp.foo;
      },
      mergeSubresult: (a, b) => a + b,
    });

    expect(scan({ foo: 1 })).toBe(1);
    expect(scan({ foo: 1, extends: { foo: 2 } })).toBe(3);
    expect(scan({ foo: 1, components: { a: { foo: 2 }, b: { foo: 4 } } })).toBe(
      7
    );
    expect(
      scan({
        foo: 1,
        extends: { foo: 2 },
        components: { a: { foo: 4 }, c: null },
      })
    ).toBe(7);

    expect(scan(Vue.extend({ foo: 1 }))).toBe(1);
  });

  it('calls postprocess to process extracted value', () => {
    const postprocess = jest.fn(x => x + 10);
    const scan = makeScanner({
      extract(comp) {
        return comp.foo;
      },
      mergeSubresult: (a, b) => a + b,
      postprocess,
    });
    expect(scan({ foo: 1, extends: { foo: 2 } })).toBe(23);
    expect(postprocess).toHaveBeenNthCalledWith(1, 2);
    expect(postprocess).toHaveBeenNthCalledWith(2, 13);
  });

  it('memoizes calls with cache: true', () => {
    const extract = jest.fn(comp => comp.foo);
    const scan = makeScanner({
      extract,
      mergeSubresult: (a, b) => a + b,
      cache: true,
    });

    const comp1 = { foo: 1 };
    const comp2 = { foo: 2, components: { comp1 } };

    scan(comp1);
    scan(comp1);
    expect(extract).toHaveBeenCalledTimes(1);
    scan(comp2);
    scan(comp2);
    expect(extract).toHaveBeenCalledTimes(2);
  });
});

describe('getInheritedOption', () => {
  it('just returns the component value when not extending', () => {
    expect(getInheritedOption({ foo: 1 }, 'foo')).toBe(1);
    expect(getInheritedOption({}, 'foo')).toBe(undefined);
  });

  it('returns the component value when extending if specified otherwise the parent value', () => {
    expect(
      getInheritedOption(
        {
          foo: 1,
          extends: {
            foo: 2,
          },
        },
        'foo'
      )
    ).toBe(1);
    expect(
      getInheritedOption(
        {
          extends: {
            foo: 2,
          },
        },
        'foo'
      )
    ).toBe(2);
  });

  it('allows specifying a custom merge strategy', () => {
    expect(
      getInheritedOption(
        {
          foo: 1,
          extends: {
            foo: 2,
          },
        },
        'foo',
        (parent, current) => `${parent}-${current}`
      )
    ).toBe('2-1');
    expect(
      getInheritedOption(
        {
          extends: {
            foo: 2,
          },
        },
        'foo',
        (parent, current) => `${parent}-${current}`
      )
    ).toBe('2-undefined');
  });
});
