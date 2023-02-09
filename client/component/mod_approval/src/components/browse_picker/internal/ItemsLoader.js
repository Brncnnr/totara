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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module mod_approval
 */

/**
 * Items loader
 */
export default class ItemsLoader {
  constructor({ getItems, onUpdate, onError, valueKey }) {
    this._getItems = getItems;
    this._onUpdate = onUpdate;
    this._onError = onError;
    this._valueKey = valueKey;
    this._variables = null;
    this._currentRequest = null;
    this._pending = false;
    this._result = null;
    this.state = {
      value: null,
      loading: false,
      loadingMore: false,
      hasMore: false,
    };
  }

  fetch() {
    if (this._currentRequest) {
      this._pending = true;
    } else {
      this.state.loading = true;
      this._currentRequest = Promise.resolve(this._getItems(this._variables));
      this._currentRequest.then(
        x => this._handleSuccess(x),
        x => this._handleError(x)
      );
    }
  }

  loadMore() {
    if (this._currentRequest) {
      // if we're loading something else, just do that
      return;
    }

    if (!this._result.next_cursor) {
      // nothing more to fetch
      return;
    }

    this.state.loadingMore = true;
    this._currentRequest = Promise.resolve(
      this._getItems(
        Object.assign({}, this._variables, { cursor: this._result.next_cursor })
      )
    );
    this._currentRequest.then(
      x => this._handleSuccess(x, true),
      x => this._handleError(x)
    );
  }

  setVariables(variables) {
    if (JSON.stringify(this._variables) === JSON.stringify(variables)) {
      return;
    }
    this._variables = variables;
    this.fetch();
  }

  _handleDone() {
    this._currentRequest = null;
    if (this.state.loading) {
      this.state.loading = false;
    }
    if (this.state.loadingMore) {
      this.state.loadingMore = false;
    }
    if (this._pending) {
      this._pending = false;
      this.fetch();
    }
  }

  _handleSuccess(result, append) {
    this._handleDone();
    this._result = result;
    const value = this._valueKey ? result && result[this._valueKey] : result;
    if (append) {
      this.state.value = (this.state.value || []).concat(value);
    } else {
      this.state.value = value;
    }
    this.state.hasMore = Boolean(result.next_cursor);
    if (this._onUpdate) {
      this._onUpdate(this.state);
    }
  }

  _handleError(e) {
    this._handleDone();
    if (this._onError) {
      this._onError(e);
    }
  }
}
