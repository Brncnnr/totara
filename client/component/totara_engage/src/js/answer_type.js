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

export default {
  /**
   * This should be a constant for answer type of multi choice
   */
  MULTI_CHOICE: 1,

  /**
   * This should be a constant for answer type of single choice
   */
  SINGLE_CHOICE: 2,

  /**
   *
   * @param {Number} value
   * @return {Boolean}
   */
  isValid(value) {
    return [this.MULTI_CHOICE, this.SINGLE_CHOICE].includes(value);
  },

  /**
   * Checking whether the value being passed in is a multi choice answer type or not.
   *
   * @param {Number} value
   * @return {Boolean}
   */
  isMultiChoice(value) {
    if (!this.isValid(value)) {
      return false;
    }

    return this.MULTI_CHOICE == value;
  },

  /**
   * Checking whether the given value is a single choice answer type or not.
   *
   * @param {Number} value
   * @return {Boolean}
   */
  isSingleChoice(value) {
    if (!this.isValid(value)) {
      return false;
    }

    return this.SINGLE_CHOICE == value;
  },
};
