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
 * @module container_workspace
 */

export const cardGrid = {
  xs: {
    name: 'xs',
    boundaries: [0, 500],
    direction: 'horizontal',
    cardUnits: 12,
  },

  s: {
    name: 's',
    boundaries: [500, 992],
    direction: 'horizontal',
    cardUnits: 6,
  },

  m: {
    name: 'm',
    boundaries: [993, 1192],
    direction: 'horizontal',
    cardUnits: 3,
  },

  l: {
    name: 'l',
    boundaries: [1193, 1396],
    direction: 'horizontal',
    cardUnits: 2,
  },

  xl: {
    name: 'xl',
    boundaries: [1397, 1672],
    direction: 'horizontal',
    cardUnits: 2,
  },
};
