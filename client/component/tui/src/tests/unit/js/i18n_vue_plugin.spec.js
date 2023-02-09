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

import i18nVuePlugin, { collectStrings } from 'tui/i18n_vue_plugin';
import { getString, hasString } from 'tui/i18n';

let Vue;
let vm;

beforeEach(() => {
  Vue = jest.fn(function() {
    this.$options = {};
  });
  Vue.config = { optionMergeStrategies: {} };
  i18nVuePlugin.install(Vue);
  vm = new Vue();
  getString.mockReset().mockImplementation((...args) => args.join(','));
  hasString.mockReset().mockReturnValue(false);
});

describe('Vue#str()', () => {
  it('calls i18n getString', () => {
    vm.$options.langStrings = {
      b: ['a'],
      d: ['c'],
      core: ['e'],
    };

    expect(vm.$str('a', 'b', 'c')).toEqual('a,b,c');
    expect(getString).toHaveBeenCalledWith('a', 'b', 'c');
    expect(vm.$str('c', 'd')).toEqual('c,d,');
    expect(getString).toHaveBeenCalledWith('c', 'd', undefined);
    expect(vm.$str('e')).toEqual('e,core,');
    expect(getString).toHaveBeenCalledWith('e', 'core', undefined);
  });

  it('warns for undeclared strings in non-production mode', () => {
    vm.$options.langStrings = { b: ['a'] };
    vm.$options.__langStrings = { d: ['c'] };
    const originalConsole = global.console;
    global.console = {
      warn: jest.fn(),
    };

    vm.$str('a', 'b', 'c');
    vm.$str('c', 'd');

    expect(console.warn).toHaveBeenCalledTimes(0);

    vm.$str('foo', 'bar');

    expect(console.warn).toHaveBeenCalledTimes(1);
    expect(console.warn.mock.calls[0][0]).toEqual(
      expect.stringContaining("$str('foo', 'bar'):")
    );
    expect(getString).toHaveBeenCalledTimes(3);

    const vm2 = new Vue();
    vm2.$str('x', 'y');
    expect(console.warn).toHaveBeenCalledTimes(2);
    expect(console.warn.mock.calls[1][0]).toEqual(
      expect.stringContaining("$str('x', 'y'):")
    );

    getString.mockClear();
    console.warn.mockClear();

    // and in prouction mode:
    const nodeEnv = process.env.NODE_ENV;
    process.env.NODE_ENV = 'production';

    vm.$str('a', 'b', 'c');
    vm.$str('c', 'd');
    vm.$str('foo', 'bar');
    expect(console.warn).toHaveBeenCalledTimes(0);
    expect(getString).toHaveBeenCalledTimes(3);

    process.env.NODE_ENV = nodeEnv;

    global.console = originalConsole;
  });
});

describe('Vue#hasStr()', () => {
  it('calls i18n hasString', () => {
    hasString.mockImplementation((key, comp) => key == 'a' && comp == 'b');
    expect(vm.$hasStr('a', 'b')).toEqual(true);
    expect(hasString).toHaveBeenCalledWith('a', 'b');
    expect(vm.$hasStr('c', 'd')).toEqual(false);
    expect(hasString).toHaveBeenCalledWith('c', 'd');
    expect(vm.$hasStr('e')).toEqual(false);
    expect(hasString).toHaveBeenCalledWith('e', 'core');
  });
});

describe('Vue#tryStr()', () => {
  it('calls getString if string is loaded or returns null otherwise', () => {
    hasString.mockImplementation((key, comp) => key == 'a' && comp == 'b');
    expect(vm.$tryStr('a', 'b')).toEqual('a,b,');
    expect(hasString).toHaveBeenCalledWith('a', 'b');
    expect(vm.$tryStr('c', 'd')).toEqual(null);
    expect(hasString).toHaveBeenCalledWith('c', 'd');
    expect(vm.$tryStr('e')).toEqual(null);
    expect(hasString).toHaveBeenCalledWith('e', 'core');
  });
});

describe('collectStrings', () => {
  it('extracts unique string requirements from the provided component', () => {
    const comp = {
      __langStrings: { a: ['1'], e: ['5'] },
      langStrings: { b: ['2'] },
      components: {
        a: {
          __langStrings: { a: ['1'], f: ['6'] },
          langStrings: { c: ['3'] },
          components: {
            b: {
              __langStrings: { a: ['1'] },
              langStrings: { d: ['4'], g: ['7'] },
            },
          },
        },
      },
    };

    expect(collectStrings(comp)).toIncludeAllMembers([
      { component: 'a', key: '1' },
      { component: 'b', key: '2' },
      { component: 'c', key: '3' },
      { component: 'd', key: '4' },
      { component: 'e', key: '5' },
      { component: 'f', key: '6' },
      { component: 'g', key: '7' },
    ]);

    const comp2 = {
      __langStrings: { a: ['1'], e: ['5'] },
      components: {
        a: {
          langStrings: { c: ['3'] },
          components: {
            b: {
              __langStrings: { a: ['1'] },
            },
            c: {},
          },
        },
      },
    };

    expect(collectStrings(comp2)).toIncludeAllMembers([
      { component: 'a', key: '1' },
      { component: 'c', key: '3' },
      { component: 'e', key: '5' },
    ]);
  });
});
