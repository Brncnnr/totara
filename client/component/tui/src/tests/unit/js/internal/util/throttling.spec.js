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
  debounce,
  throttle,
  _throttleCommon,
} from '../../../../../js/internal/util/throttling';

describe('throttleCommon', () => {
  let fn, done, throttled;
  beforeEach(() => {
    jest.useFakeTimers();
    fn = jest.fn();
    done = jest.fn();
  });

  it('should throttle a function', () => {
    throttled = _throttleCommon(fn, 10, {}, done);
    throttled();
    throttled();
    throttled();
    expect(fn).toHaveBeenCalledTimes(1);
    jest.advanceTimersByTime(15);
    expect(fn).toHaveBeenCalledTimes(2);
    jest.advanceTimersByTime(15);
    expect(done).toHaveBeenCalledTimes(1);

    throttled();
    expect(fn).toHaveBeenCalledTimes(3);
    expect(done).toHaveBeenCalledTimes(1);
    jest.advanceTimersByTime(15);
    expect(fn).toHaveBeenCalledTimes(3);
    jest.advanceTimersByTime(15);
    expect(done).toHaveBeenCalledTimes(2);
  });

  it('should debounce a function with extendWait', () => {
    const debounced = _throttleCommon(
      fn,
      10,
      { leading: false, extendWait: true },
      done
    );
    debounced();
    debounced();
    debounced();
    expect(fn).not.toHaveBeenCalled();
    jest.advanceTimersByTime(15);
    expect(fn).toHaveBeenCalledTimes(1);
    jest.advanceTimersByTime(15);
    expect(done).toHaveBeenCalledTimes(1);

    debounced();
    expect(fn).toHaveBeenCalledTimes(1);
    expect(done).toHaveBeenCalledTimes(1);
    jest.advanceTimersByTime(15);
    expect(fn).toHaveBeenCalledTimes(2);
    jest.advanceTimersByTime(15);
    expect(done).toHaveBeenCalledTimes(2);
  });

  it('passes the last arguments and context value to the function', () => {
    fn = jest.fn(function(arg) {
      return `${this}-${arg}`;
    });
    throttled = _throttleCommon(fn, 10, {}, done);

    throttled.call(1, 2);
    throttled.call(3, 4);
    throttled.call(5, 6);
    jest.advanceTimersByTime(15);
    expect(fn).toHaveReturnedWith('1-2'); // leading
    expect(fn).not.toHaveReturnedWith('3-4');
    expect(fn).toHaveReturnedWith('5-6'); // trailing
  });

  it('supports leading and trailing options', () => {
    throttled = _throttleCommon(
      fn,
      10,
      { leading: true, trailing: false },
      done
    );
    expect(fn).not.toHaveBeenCalled();
    throttled();
    expect(fn).toHaveBeenCalledTimes(1);
    throttled();
    expect(fn).toHaveBeenCalledTimes(1);
    expect(done).not.toHaveBeenCalled();
    jest.advanceTimersByTime(15);
    expect(fn).toHaveBeenCalledTimes(1);
    expect(done).toHaveBeenCalledTimes(1);

    jest.advanceTimersByTime(15);
    fn.mockClear();
    done.mockClear();

    throttled = _throttleCommon(
      fn,
      10,
      { leading: false, trailing: true },
      done
    );
    expect(fn).not.toHaveBeenCalled();
    throttled();
    expect(fn).toHaveBeenCalledTimes(0);
    throttled();
    expect(fn).toHaveBeenCalledTimes(0);
    expect(done).not.toHaveBeenCalled();
    jest.advanceTimersByTime(15);
    expect(fn).toHaveBeenCalledTimes(1);
    expect(done).not.toHaveBeenCalled();
    jest.advanceTimersByTime(15);
    expect(fn).toHaveBeenCalledTimes(1);
    expect(done).toHaveBeenCalledTimes(1);
  });

  it('only invokes trailing edge if called during wait period if leading and trailing are true', () => {
    throttled = _throttleCommon(fn, 10, {}, done);

    throttled();
    expect(fn).toHaveBeenCalledTimes(1);
    jest.advanceTimersByTime(15);
    expect(fn).toHaveBeenCalledTimes(1);

    throttled();
    throttled();
    expect(fn).toHaveBeenCalledTimes(2);
    jest.advanceTimersByTime(15);
    expect(fn).toHaveBeenCalledTimes(3);
  });

  // more complex combined tests:

  it('calls function at most every x ms (leading: true, trailing: true)', () => {
    throttled = _throttleCommon(fn, 10, {}, done);

    throttled();
    expect(fn).toHaveBeenCalledTimes(1);
    throttled();
    expect(fn).toHaveBeenCalledTimes(1);
    jest.advanceTimersByTime(7); // = 7
    expect(fn).toHaveBeenCalledTimes(1);
    jest.advanceTimersByTime(7); // = 14
    // trailing should now have been called
    expect(fn).toHaveBeenCalledTimes(2);
    jest.advanceTimersByTime(7); // = 21
    expect(fn).toHaveBeenCalledTimes(2);

    jest.advanceTimersByTime(9); // = 30

    throttled();
    expect(fn).toHaveBeenCalledTimes(3);
    throttled();
    expect(fn).toHaveBeenCalledTimes(3);
    jest.advanceTimersByTime(7); // = 37
    expect(fn).toHaveBeenCalledTimes(3);
    throttled();
    jest.advanceTimersByTime(7); // = 44
    expect(fn).toHaveBeenCalledTimes(4);
    throttled();
    jest.advanceTimersByTime(7); // = 51
    expect(fn).toHaveBeenCalledTimes(5);

    jest.advanceTimersByTime(10); // = 60
    expect(fn).toHaveBeenCalledTimes(5);
  });

  it('calls function at most every x ms (leading: false, trailing: true)', () => {
    throttled = _throttleCommon(fn, 10, { leading: false }, done);

    throttled();
    expect(fn).toHaveBeenCalledTimes(0);
    throttled();
    expect(fn).toHaveBeenCalledTimes(0);
    jest.advanceTimersByTime(7); // = 7
    expect(fn).toHaveBeenCalledTimes(0);
    jest.advanceTimersByTime(7); // = 14
    // trailing should now have been called
    expect(fn).toHaveBeenCalledTimes(1);
    jest.advanceTimersByTime(7); // = 21
    expect(fn).toHaveBeenCalledTimes(1);

    jest.advanceTimersByTime(9); // = 30

    throttled();
    expect(fn).toHaveBeenCalledTimes(1);
    throttled();
    expect(fn).toHaveBeenCalledTimes(1);
    jest.advanceTimersByTime(7); // = 37
    expect(fn).toHaveBeenCalledTimes(1);
    throttled();
    jest.advanceTimersByTime(7); // = 44
    expect(fn).toHaveBeenCalledTimes(2);
    throttled();
    jest.advanceTimersByTime(7); // = 51
    expect(fn).toHaveBeenCalledTimes(3);

    jest.advanceTimersByTime(10); // = 60
    expect(fn).toHaveBeenCalledTimes(3);
  });

  it('calls function at most every x ms (leading: true, trailing: false)', () => {
    throttled = _throttleCommon(fn, 10, { trailing: false }, done);

    throttled();
    expect(fn).toHaveBeenCalledTimes(1);
    throttled();
    expect(fn).toHaveBeenCalledTimes(1);
    jest.advanceTimersByTime(7); // = 7
    expect(fn).toHaveBeenCalledTimes(1);
    jest.advanceTimersByTime(7); // = 14
    expect(fn).toHaveBeenCalledTimes(1);
    jest.advanceTimersByTime(7); // = 21
    expect(fn).toHaveBeenCalledTimes(1);

    jest.advanceTimersByTime(9); // = 30

    throttled();
    expect(fn).toHaveBeenCalledTimes(2);
    throttled();
    expect(fn).toHaveBeenCalledTimes(2);
    jest.advanceTimersByTime(7); // = 37
    expect(fn).toHaveBeenCalledTimes(2);
    throttled();
    jest.advanceTimersByTime(7); // = 44
    expect(fn).toHaveBeenCalledTimes(2);
    throttled();
    jest.advanceTimersByTime(7); // = 51
    expect(fn).toHaveBeenCalledTimes(3);

    jest.advanceTimersByTime(10); // = 60
    expect(fn).toHaveBeenCalledTimes(3);
  });

  it('extendWait waits until there have been x ms since the last call (leading: false, trailing: true)', () => {
    const debounced = _throttleCommon(
      fn,
      10,
      { leading: false, trailing: true, extendWait: true },
      done
    );
    debounced();
    expect(fn).not.toHaveBeenCalled();
    debounced();
    jest.advanceTimersByTime(7);
    expect(fn).not.toHaveBeenCalled();
    expect(done).not.toHaveBeenCalled();
    jest.advanceTimersByTime(7);
    expect(fn).toHaveBeenCalledTimes(1);
    expect(done).not.toHaveBeenCalled();
    jest.advanceTimersByTime(7);
    expect(done).toHaveBeenCalledTimes(1);

    jest.advanceTimersByTime(15);
    expect(fn).toHaveBeenCalledTimes(1);
    expect(done).toHaveBeenCalledTimes(1);

    debounced();
    jest.advanceTimersByTime(7);
    debounced();
    jest.advanceTimersByTime(7);
    debounced();
    jest.advanceTimersByTime(7);
    expect(fn).toHaveBeenCalledTimes(1);
    expect(done).toHaveBeenCalledTimes(1);
    jest.advanceTimersByTime(7);
    expect(fn).toHaveBeenCalledTimes(2);
    jest.advanceTimersByTime(7);
    expect(done).toHaveBeenCalledTimes(2);
  });

  it('extendWait waits until there have been x ms since the last call (leading: true, trailing: true)', () => {
    const debounced = _throttleCommon(
      fn,
      10,
      { leading: true, trailing: true, extendWait: true },
      done
    );

    debounced();
    expect(fn).toHaveBeenCalledTimes(1);
    debounced();
    jest.advanceTimersByTime(7);
    expect(fn).toHaveBeenCalledTimes(1);
    expect(done).not.toHaveBeenCalled();
    jest.advanceTimersByTime(7);
    expect(fn).toHaveBeenCalledTimes(2);
    jest.advanceTimersByTime(7);
    expect(done).toHaveBeenCalledTimes(1);

    jest.advanceTimersByTime(15);
    expect(fn).toHaveBeenCalledTimes(2);
    expect(done).toHaveBeenCalledTimes(1);

    debounced();
    expect(fn).toHaveBeenCalledTimes(3);
    jest.advanceTimersByTime(7);
    debounced();
    jest.advanceTimersByTime(7);
    debounced();
    jest.advanceTimersByTime(7);
    expect(fn).toHaveBeenCalledTimes(3);
    expect(done).toHaveBeenCalledTimes(1);
    jest.advanceTimersByTime(7);
    expect(fn).toHaveBeenCalledTimes(4);
    jest.advanceTimersByTime(7);
    expect(done).toHaveBeenCalledTimes(2);
  });

  it('extendWait waits until there have been x ms since the last call (leading: true, trailing: false)', () => {
    const debounced = _throttleCommon(
      fn,
      10,
      { leading: true, trailing: false, extendWait: true },
      done
    );

    debounced();
    expect(fn).toHaveBeenCalledTimes(1);
    debounced();
    jest.advanceTimersByTime(7);
    expect(fn).toHaveBeenCalledTimes(1);
    expect(done).not.toHaveBeenCalled();
    jest.advanceTimersByTime(7);
    expect(fn).toHaveBeenCalledTimes(1);
    expect(done).toHaveBeenCalledTimes(1);

    jest.advanceTimersByTime(15);
    expect(fn).toHaveBeenCalledTimes(1);
    expect(done).toHaveBeenCalledTimes(1);

    debounced();
    expect(fn).toHaveBeenCalledTimes(2);
    jest.advanceTimersByTime(7);
    debounced();
    jest.advanceTimersByTime(7);
    debounced();
    jest.advanceTimersByTime(7);
    expect(fn).toHaveBeenCalledTimes(2);
    expect(done).toHaveBeenCalledTimes(1);
    jest.advanceTimersByTime(7);
    expect(fn).toHaveBeenCalledTimes(2);
    expect(done).toHaveBeenCalledTimes(2);
  });

  it('never calls the function (leading: false, trailing: false)', () => {
    throttled = _throttleCommon(
      fn,
      10,
      { leading: false, trailing: false },
      done
    );
    throttled();
    jest.advanceTimersByTime(20);
    expect(fn).toHaveBeenCalledTimes(0);
  });
});

