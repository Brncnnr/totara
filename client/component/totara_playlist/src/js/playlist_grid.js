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
 * @author Alvin Smith <alvin.smith@totaralearning.com>
 * @module totara_playlist
 */

export default {
  xsmall: {
    name: 'xsmall',
    boundaries: [0, 480],
    gridDirection: 'horizontal',
    cardDirection: 'vertical',
    maxItemsPerRow: 1,
    cardUnits: 11,
  },

  small: {
    name: 'small',
    boundaries: [481, 764],
    gridDirection: 'horizontal',
    cardDirection: 'vertical',
    maxItemsPerRow: 2,
    cardUnits: 5,
  },

  medium: {
    name: 'medium',
    boundaries: [765, 1192],
    gridDirection: 'horizontal',
    cardDirection: 'horizontal',
    maxItemsPerRow: 3,
    cardUnits: 3,
  },

  large: {
    name: 'large',
    boundaries: [1193, 1396],
    gridDirection: 'horizontal',
    cardDirection: 'horizontal',
    maxItemsPerRow: 4,
    cardUnits: 2,
  },

  xlarge: {
    name: 'xlarge',
    boundaries: [1397, 1672],
    gridDirection: 'horizontal',
    cardDirection: 'horizontal',
    maxItemsPerRow: 5,
    cardUnits: 2,
  },
};
