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

/*

Shimmer is a small reimplementation of immer's API.

The main differences from immer are:
* Compatible with Vue-observed objects
* Smaller API, only `produce` is implemented
* Internal implementation is different
* Does not use Proxies, only implements ES5-compatible observation
* less than 3kb minified vs immer's ~15kb minified

Gotchas:
* If you have multiple references to the same object, the others will not
  update when you modify one. Immer has the same limitation, as it is designed
  to work on trees rather than graphs.
  This is not possible to solve without walking the entire graph.
* Reference loops are not supported.

*/

import { hasOwnProperty } from '../../util';
import { isPlainObject } from '../util/object';
// eslint-disable-next-line no-unused-vars
import { enterScope, getCurrentScope, leaveScope, Scope } from './scope';
import { is } from './util';

/**
 * @typedef {Object} State
 * @property {object|array} orig
 * @property {object|array|null} proxy
 * @property {State} parent
 * @property {object} copy
 * @property {boolean} modified
 * @property {Scope} scope
 * @property {boolean} finalised
 * @property {boolean} revoked
 */

const STATE = Symbol('shimmer.state');

/**
 * Sentinel value used to return `undefined` from recipes.
 */
export const NOTHING = Symbol('shimmer.nothing');

/**
 * Keys to ignore for all functionality - copying, proxying, etc.
 */
const excludedKeys = { [STATE]: true, __ob__: true };

/**
 * Create an immutable copy of value on-demand as it is mutated by recipe.
 *
 * Unchanged parts of value are preserved.
 *
 * Return a new value from recipe to replace the whole object.
 *
 * @template {object|array} T
 * @param {T} value
 * @param {(draft: T) => any} recipe
 * @returns {T}
 */
export function produce(value, recipe) {
  // currying support
  if (typeof value === 'function' && recipe == null) {
    return param => produce(param, value);
  }
  if (!isDraftable(value)) {
    throw new Error('value is not draftable');
  }

  const scope = enterScope();

  let result;
  try {
    // create a draft from the value we were provided
    let draft = createProxy(value);

    const returnVal = recipe(draft);
    if (returnVal !== undefined) {
      draft = returnVal === NOTHING ? undefined : returnVal;
    }

    // finalise - convert drafts to plain objects
    markScope(scope, 'finalised', true);
    sweepChanges(scope);
    result = finalise(draft);
  } finally {
    // clean up scope
    leaveScope(scope);
    markScope(scope, 'revoked', true);
  }

  return result;
}

/**
 * Get original object from a draft inside of produce.
 *
 * This is useful to compare non-draft versions of objects, or if you want to
 * save a value from inside produce for use outside.
 *
 * @param {*} value
 * @returns {*}
 */
export function original(value) {
  if (!isDraft(value)) {
    throw new Error('expected a draft, got ' + value);
  }
  return value[STATE].orig;
}

/**
 * Check if object is a draft.
 *
 * @param {*} value
 * @returns {boolean}
 */
export function isDraft(value) {
  return !!value && !!value[STATE];
}

/**
 * Create a proxy/draft object for a value.
 *
 * @template {object|array} T
 * @param {T} value Object/array to proxy
 * @param {object} parent Parent state object (if not root)
 * @returns {T}
 */
function createProxy(value, parent) {
  /** @type {State} */
  const state = {
    orig: value,
    proxy: null,
    parent,
    copy: null,
    modified: false,
    scope: getCurrentScope(),
    finalised: false,
    revoked: false,
  };

  let proxy;
  if (Array.isArray(value)) {
    proxy = proxyArray(state);
  } else {
    proxy = proxyObject(state);
  }

  // Symbols are already non-enumerable by default
  proxy[STATE] = state;

  state.proxy = proxy;
  state.scope.drafts.push(state);

  return proxy;
}

/**
 * Create a proxy for an array.
 *
 * @param {State} state
 * @returns {array}
 */