describe('throttle', () => {
  let fn, throttled;
  beforeEach(() => {
    jest.useFakeTimers();
    fn = jest.fn();
  });

  it('should throttle a function', () => {
    throttled = throttle(fn, 10);
    throttled();
    throttled();
    throttled();
    expect(fn).toHaveBeenCalledTimes(1);
    jest.advanceTimersByTime(15);
    expect(fn).toHaveBeenCalledTimes(2);
    jest.advanceTimersByTime(15);

    throttled();
    expect(fn).toHaveBeenCalledTimes(3);
    jest.advanceTimersByTime(15);
    expect(fn).toHaveBeenCalledTimes(3);
    jest.advanceTimersByTime(15);
  });

  it('should throttle functions separately depending on args if perArgs is passed', () => {
    throttled = throttle(fn, 10, { perArgs: true });
    throttled();
    throttled();
    throttled(1);
    throttled(1);
    throttled(2);
    throttled(2);
    expect(fn).toHaveBeenCalledTimes(3);
    jest.advanceTimersByTime(15);
    expect(fn).toHaveBeenCalledTimes(6);
    jest.advanceTimersByTime(15);
    jest.advanceTimersByTime(15);

    throttled();
    throttled(1);
    expect(fn).toHaveBeenCalledTimes(8);
    jest.advanceTimersByTime(15);
    expect(fn).toHaveBeenCalledTimes(8);
    jest.advanceTimersByTime(15);
  });
});

