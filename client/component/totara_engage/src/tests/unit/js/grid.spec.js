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
 * @module totara_engage
 */

import { calculateRow } from 'totara_engage/grid';

describe('Engage grid.js', () => {
  const items = [1, 2, 3, 4, 5, 6, 7, 8];

  it('Checks the row calculator with 2', () => {
    let result = calculateRow(items, 2);
    expect(result.length).toEqual(4);

    result.forEach(function(single) {
      expect(single.items.length).toEqual(2);
    });
  });

  it('Checks the row calculator with 3', () => {
    let result = calculateRow(items, 3);
    expect(result.length).toEqual(3);
    expect(result[0].items).toEqual([1, 2, 3]);
    expect(result[1].items).toEqual([4, 5, 6]);
    expect(result[2].items).toEqual([7, 8]);
  });

  it('Can handle zero per row', () => {
    let result = calculateRow(items, 0);
    expect(result).toEqual([]);
  });
});