function proxyArray(state) {
  const orig = state.orig;
  const proxy = Array(orig.length);
  for (let i = 0; i < orig.length; i++) {
    Object.defineProperty(proxy, i, propertyProxyDesc(i, true));
  }
  return proxy;
}

/**
 * Create a proxy for an object.
 *
 * @param {State} state
 * @returns {object}
 */
function proxyObject(state) {
  const proxy = {};
  getOwnKeys(state.orig).forEach(key => {
    const descriptor = Object.getOwnPropertyDescriptor(state.orig, key);
    const desc = propertyProxyDesc(key, descriptor.enumerable);
    Object.defineProperty(proxy, key, desc);
  });

  return proxy;
}

/**
 * Finalise proxy/draft - get the final value to return.
 *
 * @template {object|array} T
 * @param {T} proxy
 * @returns {T}
 */
function finalise(proxy) {
  if (proxy == null) {
    return proxy;
  }
  if (!proxy[STATE]) {
    // handle cases like: draft.x = { y: draft.x.y }
    // note: immer behavior difference, immer will miss proxies set on symbols on plain objects
    if (isDraftable(proxy)) {
      getOwnKeys(proxy).forEach(key => {
        const value = proxy[key];
        const newValue = finalise(value);
        if (value !== newValue) {
          proxy[key] = newValue;
        }
      });
    }
    return proxy;
  }
  const state = proxy[STATE];
  if (!state.modified) {
    return state.orig;
  }

  return makeCopy(proxy, finalise);
}

/**
 * Set a state field to a value on all drafts within the provided scope
 *
 * @param {Scope} scope
 * @param {string} field
 * @param {*} value
 */
function markScope(scope, field, value) {
  const drafts = scope.drafts;
  for (let i = drafts.length - 1; i >= 0; i--) {
    drafts[i][field] = value;
  }
}

/**
 * Check drafts in scope for changes not caught by setters and mark them as
 * changed if they have.
 *
 * @param {Scope} scope
 */
function sweepChanges(scope) {
  const drafts = scope.drafts;
  // iterate backwards as it's slightly more efficient to scan leaf nodes first,
  // as that will mark the parents as changed and we can skip scanning them
  for (let i = drafts.length - 1; i >= 0; i--) {
    const state = drafts[i];
    if (!state.modified) {
      if (hasChanges(state)) {
        markChanged(state);
      }
    }
  }
}

/**
 * Check if draft state has any changes.
 *
 * @param {State} state
 * @returns {boolean}
 */
function hasChanges(state) {
  const { proxy } = state;

  if (Array.isArray(proxy)) {
    for (let i = proxy.length - 1; i >= 0; i--) {
      if (keyIsChanged(state, i)) {
        return true;
      }
    }
    // no added or changed entries, length should be identical unless some were
    // removed
    return proxy.length !== state.orig.length;
  } else {
    const keys = getOwnKeys(proxy);
    // added keys usually appear last so scan from the end
    for (let i = keys.length - 1; i >= 0; i--) {
      if (keyIsChanged(state, keys[i])) {
        return true;
      }
    }

    // no added or changed keys, length should be identical unless keys have been
    // removed (special keys like STATE are already stripped from both arrays)
    return keys.length !== getOwnKeys(state.orig).length;
  }
}

/**
 * Check if key is changed from original value.
 *
 * @param {State} state
 * @param {string|number|symbol} key
 * @returns {boolean}
 */
function keyIsChanged(state, key) {
  const original = state.orig[key];
  const current = state.proxy[key];

  // check for added key
  if (original === undefined && !hasOwnProperty(state.orig, key)) {
    return true;
  }
  // sub objects -- ignore if orignal is the same (will be handled by sweepChanges)
  if (current && current[STATE] && current[STATE].orig === original) {
    return false;
  }
  // handle erased descriptors - normally this would be caught by trapSet()
  if (!is(current, original)) {
    return true;
  }

  return false;
}

