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

export const WebStorageStore = jest.fn(function(storageKey) {
  const data = new Map();

  this.__storageKey = storageKey;
  this.get = jest.fn(key => (data.has(key) ? JSON.parse(data.get(key)) : null));
  this.set = jest.fn((key, value) => data.set(key, JSON.stringify(value)));
  this.delete = jest.fn(key => data.delete(key));
  this.clear = jest.fn(() => data.clear());
  this.methodMockClear = () => {
    this.get.mockClear();
    this.set.mockClear();
    this.delete.mockClear();
    this.clear.mockClear();
  };
});
