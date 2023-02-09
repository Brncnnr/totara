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

import { isDraft, NOTHING, original, produce } from '../shimmer';

class DummyClass {
  constructor(x) {
    this.x = x;
  }
}

const hasSymbols = obj => Object.getOwnPropertySymbols(obj).length > 0;

describe('produce', () => {
  it('translates object mutation to a new object', () => {
    const movie = { rating: 3, time: 9 };
    const updated = produce(movie, draft => {
      draft.rating = 5;
    });

    expect(movie).not.toBe(updated);
    expect(movie.rating).not.toBe(updated.rating);

    expect(movie).toEqual({ rating: 3, time: 9 });
    expect(updated).toEqual({ rating: 5, time: 9 });
  });

  it('translates array mutation to a new array', () => {
    const ratings = [8, 9, 5];
    const updated = produce(ratings, draft => {
      draft[1] = 4;
    });

    expect(ratings).not.toBe(updated);
    expect(ratings[1]).not.toBe(updated[1]);

    expect(ratings).toEqual([8, 9, 5]);
    expect(updated).toEqual([8, 4, 5]);
  });

  it('preserves identity for unchanged objects', () => {
    const obj = { a: 3 };
    const updated = produce(obj, () => {});
    expect(updated).toBe(obj);

    const obj2 = { sub: { a: 3 }, sub2: { b: 9 } };
    const updated2 = produce(obj2, () => {});
    expect(updated2).toBe(obj2);
    expect(updated2.sub).toBe(obj2.sub);
    expect(updated2.sub2).toBe(obj2.sub2);

    const obj3 = { sub: { a: 3 }, sub2: { b: 9 } };
    const updated3 = produce(obj3, draft => {
      draft.sub;
    });
    expect(updated3).toBe(obj3);
    expect(updated3.sub).toBe(obj3.sub);
    expect(updated3.sub2).toBe(obj3.sub2);

    const obj4 = { sub: { a: 3 }, sub2: { b: 9 } };
    const updated4 = produce(obj4, draft => {
      draft.sub.a = 9;
      draft.sub2.b;
    });
    expect(updated4.sub2).toBe(obj4.sub2);
  });

  it('preserves identity for unchanged arrays', () => {
    const obj = [3];
    const updated = produce(obj, () => {});
    expect(updated).toBe(obj);

    const obj2 = [
      [1, 2, 3],
      [4, 5, 6],
    ];
    const updated2 = produce(obj2, () => {});
    expect(updated2).toBe(obj2);
    expect(updated2[0]).toBe(obj2[0]);
    expect(updated2[1]).toBe(obj2[1]);

    const obj3 = [
      [1, 2, 3],
      [4, 5, 6],
    ];
    const updated3 = produce(obj3, draft => {
      draft[0];
    });
    expect(updated3).toBe(obj3);
    expect(updated3[0]).toBe(obj3[0]);
    expect(updated3[1]).toBe(obj3[1]);

    const obj4 = [
      [1, 2, 3],
      [4, 5, 6],
    ];
    const updated4 = produce(obj4, draft => {
      draft[0][1] = 9;
      draft[1][1];
    });
    expect(updated4).not.toBe(obj4);
    expect(updated4[1]).toBe(obj4[1]);
  });

  it('records no change when assigning to the same value', () => {
    const obj = { sub: { a: 3 }, sub2: { b: 9 } };
    const updated = produce(obj, draft => {
      // eslint-disable-next-line no-self-assign
      draft.sub = draft.sub;
    });
    expect(updated).toBe(obj);
    expect(updated.sub).toBe(obj.sub);
    expect(updated.sub2).toBe(obj.sub2);
  });

  it('handles mutations on nested objects', () => {
    const movie = {
      director: { name: 'Yuri Yurovich', birthplace: 'Norilsk' },
      format: { runtime: 135 },
    };
    const updated = produce(movie, draft => {
      draft.director.name = 'Ivan Ivanovich';
    });

    expect(movie).not.toBe(updated);
    expect(movie.director).not.toBe(updated.director);
    expect(movie.format).toBe(updated.format);

    expect(movie).toEqual({
      director: { name: 'Yuri Yurovich', birthplace: 'Norilsk' },
      format: { runtime: 135 },
    });
    expect(updated).toEqual({
      director: { name: 'Ivan Ivanovich', birthplace: 'Norilsk' },
      format: { runtime: 135 },
    });
  });

  it('ignores __ob__', () => {
    const orig = { foo: 1, bar: { baz: 2 }, __ob__: 3 };

    expect(
      produce(orig, d => {
        d.foo = 9;
      })
    ).toEqual({ foo: 9, bar: { baz: 2 } });

    expect(
      produce(orig, d => {
        d.bar.baz = 9;
      })
    ).toEqual({ foo: 1, bar: { baz: 9 } });

    const nopResult = produce(orig, d => {
      d.bar.baz;
    });
    expect(nopResult).toBe(orig);
    expect(nopResult.bar).toBe(orig.bar);
  });

  it('handles non draftable fields', () => {
    const inst = new DummyClass(1);
    const obj = { inst };

    const updated = produce(obj, draft => {
      draft.a = 9;
      draft.inst.x = 4;
    });

    expect(updated).not.toBe(obj);
    expect(updated.inst).toBe(obj.inst);
    expect(updated.inst).toBe(inst);
    expect(inst.x).toBe(4);
  });

  it('handles non draftable fields - 2', () => {
    const inst = new DummyClass(1);
    const obj = { inst };

    const updated = produce(obj, draft => {
      draft.inst.x = 4;
    });

    expect(updated).toBe(obj);
  });

  it('does not set unfinalisable properties on non-drafts', () => {
    // unfinalisable properties = anything that is not a draft
    const user = { name: 'Bob' };
    Object.defineProperty(user, 'a', {
      configurable: true,
      enumerable: true,
      get: () => null,
      set: () => {
        throw new Error('null property set');
      },
    });
    Object.defineProperty(user, 'b', {
      configurable: true,
      enumerable: true,
      get: () => ({}),
      set: () => {
        throw new Error('object property set');
      },
    });

    expect(() => {
      produce({}, draft => {
        draft.user = user;
      });
    }).not.toThrow();
  });

  it('allows modification of frozen objects', () => {
    const article = Object.freeze({
      author: Object.freeze({ name: 'Bob' }),
    });

    const result = produce(article, draft => {
      draft.author.name = 'Fred';
    });

    expect(result).toEqual({ author: { name: 'Fred' } });
  });

  it('throws on non-draftable root', () => {
    expect(() => {
      produce(new DummyClass());
    }).toThrow('value is not draftable');
  });

  it('handles objects being moved', () => {
    const obj = { a: { foo: { q: 1 } }, b: {} };

    const updated = produce(obj, draft => {
      draft.b.foo = draft.a.foo;
      delete draft.a.foo;
    });

    expect(updated).toEqual({ a: {}, b: { foo: { q: 1 } } });
  });

  it('can replace wrappers', () => {
    const obj = { x: { y: { z: 3 } } };

    const updated = produce(obj, draft => {
      draft.x = { y: draft.x.y, q: null };
    });

    expect(hasSymbols(updated.x.y)).toBe(false);
    expect(updated.x.y).toBe(obj.x.y);
    expect(updated).toEqual({ x: { y: { z: 3 }, q: null } });
  });

  it('handles objects being moved to a new wrapper', () => {
    const obj = { a: { foo: { q: 1 } } };

    const updated = produce(obj, draft => {
      draft.b = { foo: draft.a.foo };
      delete draft.a.foo;
    });

    expect(updated).toEqual({ a: {}, b: { foo: { q: 1 } } });
  });

  const hiddenDesc = {
    configurable: true,
    enumerable: false,
    value: 5,
    writable: true,
  };
  const isEnumerable = (obj, key) =>
    Object.getOwnPropertyDescriptor(obj, key).enumerable;

  it('preserves non-enumerable fields', () => {
    const obj = { a: 1 };
    Object.defineProperty(obj, 'hidden', hiddenDesc);

    const updated = produce(obj, draft => {
      draft.a = 2;
    });

    expect(isEnumerable(updated, 'hidden')).toBeFalse();
    expect(updated).toEqual({ a: 2 });
    expect(updated.hidden).toBe(5);
  });

  it('preserves non-enumerablity of modified fields', () => {
    const obj = { a: 1 };
    Object.defineProperty(obj, 'hidden', hiddenDesc);

    const updated = produce(obj, draft => {
      draft.a = 2;
      draft.hidden = 9;
    });

    expect(isEnumerable(updated, 'hidden')).toBeFalse();
    expect(updated).toEqual({ a: 2 });
    expect(updated.hidden).toBe(9);
  });

  it('does not preserve non-enumerability of deleted fields', () => {
    const obj = { a: 1 };
    Object.defineProperty(obj, 'hidden', hiddenDesc);

    const updated = produce(obj, draft => {
      draft.a = 2;
      delete draft.hidden;
      draft.hidden = 9;
    });

    expect(isEnumerable(updated, 'hidden')).toBeTrue();
    expect(updated).toEqual({ a: 2, hidden: 9 });
  });

  it('handles recipe throwing exception', () => {
    expect(() => {
      produce({}, () => {
        throw new Error('borken');
      });
    }).toThrow('borken');

    // ensure produce still works (scopes not broken)
    expect(
      produce({}, draft => {
        draft.a = 2;
      })
    ).toEqual({ a: 2 });
  });

  it('throws on nested produce', () => {
    expect(() => {
      produce({}, () => {
        produce({}, () => {
          //
        });
      });
    }).toThrow('Nested produce is not supported');
  });

  it('allows currying produce', () => {
    const fn = produce(draft => {
      draft.b = 9;
    });

    expect(fn({ a: 1 })).toEqual({ a: 1, b: 9 });
  });

  it('allows returning from recipe', () => {
    const obj = { a: { foo: 3 } };
    const ret = produce(obj, draft => {
      draft.a.foo = 5;
      return { q: draft.a };
    });
    expect(ret).not.toEqual(obj);
    expect(ret).toEqual({ q: { foo: 5 } });
  });

  it('allows returning null from recipe', () => {
    expect(produce({}, () => null)).toBe(null);
  });

  it('allows returning undefined from recipe with NOTHING sentinel', () => {
    expect(produce({}, () => NOTHING)).toBe(undefined);
  });

  it('throws on draft usage outside of produce', () => {
    let draft;
    produce({ foo: 1 }, d => {
      draft = d;
    });

    expect(() => draft.foo).toThrow('use of draft outside produce');
    expect(() => (draft.foo = 1)).toThrow('use of draft outside produce');
  });

  describe.each([
    ['primitive', () => 3, () => 5],
    ['object', () => ({ a: 3 }), () => ({ a: 5 })],
  ])('field mutations with %s as value', (_name, makeStart, makeUpdate) => {
    it('detects field modification', () => {
      const start = makeStart();
      const obj = { field: start, other: 9 };
      const val = makeUpdate();
      const updated = produce(obj, draft => {
        draft.field = val;
      });

      expect(obj).not.toBe(updated);
      expect(obj.field).not.toBe(updated.field);

      expect(obj).toEqual({ field: start, other: 9 });
      expect(updated).toEqual({ field: val, other: 9 });
    });

    it('detects field additions', () => {
      const obj = { other: 9 };
      const val = makeUpdate();
      const updated = produce(obj, draft => {
        draft.field = val;
      });

      expect(obj).not.toBe(updated);
      expect(obj.field).not.toBe(updated.field);

      expect(obj).toEqual({ other: 9 });
      expect(updated).toEqual({ field: val, other: 9 });
    });

    it('detects field removal', () => {
      const start = makeStart();
      const obj = { field: start, other: 9 };
      const updated = produce(obj, draft => {
        delete draft.field;
      });

      expect(obj).not.toBe(updated);
      expect(obj.field).not.toBe(updated.field);

      expect(obj).toEqual({ field: start, other: 9 });
      expect(updated).toEqual({ other: 9 });
    });

    it('detects field removal then addition', () => {
      const start = makeStart();
      const obj = { field: start };
      const val = makeUpdate();
      const updated = produce(obj, draft => {
        delete draft.field;
        draft.field = val;
      });

      expect(obj).not.toBe(updated);
      expect(obj.field).not.toBe(updated.field);

      expect(obj).toEqual({ field: start });
      expect(updated).toEqual({ field: val });
    });

    it('detects field modification, removal, then addition', () => {
      const start = makeStart();
      const obj = { field: start };
      const val = makeUpdate();
      const updated = produce(obj, draft => {
        draft.field = 9999;
        delete draft.field;
        draft.field = val;
      });

      expect(obj).not.toBe(updated);
      expect(obj.field).not.toBe(updated.field);

      expect(obj).toEqual({ field: start });
      expect(updated).toEqual({ field: val });
    });
  });

  describe.each([
    ['primitive', () => 3, () => 5],
    ['object', () => ({ a: 3 }), () => ({ a: 5 })],
  ])('array mutations with %s as value', (_name, makeStart, makeUpdate) => {
    it('detects array entry modification', () => {
      const start = makeStart();
      const arr = [1, start, 3];
      const val = makeUpdate();
      const updated = produce(arr, draft => {
        draft[1] = val;
      });

      expect(arr).not.toBe(updated);
      expect(arr[1]).not.toBe(updated[1]);

      expect(arr).toEqual([1, start, 3]);
      expect(updated).toEqual([1, val, 3]);
    });

    it('detects array additions at end', () => {
      const arr = [1, 2];
      const val = makeUpdate();
      const updated = produce(arr, draft => {
        draft.push(val);
      });

      expect(arr).not.toBe(updated);

      expect(arr).toEqual([1, 2]);
      expect(updated).toEqual([1, 2, val]);
    });

    it('detects array additions in middle', () => {
      const arr = [1, 2];
      const val = makeUpdate();
      const updated = produce(arr, draft => {
        draft.splice(1, 0, val);
      });

      expect(arr).not.toBe(updated);

      expect(arr).toEqual([1, 2]);
      expect(updated).toEqual([1, val, 2]);
    });

    it('detects array entry removal at end', () => {
      const start = makeStart();
      const arr = [1, 2, start];
      const updated = produce(arr, draft => {
        draft.pop();
      });

      expect(arr).not.toBe(updated);

      expect(arr).toEqual([1, 2, start]);
      expect(updated).toEqual([1, 2]);
    });

    it('detects array entry removal in middle', () => {
      const start = makeStart();
      const arr = [1, start, 2];
      const updated = produce(arr, draft => {
        draft.splice(1, 1);
      });

      expect(arr).not.toBe(updated);

      expect(arr).toEqual([1, start, 2]);
      expect(updated).toEqual([1, 2]);
    });

    it('detects array entry removal then addition', () => {
      const start = makeStart();
      const arr = [1, 2, start];
      const val = makeUpdate();
      const updated = produce(arr, draft => {
        draft.pop();
        draft.push(val);
      });

      expect(arr).not.toBe(updated);
      expect(arr[2]).not.toBe(updated[2]);

      expect(arr).toEqual([1, 2, start]);
      expect(updated).toEqual([1, 2, val]);
    });

    it('detects array entry modification, removal, then addition', () => {
      const start = makeStart();
      const arr = [1, 2, start];
      const val = makeUpdate();
      const updated = produce(arr, draft => {
        draft[2] = 9999;
        draft.pop();
        draft.push(val);
      });

      expect(arr).not.toBe(updated);
      expect(arr[2]).not.toBe(updated[2]);

      expect(arr).toEqual([1, 2, start]);
      expect(updated).toEqual([1, 2, val]);
    });
  });

  describe('falsy transitions are recorded', () => {
    it.each([
      [undefined, null],
      [undefined, false],
      [undefined, true],
      [null, undefined],
      [null, false],
      [null, true],
      [false, undefined],
      [false, null],
      [false, true],
    ])('handles field being set to %s', (from, to) => {
      const obj = { field: from };
      const updated = produce(obj, draft => {
        draft.field = to;
        expect(draft.field).toBe(to);
      });
      expect(obj.field).toBe(from);
      expect(updated.field).toBe(to);
    });
  });
});

describe('original', () => {
  it('returns the original value from a draft', () => {
    const obj = { a: 99, b: {}, c: [] };
    produce(obj, draft => {
      expect(original(draft.b)).toBe(obj.b);
      expect(original(draft.c)).toBe(obj.c);

      expect(() => original(draft.a)).toThrow('expected a draft, got 99');
      expect(() => original({})).toThrow(
        'expected a draft, got [object Object]'
      );
    });
  });
});

describe('isDraft', () => {
  it('checks whether an object is a draft or not', () => {
    const obj = { a: 99, b: {}, c: [] };
    let ref;
    produce(obj, draft => {
      ref = draft.b;
      expect(isDraft(draft.a)).toBe(false);
      expect(isDraft(draft.b)).toBe(true);
      expect(isDraft(draft.c)).toBe(true);

      expect(isDraft(obj.b)).toBe(false);
      expect(isDraft(obj.c)).toBe(false);

      expect(isDraft(null)).toBe(false);
      expect(isDraft(false)).toBe(false);
      expect(isDraft(0)).toBe(false);
      expect(isDraft({})).toBe(false);
      expect(isDraft('')).toBe(false);
    });

    expect(isDraft(ref)).toBe(true);
  });
});
