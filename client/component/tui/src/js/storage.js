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

import { config } from './config';

/* istanbul ignore next */
class Store {
  get() {}
  set() {}
  delete() {}
  clear() {}
}

export class WebStorageStore extends Store {
  /**
   * @param {string} storageKey
   * @param {Storage} storage
   * @param {object} [options]
   * @param {string} [options.rev=false] Clear storage if jsrev changes.
   */
  constructor(storageKey, storage, options = {}) {
    super();
    this._storageKey = storageKey;
    this._storage = storage;
    this._prefix = `totara:${storageKey}:${this._rootPath()}:`;
    if (options.rev) {
      this._checkRev();
    }
  }

  /**
   * Get the value associated with the provided key.
   *
   * @param {string} key
   * @returns {*}
   */
  get(key) {
    let val;
    val = this._storage.getItem(this._prefix + key);
    return val === null ? null : JSON.parse(val);
  }

  /**
   * Set the value associated with the provided key.
   *
   * @param {string} key
   * @param {*} value
   * @returns {boolean} success
   */
  set(key, value) {
    try {
      this._storage.setItem(this._prefix + key, JSON.stringify(value));
      return true;
    } catch (e) {
      // exception will be thrown when storage limit is reached
      return false;
    }
  }

  /**
   * Remove the value associated with the provided key from storage.
   *
   * @param {string} key
   */
  delete(key) {
    this._storage.removeItem(this._prefix + key);
  }

  /**
   * Clear all data managed by this store.
   */
  clear() {
    this._keys().forEach(key => this._storage.removeItem(key));
  }

  /**
   * Get path to use to separate data for sites in the same origin.
   *
   * @private
   */
  _rootPath() {
    // localStorage is per-origin so we can omit the domain
    const result = /^\w+:\/\/[^/]+\/?(.*)$/.exec(config.wwwroot);
    return result ? result[1] : config.wwwroot;
  }

  /**
   * Get all keys managed by this store.
   *
   * @private
   * @returns {string[]}
   */
  _keys() {
    const keys = [];
    for (let i = 0; i < this._storage.length; i++) {
      const key = this._storage.key(i);
      if (key.startsWith(this._prefix)) {
        keys.push(key);
      }
    }
    return keys;
  }

  /**
   * Check jsrev and clear managed data if not.
   *
   * @private
   */
  _checkRev() {
    const storageRev = this.get('__jsrev');
    if (storageRev != config.rev.js) {
      this.clear();
      this.set('__jsrev', config.rev.js);
    }
  }
}
