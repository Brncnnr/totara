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

import { config } from '../config';
import { WebStorageStore } from '../storage';

const storage = new WebStorageStore('cache', window.localStorage, {
  rev: true,
});

const cache = new Map();

/**
 * Get value from cache.
 *
 * @param {string} key
 * @returns {*}
 */
export function cacheGet(key) {
  if (cache.has(key)) {
    return cache.get(key);
  }
  if (config.rev.js != -1) {
    const value = storage.get(key);
    cache.set(key, value);
    return value;
  }
  return null;
}

/**
 * Set value in cache.
 *
 * @param {string} key
 * @param {*} value
 */
export function cacheSet(key, value) {
  cache.set(key, value);
  if (config.rev.js != -1) {
    storage.set(key, value);
  }
}

/**
 * Remove value from cache.
 *
 * @param {string} key
 */
export function cacheDelete(key) {
  cache.delete(key);
  if (config.rev.js != -1) {
    storage.delete(key);
  }
}

/* istanbul ignore else */
if (process.env.NODE_ENV == 'test') {
  cacheGet.__resetInternalCache = () => cache.clear();
}
