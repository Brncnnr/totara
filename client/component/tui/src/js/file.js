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
 * @module totara_core
 */

import { langString, loadLangStrings } from 'tui/i18n';

/**
 * Given the size as the Bytes number, this function will try to
 * convert it to a human readable string.
 *
 * @param {Number|String} size
 * @return {Promise<string>}
 */
async function getReadableSize(size) {
  size = parseInt(size);

  if (size === -1) {
    const str = langString('unlimited', 'core');
    await loadLangStrings([str]);

    return str.toString();
  }

  let unitSize, unitStr;

  if (size >= 1073741824) {
    unitSize = Math.round((size / 1073741824) * 10) / 10;
    unitStr = langString('sizegb', 'core');
  } else if (size >= 1048576) {
    unitSize = Math.round((size / 1048576) * 10) / 10;
    unitStr = langString('sizemb', 'core');
  } else if (size >= 1024) {
    unitSize = Math.round((size / 1024) * 10) / 10;
    unitStr = langString('sizekb', 'core');
  } else {
    unitSize = size;
    unitStr = langString('sizeb', 'core');
  }

  await loadLangStrings([unitStr]);
  const params = {
    size: unitSize,
    unit: unitStr.toString(),
  };

  let result = langString('filesize', 'totara_core', params);
  await loadLangStrings([result]);

  return result.toString();
}

getReadableSize.langStrings = [
  langString('sizegb', 'core'),
  langString('sizemb', 'core'),
  langString('sizekb', 'core'),
  langString('sizeb', 'core'),
  langString('filesize', 'totara_core'),
  langString('unlimited', 'core'),
];

export { getReadableSize };
