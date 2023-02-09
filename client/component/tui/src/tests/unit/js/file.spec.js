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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @module tui
 */
import { getReadableSize } from 'tui/file';
import * as i18n from 'tui/i18n';

i18n.__setString('sizegb', 'core', 'GB');
i18n.__setString('sizemb', 'core', 'MB');
i18n.__setString('sizekb', 'core', 'KB');
i18n.__setString('sizeb', 'core', 'Byte');
i18n.__setString('filesize', 'totara_core', '{$a->size} {$a->unit}');

describe('getReadableSize', () => {
  it('gives GB size', async () => {
    // This is 1 GB
    expect(await getReadableSize(1073741824)).toEqual('1 GB');

    // This is 2 GB
    expect(await getReadableSize(2147483648)).toEqual('2 GB');

    // This is 10 GB
    expect(await getReadableSize(10737418240)).toEqual('10 GB');
  });

  it('gives MB size', async () => {
    // This is 1 MB
    expect(await getReadableSize(1048576)).toEqual('1 MB');

    // This is 2 MB
    expect(await getReadableSize(2097152)).toEqual('2 MB');
  });

  it('gives KB size', async () => {
    // This is 1 KB
    expect(await getReadableSize(1024)).toEqual('1 KB');

    // This is 2 KB
    expect(await getReadableSize(2048)).toEqual('2 KB');
  });

  it('gives Byte size', async () => {
    // This is 100 Bytes
    expect(await getReadableSize(100)).toEqual('100 Byte');

    // This is 102 Bytes
    expect(await getReadableSize(102)).toEqual('102 Byte');
  });
});
