/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @module mod_contentmarketplace
 */

/**
 * Completion tracking disabled constant
 * @type {String}
 */
export const COMPLETION_TRACKING_NONE = 'tracking_none';

/**
 * Self completion constant
 * @type {String}
 */
export const COMPLETION_TRACKING_MANUAL = 'tracking_manual';

/**
 * Unknown completion status (i.e. not started)
 * @type {String}
 */
export const COMPLETION_STATUS_UNKNOWN = 'unknown';

/**
 * In progress completion status
 * @type {String}
 */
export const COMPLETION_STATUS_INCOMPLETE = 'incomplete';

/**
 * Completion progress comes from content marketplace
 * @type {String}
 */
export const COMPLETION_CONDITION_CONTENT_MARKETPLACE = 'CONTENT_MARKETPLACE';
