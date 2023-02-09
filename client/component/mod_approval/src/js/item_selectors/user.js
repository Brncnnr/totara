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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @module mod_approval
 */

import { totaraUrl } from 'tui/util';

/**
 * Get the user's profile URL.
 *
 * @param {object} user core_user fullname is required, id and card_display.profile_url are optional
 * @returns {string}
 */
export function getProfileUrl(user) {
  let url = (user.card_display || {}).profile_url || '#';
  // Fall back to hard-coded url if profile_url is unavailable.
  if (url == '#' && typeof user.id !== 'undefined') {
    url = totaraUrl('/user/profile.php', { id: user.id });
  }
  return url;
}

/**
 * Get the HTML of an <a> tag linking to the user's profile URL.
 *
 * @param {object} user core_user fullname is required, id and card_display.profile_url are optional
 * @returns {string}
 */
export function getProfileAnchor(user) {
  const el = document.createElement('a');
  el.href = getProfileUrl(user);
  el.textContent = user.fullname;
  return el.outerHTML;
}

/**
 * Validate the user type for user profile
 * @param {object} user core_user id and fullname are required
 */
export function validateForProfile(user) {
  if (!(Number(user.id) > 0)) {
    return false;
  }
  if (!(user.fullname || '')) {
    return false;
  }
  const cardHasProfileImage =
    typeof user.card_display === 'object' &&
    'profile_picture_url' in user.card_display;
  const userHasProfileImageUrl =
    'profileimageurl' in user || 'profileimageurlsmall' in user;
  if (!cardHasProfileImage && !userHasProfileImageUrl) {
    return false;
  }
  return true;
}
