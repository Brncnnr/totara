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
import { cacheGet, cacheSet, cacheDelete } from 'tui/internal/persistent_cache';
import { config } from 'tui/config';

const storageInstance = WebStorageStore.mock.instances.find(
  x => x.__storageKey == 'cache'
);

describe('persistent_cache', () => {
  beforeEach(() => {
    storageInstance.clear();
    storageInstance.methodMockClear();
    cacheGet.__resetInternalCache();
    config.rev.js = 1000;
  });

  it('allows storing items in the cache', () => {
    expect(cacheGet('key')).toBe(null);
    const item = { name: 'bob' };
    cacheSet('key', item);
    expect(cacheGet('key')).toBe(item);
    cacheDelete('key');
    expect(cacheGet('key')).toBe(null);
  });

  it('writes stored data to localstorage', () => {
    expect(cacheGet('key')).toBe(null);
    expect(storageInstance.get).toHaveBeenCalledWith('key');
    expect(storageInstance.get).toHaveBeenCalledTimes(1);
    const item = { name: 'bob' };
    cacheSet('key', item);
    expect(cacheGet('key')).toBe(item);
    expect(storageInstance.set).toHaveBeenCalledWith('key', item);
    expect(storageInstance.get).toHaveBeenCalledTimes(1);
    expect(storageInstance.get('key')).toEqual(item);
    cacheDelete('key');
    expect(storageInstance.delete).toHaveBeenCalledWith('key');
    expect(cacheGet('key')).toBe(null);
  });

  it('skips writing to localstorage if caching is disabled', () => {
    config.rev.js = -1;
    expect(cacheGet('key')).toBe(null);
    expect(storageInstance.get).not.toHaveBeenCalled();
    const item = { name: 'bob' };
    cacheSet('key', item);
    expect(cacheGet('key')).toBe(item);
    expect(storageInstance.set).not.toHaveBeenCalled();
    expect(storageInstance.get).not.toHaveBeenCalled();
    cacheDelete('key');
    expect(storageInstance.delete).not.toHaveBeenCalled();
    expect(cacheGet('key')).toBe(null);
  });
});
