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

import {
  getString,
  hasString,
  unloadedStrings,
  loadStrings,
  toVueRequirements,
} from 'tui/i18n';
import {
  loadStrings as rawLoadStrings,
  getString as rawGetString,
  hasString as rawHasString,
} from 'tui/internal/lang_string_store';

jest.unmock('tui/i18n');
jest.mock('tui/internal/lang_string_store');

jest.mock('tui/util', function() {
  return {
    getQueryStringParam(name) {
      if (name == 'strings') return 1;
    },
  };
});

beforeEach(() => {
  rawLoadStrings.mockClear();
  rawGetString.mockClear();
  rawHasString.mockClear();
});

describe('getString', () => {
  it('wraps raw', () => {
    expect(getString('bar', 'foo', 'c')).toBe('baz');
    expect(rawGetString).toHaveBeenCalledWith('bar', 'foo');
    expect(getString('baz', 'foo')).toBe('qux');
    expect(rawGetString).toHaveBeenCalledWith('baz', 'foo');
  });

  it('replaces simple placeholders', () => {
    expect(getString('replace', 'foo', 'bob')).toBe('hello bob');
    expect(rawGetString).toHaveBeenCalledWith('replace', 'foo');
    expect(getString('replace', 'foo', 0)).toBe('hello 0');
    expect(getString('replace', 'foo', '')).toBe('hello ');
    expect(getString('replace', 'foo', null)).toBe('hello {$a}');
    expect(getString('replace', 'foo', undefined)).toBe('hello {$a}');
    expect(getString('replace', 'foo')).toBe('hello {$a}');
  });

  it('replaces object placeholders', () => {
    expect(
      getString('replace_complex', 'foo', { name: 'bob', weather: 'sunny' })
    ).toBe('hello bob, today is sunny');
    expect(rawGetString).toHaveBeenCalledWith('replace_complex', 'foo');
    expect(getString('replace_complex', 'foo', { name: 'bob' })).toBe(
      'hello bob, today is {$a->weather}'
    );
    expect(getString('replace_complex', 'foo', { name: '', weather: 0 })).toBe(
      'hello , today is 0'
    );
  });

  it('normalizes component', () => {
    expect(getString('save', 'moodle')).toBe('Save');
    expect(rawGetString).toHaveBeenCalledWith('save', 'core');
    expect(getString('save', 'core')).toBe('Save');
    expect(rawGetString).toHaveBeenCalledWith('save', 'core');
    expect(getString('save')).toBe('Save');
    expect(rawGetString).toHaveBeenCalledWith('save', 'core');
  });
});

describe('hasString', () => {
  it('wraps raw', () => {
    expect(hasString('bar', 'foo')).toBe(true);
    expect(rawHasString).toHaveBeenCalledWith('bar', 'foo');
    expect(hasString('bar', 'a')).toBe(false);
    expect(rawHasString).toHaveBeenCalledWith('bar', 'a');
    expect(hasString('b', 'foo')).toBe(false);
    expect(rawHasString).toHaveBeenCalledWith('b', 'foo');
  });

  it('normalizes component', () => {
    expect(hasString('f', 'moodle')).toBe(false);
    expect(rawHasString).toHaveBeenCalledWith('f', 'core');
    expect(hasString('save', 'moodle')).toBe(true);
    expect(rawHasString).toHaveBeenCalledWith('save', 'core');
    expect(hasString('f')).toBe(false);
    expect(rawHasString).toHaveBeenCalledWith('f', 'core');
  });
});

describe('unloadedStrings', () => {
  it('filters out strings which are already loaded', () => {
    expect(
      unloadedStrings([
        {
          component: 'foo',
          key: 'bar',
        },
        {
          component: 'a',
          key: 'b',
        },
      ])
    ).toEqual([
      {
        component: 'a',
        key: 'b',
      },
    ]);
  });

  it('normalizes component', () => {
    expect(
      unloadedStrings([
        {
          component: 'core',
          key: 'save',
        },
      ])
    ).toEqual([]);
  });
});

describe('loadStrings', () => {
  it('calls raw loadStrings with the provided strings', async () => {
    const requests = [{ component: 'a', key: 'b' }];
    expect(await loadStrings(requests)).toBe(undefined);
    expect(rawLoadStrings).toHaveBeenCalledWith(requests);
  });

  it('normalizes', async () => {
    const requests = [{ component: 'core', key: 'save' }];
    expect(await loadStrings(requests)).toBe(undefined);
    expect(rawLoadStrings).toHaveBeenCalledWith([
      { component: 'core', key: 'save' },
    ]);
  });
});

describe('toVueRequirements', () => {
  it('called with known string', () => {
    const requests = [{ component: 'core', key: 'save' }];
    expect(toVueRequirements(requests)).toStrictEqual({ core: ['save'] });
  });
});
