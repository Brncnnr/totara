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

/**
 * This file contains code for working with 2D geometry and 2D math, for example
 * in positioning calculations.
 */

/**
 * Represents an x and y coordinate pair in two-dimensional space.
 */
export class Point {
  constructor(x, y) {
    this.x = x;
    this.y = y;
  }

  /**
   * Adds a Point to the location of this Point and returns a new Point.
   *
   * @param {Point} point
   * @returns {Point}
   */
  add(point) {
    return new Point(this.x + point.x, this.y + point.y);
  }

  /**
   * Subtracts a Point from the location of this Point and returns a new Point.
   *
   * @param {Point} point
   * @returns {Point}
   */
  sub(point) {
    return new Point(this.x - point.x, this.y - point.y);
  }
}

/**
 * Stores a width and height pair.
 */
export class Size {
  constructor(width, height) {
    this.width = width;
    this.height = height;
  }
}

/**
 * Describes the width, height, and location of a rectangle.
 */
export class Rect {
  constructor(x, y, width, height) {
    this.left = x;
    this.top = y;
    this.right = x + width;
    this.bottom = y + height;
    this.width = width;
    this.height = height;
  }

  static fromPositions({ left, top, right, bottom }) {
    return new Rect(left, top, right - left, bottom - top);
  }

  getPosition() {
    return new Point(this.left, this.top);
  }

  getSize() {
    return new Size(this.width, this.height);
  }

  /**
   * Adds a point to the location of this Rect and returns a new Rect.
   *
   * @param {Point} point
   * @returns {Rect}
   */
  add(point) {
    return new Rect(
      this.left + point.x,
      this.top + point.y,
      this.width,
      this.height
    );
  }

  /**
   * Subtracts a point from the location of this Rect and returns a new Rect.
   *
   * @param {Point} point
   * @returns {Rect}
   */
  sub(point) {
    return new Rect(
      this.left - point.x,
      this.top - point.y,
      this.width,
      this.height
    );
  }

  /**
   * Check if this Rect contains the provided point.
   *
   * @param {Point} point
   * @returns {boolean}
   */
  contains(point) {
    return (
      point.x >= this.left &&
      point.x <= this.right &&
      point.y >= this.top &&
      point.y <= this.bottom
    );
  }
}
