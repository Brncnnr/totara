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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module tui
 */

const started = {};
const completed = {};

export default function pending(key) {
  if (!started[key]) started[key] = 0;
  started[key]++;
  let called = false;
  return () => {
    if (called) return;
    called = true;
    if (!completed[key]) completed[key] = 0;
    completed[key]++;
  };
}

const getVal = (obj, key) => obj[key] || 0;
const getTotal = obj => Object.values(obj).reduce((acc, cur) => acc + cur, 0);

pending.__started = key => (key ? getVal(started, key) : getTotal(started));
pending.__completed = key =>
  key ? getVal(completed, key) : getTotal(completed);
pending.__outstanding = key =>
  pending.__started(key) - pending.__completed(key);
