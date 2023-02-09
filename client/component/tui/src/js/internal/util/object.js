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

/**
 * Object.prototype.hasOwnProperty wrapper.
 *
 * @param {object} obj
 * @param {string} key
 * @returns {boolean}
 */
export function hasOwnProperty(obj, key) {
  return Object.prototype.hasOwnProperty.call(obj, key);
}

const objectConstructorString = Function.prototype.toString.call(Object);

/**
 * Check if an object is a plain object (i.e. {}), not a class instance etc.
 *
 * @param {*} value
 * @returns {boolean}
 */
export function isPlainObject(value) {
  if (!value || typeof value != 'object') {
    return false;
  }
  const proto = Object.getPrototypeOf(value);
  if (proto == null) {
    return true;
  }
  const ctor = hasOwnProperty(proto, 'constructor') && proto.constructor;
  return (
    typeof ctor == 'function' &&
    ctor instanceof ctor &&
    Function.prototype.toString.call(ctor) === objectConstructorString
  );
}

/**
 * Structural deep clone.
 *
 * Only clones plain objects and arrays, other values are left alone.
 *
 * @param {(object|array)} obj
 * @return {(object|array)}
 */
export function structuralDeepClone(obj) {
  return structuralCloneImpl(obj, true, []);
}

/**
 * Structural shallow clone.
 *
 * Only clones plain objects and arrays, other values are left alone.
 *
 * @param {(object|array)} obj
 * @return {(object|array)}
 */
export function structuralShallowClone(obj) {
  return structuralCloneImpl(obj, false);
}

function structuralCloneImpl(obj, deep, stack) {
  if (stack && stack.includes(obj)) {
    throw new TypeError('Cannot clone circular structure.');
  }

  let result = obj;
  const isArr = Array.isArray(obj),
    isObj = isPlainObject(obj);

  if (isArr || isObj) {
    if (isArr) {
      result = [];
    } else {
      result = {};
    }

    for (let key in obj) {
      if (hasOwnProperty(obj, key)) {
        let substack;
        if (deep) {
          substack = stack.slice();
          substack.push(result);
        }
        result[key] = deep
          ? structuralCloneImpl(obj[key], deep, substack)
          : obj[key];
      }
    }
  }

  return result;
}

/**
 * Get the value of path at object.
 *
 * @param {object} object
 * @param {array|string|number} path Path of property to get, e.g. ['a', 2, 'q']
 * @returns {*} Value at path.
 */
export function get(object, path) {
  if (typeof path === 'string' || typeof path === 'number') {
    if (path === '__proto__') {
      return;
    }
    return object[path];
  }

  let i = 0;
  while (object != null && i < path.length) {
    const key = path[i++];
    if (key === '__proto__') {
      return;
    }
    object = object[key];
  }
  return object;
}

export const isIndex = val =>
  typeof val == 'number' || /^(?:0|[1-9]\d*)$/.test(val);

const setKey = (target, key, value) => {
  target[key] = value;
};

/**
 * Set the value at path of object.
 *
 * Arrays are created for missing index properties and objects are created for other missing properties.
 *
 * @param {object} object Object to modify.
 * @param {array|string|number} path Path of property to set, e.g. ['a', 2, 'q']
 * @param {*} value
 * @returns {object}
 */
export function set(object, path, value) {
  return baseSet(object, path, value, setKey);
}

/**
 * Base implementation for set and vue_util.set.
 *
 * @param {object} object Object to modify.
 * @param {array|string|number} path Path of property to set, e.g. ['a', 2, 'q']
 * @param {*} value
 * @param {*} setKey
 * @returns {object}
 */
export function baseSet(object, path, value, setKey) {
  if (typeof path === 'string' || typeof path === 'number') {
    if (path === '__proto__') {
      return;
    }
    setKey(object, path, value);
    return;
  }

  const lastIndex = path.length - 1;
  let index = 0;

  while (object != null && index <= lastIndex) {
    const key = path[index];
    if (key === '__proto__') {
      return;
    }
    if (index == lastIndex) {
      setKey(object, key, value);
    } else {
      if (object[key] == null) {
        // no object at key, create one
        setKey(object, key, isIndex(path[index + 1]) ? [] : {});
      }
    }
    object = object[key];
    index++;
  }
  return object;
}
