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

import pending from '../pending';

const STATE_IDLE = 0;
const STATE_WAITING = 1;
const STATE_EXECUTING = 2;

/**
 * Queue requests to be loaded.
 *
 * Pass the "serial" flag to only execute one batch at a time
 * (next batch must wait until the previous has completed).
 *
 * @internal
 */
export default class BatchingQueue {
  /**
   * @param {object} options
   * @param {(queue: any[]) => Promise} options.handler Load items.
   * @param {number} [options.wait=0]
   * @param {function} [options.equals]
   * @param {boolean} [options.serial] Wait until the previous batch has
   *   completed before executing the next.
   */
  constructor({ handler, wait = 0, equals, serial = false }) {
    this._handler = handler;
    this._wait = wait;
    this._equals = equals || ((a, b) => a === b);
    this._serial = serial;

    // requests in queue
    this._queuedRequests = [];
    // callbacks to call when queue loaded
    this._queuedCallbacks = [];

    this.state = STATE_IDLE;
  }

  /**
   * Add an array of requests to the queue.
   *
   * @param {array} requests
   * @returns {Promise}
   */
  enqueueMany(requests) {
    if (!Array.isArray(requests)) {
      throw new TypeError('requests');
    }
    return new Promise((resolve, reject) => {
      requests.forEach(x => {
        if (!this.contains(x)) {
          this._queuedRequests.push(x);
        }
      });

      this._queuedCallbacks.push([resolve, reject]);

      this._checkRunner();
    });
  }

  enqueue(request) {
    return this.enqueueMany([request]);
  }

  /**
   * Check if item is in queue.
   *
   * @param {*} item
   * @returns {boolean}
   */
  contains(item) {
    return this._queuedRequests.some(x => this._equals(x, item));
  }

  /**
   * @returns {number}
   */
  get size() {
    return this._queuedRequests.length;
  }

  /**
   * Check to see if timeout for runner should be started.
   */
  _checkRunner() {
    if (this.state == STATE_IDLE && this._queuedRequests.length > 0) {
      this.state = STATE_WAITING;
      const done = pending();
      setTimeout(() => {
        done();
        this._runQueue();
      }, this._wait);
    }
  }

  /**
   * Drain and process queue.
   */
  _runQueue() {
    if (this._serial) {
      this.state = STATE_EXECUTING;
    } else {
      this.state = STATE_IDLE;
    }
    const queue = this._queuedRequests;
    this._queuedRequests = [];
    const callbacks = this._queuedCallbacks;
    this._queuedCallbacks = [];

    this._executeHandler(queue)
      .then(
        r => callbacks.forEach(x => x[0](r)),
        r => callbacks.forEach(x => x[1](r))
      )
      .then(() => {
        if (this._serial) {
          this.state = STATE_IDLE;
        }
        this._checkRunner();
      });
  }

  /**
   * Execute handler with queued items.
   *
   * Handler will return a promise that resolves when the data has been loaded.
   *
   * Exactly how this happens is up to the handler, but all waiting requests will recieve the same data.
   *
   * @param {array} queue
   * @returns {Promise}
   */
  _executeHandler(queue) {
    // Use a try-catch here rather than `new Promise` so that handler is called immediately.
    // (makes unit tests simpler)
    try {
      return Promise.resolve(this._handler(queue));
    } catch (e) {
      return Promise.reject(e);
    }
  }
}
