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

import { totaraUrl } from 'tui/util';

describe('totaraUrl', () => {
  it('prepends base url unless absolute', () => {
    expect(totaraUrl('/hello')).toBe('http://foo/hello');
    expect(totaraUrl('/hello.php')).toBe('http://foo/hello.php');
    expect(totaraUrl('/hello?a=b')).toBe('http://foo/hello?a=b');
    expect(totaraUrl('//bar/hello')).toBe('//bar/hello');
    expect(totaraUrl('https://bar/hello')).toBe('https://bar/hello');
    expect(totaraUrl('foo://bar/hello')).toBe('foo://bar/hello');
    expect(totaraUrl('http://bar/hello.php')).toBe('http://bar/hello.php');
  });

  it('requires a leading /', () => {
    expect(() => totaraUrl('hello')).toThrow();
  });

  it('formats the provided params', () => {
    expect(
      totaraUrl('/hello', { a: 'one&two three', b: 2, c: true, d: [3, 4] })
    ).toBe('http://foo/hello?a=one%26two%20three&b=2&c=true&d[0]=3&d[1]=4');

    expect(
      totaraUrl('/hello', {
        d: [3, 4],
        e: { f: 5, g: 6 },
        f: [
          [1, 2],
          [3, 4],
        ],
        g: { f: { a: 5 }, g: { a: 6 } },
      })
    ).toBe(
      'http://foo/hello?d[0]=3&d[1]=4&e[f]=5&e[g]=6&' +
        'f[0][0]=1&f[0][1]=2&f[1][0]=3&f[1][1]=4&' +
        'g[f][a]=5&g[g][a]=6'
    );

    expect(totaraUrl('/xyz?a=1', { b: 2 })).toBe('http://foo/xyz?a=1&b=2');
    expect(totaraUrl('/xyz?a=1&', { b: 2 })).toBe('http://foo/xyz?a=1&b=2');
    expect(totaraUrl('/xyz?', { b: 2 })).toBe('http://foo/xyz?b=2');
    expect(totaraUrl('/xyz', {})).toBe('http://foo/xyz');
    expect(totaraUrl('/xyz.php?a=1', { b: 2 })).toBe(
      'http://foo/xyz.php?a=1&b=2'
    );
    expect(totaraUrl('/xyz.php', {})).toBe('http://foo/xyz.php');
    expect(totaraUrl('//xyz', { b: 2 })).toBe('//xyz?b=2');
  });

  it('passes through URL constructor if defined', () => {
    expect(totaraUrl('/hello')).toBe('http://foo/hello');
    global.URL = jest.fn();
    totaraUrl('/oo');
    expect(global.URL).toHaveBeenLastCalledWith('http://foo/oo');
    delete global.URL;
  });
});
