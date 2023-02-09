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

import pending from 'tui/pending';

jest.unmock('tui/pending');

describe('pending', () => {
  it('stores pending items in a global array', async () => {
    expect(global.testbridge.pending).toEqual([]);
    const done = pending('key1');
    expect(global.testbridge.pending).toEqual(['key1']);
    done();
    expect(global.testbridge.pending).toEqual([]);
  });

  it('handles multiple pending items with the same key', () => {
    const done1 = pending('key1');
    const done2 = pending('key1');
    const done3 = pending('key1');
    expect(global.testbridge.pending).toEqual(['key1', 'key1', 'key1']);
    done1();
    done2();
    expect(global.testbridge.pending).toEqual(['key1']);
    done3();
    expect(global.testbridge.pending).toEqual([]);
  });

  it('handles done being called multiple times', () => {
    const done1 = pending('key1');
    const done2 = pending('key1');
    expect(global.testbridge.pending).toEqual(['key1', 'key1']);
    done1();
    done1();
    done1();
    done1();
    expect(global.testbridge.pending).toEqual(['key1']);
    done2();
    expect(global.testbridge.pending).toEqual([]);
  });

  it('defaults to "pending" key', () => {
    const done = pending();
    expect(global.testbridge.pending).toEqual(['pending']);
    done();
  });
});
