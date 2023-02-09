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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @module totara_engage
 */

/**
 * Collection of constants used to generate source links
 * to different engage pages.
 */
export default {
  /**
   * @param {int} id
   * @returns {string}
   */
  article(id) {
    return ['ea', id].join('.');
  },

  /**
   * @param {int} id
   * @returns {string}
   */
  workspace(id) {
    return ['ws', id, '1'].join('.');
  },

  /**
   * @param {int} id
   * @param {boolean} isLibraryView
   * @returns {string}
   */
  playlist(id, isLibraryView = false) {
    let parts = ['pl', id];
    if (isLibraryView) {
      parts.push('l');
    }

    return parts.join('.');
  },

  /**
   * All library pages appearing on the Library tab
   *
   * @param {int} subpage
   * @returns {string}
   */
  library(subpage) {
    return ['lb', subpage].join('.');
  },

  /**
   * @returns {string}
   */
  libraryYourResources() {
    return this.library(0);
  },

  /**
   * @returns {string}
   */
  libraryBookmarked() {
    return this.library(1);
  },

  /**
   * @returns {string}
   */
  libraryShared() {
    return this.library(2);
  },

  /**
   * @returns {string}
   */
  libraryOwnersResources() {
    return this.library(3);
  },

  /**
   * @returns {string}
   */
  librarySearchResults() {
    return this.library(4);
  },
};
