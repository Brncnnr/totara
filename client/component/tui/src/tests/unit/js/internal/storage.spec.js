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

import { WebStorageStore } from 'tui/storage';
import { config } from 'tui/config';

jest.unmock('tui/storage');

let mockData = [];
const storageMock = {
  getItem: jest.fn(key => {
    const entry = mockData.find(x => x.key == key);
    return entry ? entry.value : null;
  }),
  setItem: jest.fn((key, value) => {
    const entry = mockData.find(x => x.key == key);
    if (entry) {
      entry.value = value;
    } else {
      mockData.push({ key, value });
    }
  }),
  removeItem: jest.fn(key => {
    const index = mockData.findIndex(x => x.key == key);
    if (index !== -1) {
      mockData.splice(index, 1);
    }
  }),
  get length() {
    return mockData.length;
  },
  key(i) {
    return mockData[i] && mockData[i].key;
  },
  __keys() {
    return mockData.map(x => x.key);
  },
  __reset() {
    storageMock.getItem.mockClear();
    storageMock.setItem.mockClear();
    storageMock.removeItem.mockClear();
    mockData = [];
  },
};

describe('WebStorageStore', () => {
  let store;
  beforeEach(() => {
    storageMock.__reset();
    config.wwwroot = 'http://foo';
    config.rev.js = 1000;
    store = new WebStorageStore('store', storageMock);
  });

  it('allows setting, getting, and removing data', () => {
    expect(store.get('item')).toEqual(null);
    store.set('item', 'value');
    expect(store.get('item')).toEqual('value');
    store.delete('item');
    expect(store.get('item')).toEqual(null);
  });

  it('sets data in browser storage as JSON', () => {
    store.set('item', { name: 'bob' });
    expect(storageMock.setItem).toHaveBeenCalledWith(
      'totara:store::item',
      '{"name":"bob"}'
    );
    expect(store.get('item')).toEqual({ name: 'bob' });
    expect(storageMock.getItem).toHaveBeenCalledWith('totara:store::item');
    store.delete('item');
    expect(storageMock.removeItem).toHaveBeenCalledWith('totara:store::item');
  });

  it('stores data for different wwwroots under a different prefix', () => {
    store.set('item', 1);
    expect(storageMock.getItem('totara:store::item')).toBe('1');

    config.wwwroot = 'http://foo/bar';
    store = new WebStorageStore('store', storageMock);
    store.set('item', 2);

    expect(storageMock.getItem('totara:store::item')).toBe('1');
    expect(storageMock.getItem('totara:store:bar:item')).toBe('2');

    config.wwwroot = 'http://foo/baz';
    store = new WebStorageStore('store', storageMock);
    store.set('item', 3);

    expect(storageMock.getItem('totara:store::item')).toBe('1');
    expect(storageMock.getItem('totara:store:bar:item')).toBe('2');
    expect(storageMock.getItem('totara:store:baz:item')).toBe('3');
  });

  it('has option to clear relevant storage keys when jsrev changes', () => {
    config.wwwroot = 'http://foo/bar';
    store = new WebStorageStore('store', storageMock, { rev: true });
    expect(storageMock.getItem('totara:store:bar:__jsrev')).toBe('1000');
    store.set('item', 1);
    expect(storageMock.getItem('totara:store:bar:item')).toBe('1');

    config.wwwroot = 'http://foo';
    store = new WebStorageStore('store', storageMock, { rev: true });
    expect(storageMock.getItem('totara:store::__jsrev')).toBe('1000');
    store.set('item', 2);
    expect(storageMock.getItem('totara:store:bar:item')).toBe('1');
    expect(storageMock.getItem('totara:store::item')).toBe('2');

    // shouldn't get cleared at this point
    store = new WebStorageStore('store', storageMock, { rev: true });
    expect(storageMock.getItem('totara:store:bar:item')).toBe('1');
    expect(storageMock.getItem('totara:store::item')).toBe('2');

    // now change jsrev and make sure only that store gets cleared
    config.rev.js = 2000;

    store = new WebStorageStore('store', storageMock, { rev: true });
    expect(storageMock.getItem('totara:store::__jsrev')).toBe('2000');
    expect(storageMock.getItem('totara:store::item')).toBe(null);
    expect(storageMock.getItem('totara:store:bar:item')).toBe('1');
  });

  it('defaults path to full wwwroot if it cannot be parsed', () => {
    config.wwwroot = 'nonsense';
    store = new WebStorageStore('store', storageMock);
    store.set('item', 1);
    expect(storageMock.getItem('totara:store:nonsense:item')).toBe('1');
  });

  it('handles exception when writing', () => {
    store = new WebStorageStore('store', storageMock);
    expect(store.set('foo')).toBe(true);
    storageMock.setItem.mockImplementationOnce(() => {
      throw new Error();
    });
    expect(store.set('foo')).toBe(false);
  });
});
