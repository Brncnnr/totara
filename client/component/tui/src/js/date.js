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
 * @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @module tui
 */

import { langString } from './i18n';

/**
 * View date-fns docs for additional date helpers
 * https://date-fns.org/v2.13.0/docs/
 * The format methods would require additional lang support
 */
import {
  formatISO,
  getDate,
  getDaysInMonth,
  getMonth,
  getYear,
  isAfter,
  isBefore,
  isExists,
  parseISO,
} from 'date-fns';

export const DEFAULT_YEAR_RANGE_OFFSET = 50;

export { isExists, parseISO } from 'date-fns';

/**
 * Get days of month array for select
 *
 * @param {number} month
 * @param {number} year
 * @returns {array}
 */
export function getDaysInMonthSelectArray(month, year) {
  let daysInMonth = month ? getDaysInMonth(new Date(year || 0, month)) : 31;
  let days = [];

  for (let i = 1; i <= daysInMonth; i++) {
    days.push({
      id: i,
      label: i,
    });
  }

  return days;
}

/**
 * Return array of month strings.
 *
 * @returns {array}
 */
export function getMonthStringsSelectArray() {
  const months = [
    {
      id: 0,
      label: langString('month_january', 'totara_core'),
      labelShort: langString('month_january_short', 'totara_core'),
    },
    {
      id: 1,
      label: langString('month_february', 'totara_core'),
      labelShort: langString('month_february_short', 'totara_core'),
    },
    {
      id: 2,
      label: langString('month_march', 'totara_core'),
      labelShort: langString('month_march_short', 'totara_core'),
    },
    {
      id: 3,
      label: langString('month_april', 'totara_core'),
      labelShort: langString('month_april_short', 'totara_core'),
    },
    {
      id: 4,
      label: langString('month_may', 'totara_core'),
      labelShort: langString('month_may_short', 'totara_core'),
    },
    {
      id: 5,
      label: langString('month_june', 'totara_core'),
      labelShort: langString('month_june_short', 'totara_core'),
    },
    {
      id: 6,
      label: langString('month_july', 'totara_core'),
      labelShort: langString('month_july_short', 'totara_core'),
    },
    {
      id: 7,
      label: langString('month_august', 'totara_core'),
      labelShort: langString('month_august_short', 'totara_core'),
    },
    {
      id: 8,
      label: langString('month_september', 'totara_core'),
      labelShort: langString('month_september_short', 'totara_core'),
    },
    {
      id: 9,
      label: langString('month_october', 'totara_core'),
      labelShort: langString('month_october_short', 'totara_core'),
    },
    {
      id: 10,
      label: langString('month_november', 'totara_core'),
      labelShort: langString('month_november_short', 'totara_core'),
    },
    {
      id: 11,
      label: langString('month_december', 'totara_core'),
      labelShort: langString('month_december_short', 'totara_core'),
    },
  ];
  return months;
}

/**
 * Get array for select input range of year
 *
 * @param {number} year mid range year (defaults to current)
 * @param {number} yearsBefore=50 years before mid range (defaults to 50)
 * @param {number} yearsAfter=50 years after mid range (defaults to 50)
 * @returns {array}
 */
export function getYearsSelectArray(year, yearsBefore, yearsAfter) {
  let years = [];

  // Provides Defaults for year, years before & years after
  year = year || getYear(new Date());
  yearsAfter = Number.isInteger(yearsAfter)
    ? yearsAfter
    : DEFAULT_YEAR_RANGE_OFFSET;
  yearsBefore = Number.isInteger(yearsBefore)
    ? yearsBefore
    : DEFAULT_YEAR_RANGE_OFFSET;

  for (let i = year - yearsBefore; i <= year + yearsAfter; i++) {
    years.push({
      id: i,
      label: i,
    });
  }

  return years;
}

/**
 * Get array for select input range of year when using fixed start and/or end years.
 *
 * @param {?number} startYear Defaults to 50 years ago
 * @param {?number} endYear Defaults to 50 years in the future
 */
