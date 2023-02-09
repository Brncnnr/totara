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
 * @module tui
 */

/**
 * Create a square graphic image filled with the specified colour.
 * @param {string} colour the colour as #rrggbb or #rgb
 * @param {number=} size the width and the height of the image, default to 512.
 * @returns {string}
 */
export function createSquareImage(colour, size) {
  if (typeof size === 'undefined') {
    size = 512;
  }
  return (
    'data:image/svg+xml,' +
    encodeURIComponent(
      `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${size} ${size}">` +
        `<path fill="${colour}" d="M0 0h${size}v${size}H0z"/>` +
        '</svg>'
    )
  );
}

/**
 * Create a placeholder avatar image.
 * @param {string} colour the colour as #rrggbb or #rgb
 * @param {number=} size the width and the height of the image, default to 512.
 * @returns {string}
 */
export function createSilhouetteImage(colour, size) {
  if (typeof size === 'undefined') {
    size = 512;
  }
  const ellipse = (cx, cy, rx, ry) =>
    `<ellipse cx="${cx}" cy="${cy}" rx="${rx}" ry="${ry}" fill="${colour}"/>`;
  return (
    'data:image/svg+xml,' +
    encodeURIComponent(
      `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${size} ${size}">` +
        `<path fill="${colour}" opacity="0.25" d="M0 0h${size}v${size}H0z"/>` +
        ellipse(size / 2, size, size * 0.4, size / 4) +
        ellipse(size / 2, size / 2, size / 4, size * 0.3) +
        '</svg>'
    )
  );
}
