/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Jack Humphrey <jack.humphrey@totaralearning.com>
 * @module totara_api
 */

/** @enum {string} */
export const Units = {
  WEEKS: 'WEEKS',
  DAYS: 'DAYS',
  HOURS: 'HOURS',
  MINUTES: 'MINUTES',
  SECONDS: 'SECONDS',
};

export const unitsInSeconds = [
  { id: Units.WEEKS, seconds: 604800 },
  { id: Units.DAYS, seconds: 86400 },
  { id: Units.HOURS, seconds: 3600 },
  { id: Units.MINUTES, seconds: 60 },
  { id: Units.SECONDS, seconds: 1 },
];

/**
 * Finds suitable units for given duration
 * Returns object containing { value, units }
 * @param {number} seconds
 * @returns {object}
 */
export function parseSeconds(seconds) {
  let value, units;
  for (let i = 0; i < unitsInSeconds.length; i++) {
    if (seconds % unitsInSeconds[i].seconds === 0) {
      value = seconds / unitsInSeconds[i].seconds;
      units = unitsInSeconds[i].id;
      return { value, units };
    }
  }
}