export function getFixedYearsSelectArray(startYear, endYear) {
  const currentYear = getYear(new Date());

  if (!startYear && startYear !== 0) {
    startYear = currentYear - DEFAULT_YEAR_RANGE_OFFSET;
  }

  if (!endYear && endYear !== 0) {
    endYear = currentYear + DEFAULT_YEAR_RANGE_OFFSET;
  }

  const years = [];
  for (let i = startYear; i <= endYear; i++) {
    years.push({
      id: i,
      label: i,
    });
  }

  return years;
}

/**
 * Get current date values
 *
 * @returns {object}
 */
export function getCurrentDateValues() {
  let date = new Date();

  return {
    day: getDate(date),
    month: getMonth(date),
    year: getYear(date),
  };
}

/**
 * Get date values from ISO
 *
 * @param {iso} iso
 * @returns {object}
 */
export function getValuesFromIso(iso) {
  let date = parseISO(iso);

  // Check if date is valid
  if (!(date instanceof Date) || isNaN(date)) {
    return;
  }

  return {
    day: getDate(date),
    month: getMonth(date),
    year: getYear(date),
  };
}

/**
 * Get ISO 8601 standard string
 *
 * @param {object} data {day: Int, month: Int, year: Int}
 * @param {boolean} dateOnly only return date ISO
 * @returns {string}
 */
export function getIsoFromValues(data, dateOnly) {
  if (!isExists(data.year, +data.month, data.day)) {
    return false;
  }

  let date = new Date(data.year, +data.month, data.day);
  return formatISO(date, { representation: dateOnly ? 'date' : 'complete' });
}

/**
 * Get user local time zone key
 * Not supported in IE
 *
 * @returns {string}
 */
export function getLocalTimeZone() {
  return Intl.DateTimeFormat().resolvedOptions().timeZone;
}

/**
 * Check if a ISO is after another
 *
 * @param {iso} date provided date
 * @param {iso} baseDate the date to compare against
 * @returns {boolean}
 */
export function isIsoAfter(date, baseDate) {
  // If date values not passed or are equal
  if (!date || !baseDate || date === baseDate) {
    return true;
  }

  return isAfter(parseISO(date), parseISO(baseDate));
}

/**
 * Check a ISO is before another
 *
 * @param {iso} date provided date
 * @param {iso} baseDate the date to compare against
 * @returns {boolean}
 */
export function isIsoBefore(date, baseDate) {
  // If date values not passed or are equal
  if (!date || !baseDate || date === baseDate) {
    return true;
  }

  return isBefore(parseISO(date), parseISO(baseDate));
}

/**
 * Break down of symbols used by php strftime.
 * Not to be confused with the formats used by DateTime/DateTimeImmutable etc.
 * @see https://www.php.net/manual/en/function.strftime.php
 */
const strftimeYearSymbols = ['%y', '%Y'];
const strftimeMonthSymbols = ['%m', '%h', '%b', '%B'];
const strftimeDaySymbols = ['%d', '%a', '%A', '%e'];

/**
 * Get date order from strftime-style format string.
 *
 * @param {string} format
 * @returns {('y'|'m'|'d')[]} Array of y, m, and d
 */
export function getDateOrderFromStrftime(format) {
  const formatParts = [
    strftimeYearSymbols,
    strftimeMonthSymbols,
    strftimeDaySymbols,
  ];
  const [yearPosition, monthPosition, dayPosition] = formatParts.map(
    partSymbols => {
      const symbolPositions = partSymbols.map(symbol => format.indexOf(symbol));

      const foundSymbolPositions = symbolPositions.filter(
        position => position !== -1
      ); // Filter missing symbols.

      if (foundSymbolPositions.length === 0) {
        return -1;
      }

      return Math.min(...foundSymbolPositions); // Return the position of the first symbol found.
    }
  );

  const anyMissingParts = [yearPosition, monthPosition, dayPosition].some(
    position => position === -1
  );
  if (anyMissingParts) {
    return ['y', 'm', 'd']; // Default order.
  }

  const order = [];
  order[yearPosition] = 'y';
  order[monthPosition] = 'm';
  order[dayPosition] = 'd';

  return order.filter(Boolean); // Normalize the indexes (remove nulls).
}
