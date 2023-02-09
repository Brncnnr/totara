/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @module tui
 */
import { addListener, removeListener, testUtils } from 'tui/dom/keyboard_stack';

describe('keyboard_getStack()', () => {
  let oldAddListener;
  let keypressAdded;

  // this should map to listener
  let keydownCallback;

  function newListener(type, callback) {
    if (type != 'keydown') {
      oldAddListener(type, callback);
      return;
    }

    keydownCallback = callback;
    keypressAdded++;
  }

  beforeAll(() => {
    keypressAdded = 0;
    keydownCallback = null;
    oldAddListener = document.addEventListener;
    document.addEventListener = newListener;
  });

  beforeEach(() => {
    testUtils.reset();
  });

  afterEach(() => {
    document.addEventListener = oldAddListener;
  });

  it('addListener works as expected', () => {
    let spy = jest.fn();
    addListener('a', spy);
    let stack = testUtils.getStack();

    expect(stack.a.length).toBe(1);
    expect(stack.a[0]).toBe(spy);

    addListener('a', spy);
    expect(stack.a.length).toBe(2);
    expect(stack.a[0]).toBe(spy);
    expect(stack.a[1]).toBe(spy);

    let spy2 = jest.fn();
    let spy3 = jest.fn();
    addListener('Esc', spy2);
    addListener('Escape', spy3);
    expect(stack.Escape.length).toBe(2);
    expect(stack.Escape[0]).toBe(spy2);
    expect(stack.Escape[1]).toBe(spy3);

    expect(keypressAdded).toBe(1);

    let oldError = console.error;
    let errorSpy = jest.fn();
    console.error = errorSpy;
    addListener('z', 'some string');
    expect(errorSpy).toHaveBeenCalledWith(
      'Supplied callback to keyboard_stack.addListener is not a function'
    );

    console.error = oldError;
  });

  it('Listening works as expected', () => {
    let spy = jest.fn();
    let stack = testUtils.getStack();
    addListener('Esc', spy);
    addListener('Escape', spy);
    expect(stack.Escape.length).toBe(2);
    expect(stack.Escape[0]).toBe(spy);
    expect(stack.Escape[1]).toBe(spy);

    expect(keypressAdded).toBe(1);

    let kbEvent = new KeyboardEvent('keydown', {
      key: 'Esc',
    });
    keydownCallback(kbEvent);
    expect(spy).toHaveBeenCalledTimes(2);

    kbEvent = new KeyboardEvent('keydown', {
      key: 'Escape',
    });
    keydownCallback(kbEvent);
    expect(spy).toHaveBeenCalledTimes(4);

    kbEvent = new KeyboardEvent('keydown', {
      key: 'a',
    });
    keydownCallback(kbEvent);
    expect(spy).toHaveBeenCalledTimes(4);
  });

  it('preventPropogation works as expected', () => {
    let propagateStopped = jest.fn(event => {
      event.stopPropagation();
    });
    let propagate = jest.fn();
    let keyEvent = new KeyboardEvent('keydown', {
      key: 'Escape',
    });

    addListener('Esc', propagate);
    addListener('Esc', propagate);
    addListener('Esc', propagateStopped);
    addListener('a', propagate);

    keydownCallback(keyEvent);

    expect(propagate).not.toHaveBeenCalled();
    expect(propagateStopped).toHaveBeenCalled();

    keyEvent = new KeyboardEvent('keydown', {
      key: 'Escape',
    });
    removeListener('Esc', propagateStopped);
    keydownCallback(keyEvent);
    expect(propagate).toHaveBeenCalledTimes(2);
    // Once, because it was called in the last set
    expect(propagateStopped).toHaveBeenCalledTimes(1);
  });

  it('removing works as expected', () => {
    let spy = jest.fn();
    let spy2 = jest.fn();
    let spy3 = jest.fn();
    let stack = testUtils.getStack();

    addListener('a', spy);
    addListener('a', spy2);
    addListener('b', spy3);

    expect(stack.a.length).toBe(2);
    expect(stack.b.length).toBe(1);

    removeListener('a', spy2);
    expect(stack.a.length).toBe(1);
    expect(stack.a[0]).toBe(spy);
    expect(stack.b.length).toBe(1);

    // these shouldn't throw errors
    removeListener('q', spy);
    removeListener('b', spy);

    removeListener('a', spy);
    expect(stack.a).toBeUndefined();
  });
});
