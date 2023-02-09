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

import pending from './pending';

/**
 * Announce a message to screen readers using an aria-live area.
 *
 * @param {object} options
 * @param {string} options.message
 * @param {'polite'|'assertive'} [options.type=polite]
 */
export function announce({ type = 'polite', message }) {
  if (process.env.NODE_ENV === 'development') {
    console.log(`screen reader (${type}): ${message}`);
  }
  getAnnounceRegion(type).then(region => {
    region.textContent = message;
  });
}

const typeRegions = {};

function getAnnounceRegion(type) {
  if (typeRegions[type]) return typeRegions[type];
  const region = document.createElement('div');
  typeRegions[type] = new Promise(r => {
    const done = pending();
    setTimeout(() => {
      done();
      r(region);
    }, 0);
  });
  region.className = 'sr-only';
  region.setAttribute('role', 'status');
  region.setAttribute('aria-live', 'polite');

  document.body.appendChild(region);
  return typeRegions[type];
}