/**
 * Mark a draft state and its parents has having been changed.
 *
 * This lets us know to clone it later on.
 *
 * @param {State} state
 */
function markChanged(state) {
  if (!state.modified) {
    state.modified = true;
    // recursively mark parents as changed as well -- this is how we know to
    // clone them in finalise
    if (state.parent) {
      markChanged(state.parent);
    }
  }
}

/**
 * Get current recorded value of property, without triggering getter and creating a proxy.
 *
 * @param {State} state
 * @param {string|number|symbol} prop
 * @returns {*}
 */
function peek(state, prop) {
  return state.copy ? state.copy[prop] : state.orig[prop];
}

/**
 * Check if provided object can be proxied and immutably updated.
 *
 * @param {*} obj
 * @returns {boolean}
 */
function isDraftable(obj) {
  return Array.isArray(obj) || isPlainObject(obj);
}

/**
 * Get keys of object, including symbols but excluding excluded keys.
 *
 * @param {object} obj
 * @returns {Array<string|symbol>}
 */
function getOwnKeys(obj) {
  return Object.getOwnPropertyNames(obj)
    .concat(Object.getOwnPropertySymbols(obj))
    .filter(x => !excludedKeys[x]);
}

/**
 * Create PropertyDescriptor for proxying property.
 *
 * @param {string|number|symbol} prop
 * @param {boolean} enumerable
 * @returns {PropertyDescriptor}
 */
function propertyProxyDesc(prop, enumerable) {
  return {
    configurable: true,
    enumerable,
    get() {
      return trapGet(this[STATE], prop);
    },
    set(value) {
      trapSet(this[STATE], prop, value);
    },
  };
}

/**
 * Trap read of a field - return modified value if exists, and create proxy if
 * need be.
 *
 * @param {State} state
 * @param {string|number|symbol} prop
 * @returns {*}
 */
function trapGet(state, prop) {
  stateValid(state);
  let value = peek(state, prop);

  // convert to a proxy if we need to and it is not already
  if (!state.finalised && isDraftable(value) && !value[STATE]) {
    value = createProxy(value, state);
    storeValue(state, prop, value);
  }

  return value;
}

/**
 * Trap set of a field - marks the object as modified and saves the modified
 * value to the state.
 *
 * @param {State} state
 * @param {string|number|symbol} prop
 * @param {*} value
 */
function trapSet(state, prop, value) {
  stateValid(state);
  if (peek(state, prop) === value) {
    // no change
    return;
  }
  markChanged(state);
  storeValue(state, prop, value);
}

/**
 * Store value in in draft state.
 *
 * @param {State} state
 * @param {string|number|symbol} prop
 * @param {*} value
 */
function storeValue(state, prop, value) {
  if (!state.copy) {
    state.copy = makeCopy(state.orig);
  }
  state.copy[prop] = value;
}

/**
 * Make a shallow copy of an object or array, preserving symbols and
 * enumerability.
 *
 * @template {object|array} T
 * @param {} obj
 * @param {(value: any) => any} valueHook
 *  Hook to modify value before it is set (used in finalise())
 * @returns {T}
 */
function makeCopy(obj, valueHook) {
  if (Array.isArray(obj)) {
    return valueHook
      ? Array.prototype.map.call(obj, valueHook)
      : Array.prototype.slice.call(obj);
  } else {
    const result = {};
    getOwnKeys(obj).forEach(key => {
      const descriptor = Object.getOwnPropertyDescriptor(obj, key);
      let value = obj[key];
      if (valueHook) {
        value = valueHook(value);
      }
      if (descriptor.enumerable) {
        result[key] = value;
      } else {
        Object.defineProperty(result, key, {
          configurable: true,
          enumerable: false,
          value,
          writable: true,
        });
      }
    });
    return result;
  }
}

/**
 * Assert state is not revoked.
 *
 * @throws {Error}
 * @param {State} state
 */
function stateValid(state) {
  if (state.revoked) {
    throw new Error('use of draft outside produce');
  }
}
