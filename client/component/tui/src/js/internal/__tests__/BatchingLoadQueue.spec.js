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

import { flushMicrotasks } from '../../../tests/unit/util';
import BatchingLoadQueue from '../BatchingLoadQueue';

function defineCommonTests(opts) {
  it('waits at least {wait} ms before calling queue executor', async () => {
    const handler = jest.fn();
    const q = new BatchingLoadQueue({ handler, wait: 10, ...opts });

    const resolved = jest.fn();
    q.enqueue({}).then(resolved);

    expect(handler).not.toHaveBeenCalled();
    jest.advanceTimersByTime(1);
    expect(handler).not.toHaveBeenCalled();
    jest.advanceTimersByTime(10);
    expect(handler).toHaveBeenCalled();

    await flushMicrotasks();
    expect(resolved).toHaveBeenCalled();
  });

  it('defaults to a wait time of 0', async () => {
    const handler = jest.fn();
    const q = new BatchingLoadQueue({ handler, ...opts });

    const resolved = jest.fn();
    q.enqueue({}).then(resolved);

    expect(handler).not.toHaveBeenCalled();
    jest.advanceTimersByTime(0);
    expect(handler).toHaveBeenCalled();

    await flushMicrotasks();
    expect(resolved).toHaveBeenCalled();
  });

  it('deduplicates requests', () => {
    const handler = jest.fn(reqs => {
      expect(reqs).toEqual([1]);
    });
    const q = new BatchingLoadQueue({ handler, ...opts });
    q.enqueueMany([1, 1, 1]);

    jest.runAllTimers();
    expect(handler).toHaveBeenCalled();
  });

  it('enqueue requires an array of items', () => {
    const q = new BatchingLoadQueue({ handler: () => {}, ...opts });
    expect(() => q.enqueueMany(3)).toThrow('requests');
  });

  it('allows passing a custom equals function', () => {
    const handler = jest.fn(reqs => {
      expect(reqs).toEqual(['aardvark', 'bison']);
    });
    const q = new BatchingLoadQueue({
      handler,
      equals: (a, b) => a[0] === b[0],
    });
    q.enqueueMany(['aardvark', 'antelope', 'bison']);

    jest.runAllTimers();
    expect(handler).toHaveBeenCalled();
  });

  it('passes result to requesting code', async () => {
    const handler = jest.fn(() => 100);
    const q = new BatchingLoadQueue({ handler, wait: 10, ...opts });

    const p1 = q.enqueue({});
    const p2 = q.enqueue({});
    jest.runAllTimers();

    await expect(p1).resolves.toEqual(100);
    await expect(p2).resolves.toEqual(100);
  });

  it('passes error to requesting code', async () => {
    const handler = jest.fn(() => {
      throw new Error('nope');
    });
    const q = new BatchingLoadQueue({ handler, wait: 10, ...opts });

    const p1 = q.enqueue({});
    const p2 = q.enqueue({});
    jest.runAllTimers();

    await expect(p1).rejects.toThrow('nope');
    await expect(p2).rejects.toThrow('nope');
  });
}

describe('BatchingLoadQueue', () => {
  beforeEach(() => {
    jest.useFakeTimers();
  });

  defineCommonTests();

  it('does not wait until previous batch finishes before starting the next', async () => {
    let val = 1;
    const pendingHandlers = [];
    const handler = jest.fn(() => {
      const final = val++;
      return new Promise(r => pendingHandlers.push(() => r(final)));
    });
    const q = new BatchingLoadQueue({ handler, wait: 10 });

    const pa1r = jest.fn();
    const pa1 = q.enqueue('pa1');
    const pa2 = q.enqueue('pa2');
    pa1.then(pa1r);
    jest.runAllTimers();
    await flushMicrotasks();

    expect(handler).toHaveBeenCalledWith(['pa1', 'pa2']);
    expect(pa1r).not.toHaveBeenCalled();

    const pb1r = jest.fn();
    const pb1 = q.enqueue('pb1');
    pb1.then(pb1r);
    jest.runAllTimers();
    await flushMicrotasks();

    expect(handler).toHaveBeenCalledWith(['pb1']);
    expect(pa1r).not.toHaveBeenCalled();
    expect(pb1r).not.toHaveBeenCalled();

    pendingHandlers.shift()();
    pendingHandlers.shift()();

    jest.runAllTimers();
    await flushMicrotasks();

    expect(pa1r).toHaveBeenCalled();
    expect(pb1r).toHaveBeenCalled();

    await expect(pa1).resolves.toEqual(1);
    await expect(pa2).resolves.toEqual(1);
    await expect(pb1).resolves.toEqual(2);
  });
});

describe('BatchingLoadQueue serial', () => {
  beforeEach(() => {
    jest.useFakeTimers();
  });

  defineCommonTests({ serial: true });

  it('waits until previous batch finishes before starting the next', async () => {
    let val = 1;
    const pendingHandlers = [];
    const handler = jest.fn(() => {
      const final = val++;
      return new Promise(r => pendingHandlers.push(() => r(final)));
    });
    const q = new BatchingLoadQueue({ handler, wait: 10, serial: true });

    const pa1r = jest.fn();
    const pa1 = q.enqueue('pa1');
    const pa2 = q.enqueue('pa2');
    pa1.then(pa1r);
    jest.runAllTimers();
    await flushMicrotasks();

    expect(handler).toHaveBeenCalledWith(['pa1', 'pa2']);
    expect(pa1r).not.toHaveBeenCalled();

    const pb1r = jest.fn();
    const pb1 = q.enqueue('pb1');
    pb1.then(pb1r);
    jest.runAllTimers();
    await flushMicrotasks();

    expect(handler).not.toHaveBeenCalledWith(['pb1']);
    expect(pa1r).not.toHaveBeenCalled();
    expect(pb1r).not.toHaveBeenCalled();

    pendingHandlers.shift()();

    expect(handler).not.toHaveBeenCalledWith(['pb1']);
    expect(pa1r).not.toHaveBeenCalled();

    jest.runAllTimers();
    await flushMicrotasks();

    expect(pa1r).toHaveBeenCalled();

    jest.runAllTimers();
    await flushMicrotasks();

    expect(handler).toHaveBeenCalledWith(['pb1']);
    expect(pb1r).not.toHaveBeenCalled();

    pendingHandlers.shift()();

    jest.runAllTimers();
    await flushMicrotasks();

    expect(pb1r).toHaveBeenCalled();

    await expect(pa1).resolves.toEqual(1);
    await expect(pa2).resolves.toEqual(1);
    await expect(pb1).resolves.toEqual(2);
  });
});
