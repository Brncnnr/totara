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
   * @var string
   */
  PUBLIC: 'PUBLIC',

  /**
   * @var string
   */
  PRIVATE: 'PRIVATE',

  /**
   * @var string
   */
  RESTRICTED: 'RESTRICTED',

  /**
   *
   * @param {String} value
   * @return {boolean}
   */
  isPublic(value) {
    return this.PUBLIC === value;
  },

  /**
   *
   * @param {String} value
   * @return {boolean}
   */
  isPrivate(value) {
    return this.PRIVATE === value;
  },

  /**
   *
   * @param {String} value
   * @return {boolean}
   */
  isRestricted(value) {
    return this.RESTRICTED === value;
  },

  /**
   * Checking whether the value of access is something that machine can understand.
   *
   * @param {String} value
   * @return {boolean}
   */
  isValid(value) {
    return [this.PUBLIC, this.PRIVATE, this.RESTRICTED].includes(value);
  },
};
