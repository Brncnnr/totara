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
  uniqueId,
  pull,
  pick,
  result,
  memoize,
  memoizeLoad,
  get,
  set,
  orderBy,
  isPlainObject,
} from 'tui/util';

let counter = 1;
const incr = () => counter++;

describe('uniqueId', () => {
  it('returns an incrementing unique id', () => {
    let lastResult = uniqueId();
    for (let i = 0; i < 5; i++) {
      const result = uniqueId();
      expect(result).toBe(lastResult + 1);
      lastResult = result;
    }
  });
});

describe('pull', () => {
  it('removes the provided element', () => {
    const arr = [1, 2, 3];
    pull(arr, 2);
    expect(arr).toEqual([1, 3]);
  });

  it('only removes the first instance', () => {
    const arr = [1, 2, 3, 2];
    pull(arr, 2);
    expect(arr).toEqual([1, 3, 2]);
  });

  it('does nothing if element is not found', () => {
    const arr = [1, 3];
    pull(arr, 2);
    expect(arr).toEqual([1, 3]);
  });
});

describe('pick', () => {
  it('copies out selected keys', () => {
    expect(pick({ a: 1, b: 2, c: 3 }, ['a', 'c'])).toEqual({ a: 1, c: 3 });
  });

  it('ignores not found keys', () => {
    expect(pick({ a: 1 }, ['c'])).toEqual({});
    expect(pick({}, ['c'])).toEqual({});
  });

  it('requires keys to be an array', () => {
    expect(() => pick({}, {})).toThrow('keys must be an array');
  });
});

describe('result', () => {
  it('calls a functin and returns the result', () => {
    expect(result(() => 3)).toBe(3);
  });

  it('passes non-function values through', () => {
    expect(result(1)).toBe(1);
    expect(result('b')).toBe('b');
    var obj = {};
    expect(result(obj)).toBe(obj);
  });
});

describe('memoize', () => {
  it('returns the same result for each invocation', () => {
    const fn = jest.fn(() => incr());
    const memoFn = memoize(fn);

    const result = memoFn();
    // check that we get the same result calling memoFn a second time
    expect(memoFn()).toBe(result);

    expect(fn).toHaveBeenCalledTimes(1);
  });

  it('keys result by argument', () => {
    const fn = jest.fn(key => key + '-' + incr());
    const memoFn = memoize(fn);

    const resultA = memoFn('a');
    expect(memoFn('a')).toBe(resultA);
    expect(fn).toHaveBeenCalledTimes(1);
    expect(fn).toHaveBeenLastCalledWith('a');

    const resultB = memoFn('b');
    expect(memoFn('b')).toBe(resultB);
    expect(fn).toHaveBeenCalledTimes(2);
    expect(fn).toHaveBeenLastCalledWith('b');
  });

  it('allows providing a custom key function', () => {
    const add = jest.fn((a, b) => a + b);
    const keyFn = jest.fn((a, b) => `${a}-${b}`);
    const memoAdd = memoize(add, keyFn);

    memoAdd(1, 2);
    memoAdd(1, 2);
    expect(add).toHaveBeenCalledTimes(1);
  });
});

describe('memoizeLoad', () => {
  it('only calls fn once', async () => {
    const fn = jest.fn(async () => 3);
    const memoLoad = memoizeLoad(fn);

    const promise1 = memoLoad();
    await expect(promise1).resolves.toBe(3);
    // exact same promise is returned from subsequent calls
    expect(memoLoad()).toBe(promise1);
  });

  it('unless it rejects', async () => {
    const fn = jest
      .fn()
      .mockReturnValueOnce(Promise.reject('error'))
      .mockReturnValueOnce(Promise.resolve(2));
    const memoLoad = memoizeLoad(fn);

    await expect(memoLoad()).rejects.toBe('error');

    const promise1 = memoLoad();
    await expect(promise1).resolves.toBe(2);
    // exact same promise is returned from subsequent calls
    expect(memoLoad()).toBe(promise1);
  });
});

describe('get', () => {
  it('returns the value at path', () => {
    const obj = { a: [{ b: { c: 3 } }], d: 4, e: { f: { g: 5 } } };
    expect(get(obj, [])).toBe(obj);
    expect(get(obj, [''])).toBe(undefined);
    expect(get(obj, ['q'])).toBe(undefined);
    expect(get(obj, ['q', 'w'])).toBe(undefined);
    expect(get(obj, ['a', '0', 'b', 'c'])).toBe(3);
    expect(get(obj, ['a', 0, 'b', 'c'])).toBe(3);
    expect(get(obj, ['d'])).toBe(4);
    expect(get(obj, ['e', 'f'])).toBe(obj.e.f);
    expect(get(obj, ['e', 'f', 'g'])).toBe(5);
  });

  it('takes bare string and array paths', () => {
    const obj = { a: 1 };
    expect(get(obj, 'a')).toBe(1);

    const arr = [9, 8, 7];
    expect(get(arr, 1)).toBe(8);
  });

  it('does not get __proto__', () => {
    const obj = {};
    expect(get(obj, '__proto__')).toBeUndefined();
    expect(get(obj, ['__proto__', 'constructor'])).toBeUndefined();
  });
});

describe('set', () => {
  it('sets the value at path', () => {
    const obj = {};
    // set(obj, ['a'], 3);
    // expect(obj.a).toBe(3);
    set(obj, ['b', 'c'], 4);
    expect(obj.b.c).toBe(4);
    set(obj, ['d', '3'], 5);
    expect(obj.d[3]).toBe(5);
    expect(Array.isArray(obj.d)).toBe(true);
    set(obj, ['e', '3', 'f'], 6);
    expect(obj.e[3].f).toBe(6);
  });

  it('takes bare string and array paths', () => {
    const obj = {};
    set(obj, 'a', 1);
    expect(obj.a).toBe(1);

    const arr = [];
    set(arr, 1, 8);
    expect(arr[1]).toBe(8);
  });

  it('does not set __proto__', () => {
    const obj = {};

    set(obj, ['__proto__', 'foo'], 1);
    expect(obj.__proto__.foo).toBeUndefined();

    set(obj, '__proto__', 1);
    expect(obj.__proto__).toBe(Object.prototype);
  });
});

describe('orderBy', () => {
  it('sorts an array using a key function', () => {
    const arr = [2, 3, 2, 9, 2].map(x => ({ index: x }));
    const ordered = orderBy(arr, x => x.index);
    expect(ordered.map(x => x.index)).toEqual([2, 2, 2, 3, 9]);
  });
});

describe('isPlainObject', () => {
  it('detects whether object is a plain object ({}) or not', () => {
    const Es5Class = function() {};
    class Es6Class {}

    expect(isPlainObject(true)).toBe(false);
    expect(isPlainObject('a')).toBe(false);
    expect(isPlainObject(9)).toBe(false);
    expect(isPlainObject(function() {})).toBe(false);
    expect(isPlainObject([])).toBe(false);
    expect(isPlainObject(new Date())).toBe(false);
    expect(isPlainObject(new Es5Class())).toBe(false);
    expect(isPlainObject(new Es6Class())).toBe(false);

    expect(isPlainObject({})).toBe(true);
    expect(isPlainObject({ b: 3 })).toBe(true);
  });
});
