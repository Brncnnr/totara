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

export const PUBLIC = 'PUBLIC';
export const PRIVATE = 'PRIVATE';
export const HIDDEN = 'HIDDEN';

/**
 *
 * @param {String} value
 * @return {Boolean}
 */
export function isPublic(value) {
  return value === PUBLIC;
}

/**
 *
 * @param {String} value
 * @return {Boolean}
 */
export function isPrivate(value) {
  return value === PRIVATE;
}

/**
 *
 * @param {String} value
 * @return {boolean}
 */
export function isHidden(value) {
  return value === HIDDEN;
}
