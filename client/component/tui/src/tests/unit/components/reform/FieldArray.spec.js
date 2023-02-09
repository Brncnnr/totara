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

import { mount } from '@vue/test-utils';
import FieldArray from 'tui/components/reform/FieldArray';
import { ReformScopeProvider } from './util';

function create({ scope, path }) {
  const result = {};
  const outerWrapper = mount(ReformScopeProvider, {
    propsData: { scope },
    stubs: {
      Scope: true,
    },
    scopedSlots: {
      default() {
        const h = this.$createElement;
        return h(FieldArray, {
          props: { path },
          scopedSlots: {
            default(props) {
              result.props = props;
              return h('div');
            },
          },
        });
      },
    },
  });

  result.wrapper = outerWrapper.find(FieldArray);

  return result;
}

describe('FieldArray', () => {
  it('updates state using provided functions', () => {
    const slices = {};
    const scope = {
      getValue: jest.fn(() => []),
      updateRegistration: () => {},
      $_internalUpdateSliceState: jest.fn((path, fn) => {
        slices[path] = fn(slices[path] || {});
      }),
    };
    const opt = create({ scope, path: 'foo' });

    opt.props.push('foo');
    expect(slices.foo).toEqual({ values: ['foo'], touched: [null] });

    opt.props.push('bar');
    expect(slices.foo).toEqual({
      values: ['foo', 'bar'],
      touched: [null, null],
    });

    slices.foo.touched[1] = true;
    opt.props.shift();
    expect(slices.foo).toEqual({ values: ['bar'], touched: [true] });

    opt.props.push('baz');
    opt.props.pop();
    expect(slices.foo).toEqual({ values: ['bar'], touched: [true] });

    opt.props.push('baz');
    opt.props.push('qux');
    opt.props.swap(0, 1);
    expect(slices.foo).toEqual({
      values: ['baz', 'bar', 'qux'],
      touched: [null, true, null],
    });

    opt.props.move(2, 0);
    expect(slices.foo).toEqual({
      values: ['qux', 'baz', 'bar'],
      touched: [null, null, true],
    });

    opt.props.insert(1, 'foo');
    expect(slices.foo).toEqual({
      values: ['qux', 'foo', 'baz', 'bar'],
      touched: [null, null, null, true],
    });

    opt.props.remove(0);
    opt.props.remove(1);
    expect(slices.foo).toEqual({
      values: ['foo', 'bar'],
      touched: [null, true],
    });

    opt.props.unshift('baz');
    expect(slices.foo).toEqual({
      values: ['baz', 'foo', 'bar'],
      touched: [null, null, true],
    });

    opt.props.replace(2, 'qux');
    expect(slices.foo).toEqual({
      values: ['baz', 'foo', 'qux'],
      touched: [null, null, true],
    });
  });
});