describe('debounce', () => {
  let fn, debounced;
  beforeEach(() => {
    jest.useFakeTimers();
    fn = jest.fn();
  });

  it('should debounce a function', () => {
    debounced = debounce(fn, 10);
    debounced();
    debounced();
    debounced();
    expect(fn).toHaveBeenCalledTimes(0);
    jest.advanceTimersByTime(15);
    expect(fn).toHaveBeenCalledTimes(1);
    jest.advanceTimersByTime(15);

    debounced();
    expect(fn).toHaveBeenCalledTimes(1);
    jest.advanceTimersByTime(15);
    expect(fn).toHaveBeenCalledTimes(2);
    jest.advanceTimersByTime(15);
  });

  it('should debounce functions separately depending on args if perArgs is passed', () => {
    debounced = debounce(fn, 10, { perArgs: true });
    debounced();
    debounced();
    debounced(1);
    debounced(1);
    debounced(2);
    debounced(2);
    expect(fn).toHaveBeenCalledTimes(0);
    jest.advanceTimersByTime(15);
    expect(fn).toHaveBeenCalledTimes(3);
    jest.advanceTimersByTime(15);
    jest.advanceTimersByTime(15);

    debounced();
    debounced(1);
    expect(fn).toHaveBeenCalledTimes(3);
    jest.advanceTimersByTime(15);
    expect(fn).toHaveBeenCalledTimes(5);
    jest.advanceTimersByTime(15);
  });
});
