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
 * @author Arshad Anwer <arshad.anwer@totaralearning.com>
 * @module tui
 */

const DIRECTION_TYPE = {
  TOP_SCROLL: 'TOP_SCROLL',
  DOWN_SCROLL: 'DOWN_SCROLL',
};

const CALC_TYPE = {
  INIT: 'INIT',
  FIXED: 'FIXED',
  DYNAMIC: 'DYNAMIC',
};

const START_LEADING_BUFFER = 5;

/**
 * Utility class to handle virtual scrolling
 */
export default class VirtualUtils {
  constructor(options, updateCallBack) {
    this._initialize(options, updateCallBack);
  }

  /**
   * @param {Object} options
   * @param {Number} options.keeps threshhold to how many elements to be rendered at a any given time
   * @param {Number} options.buffer to be substracted to estimate starting point
   * @param {Array} options.uniqueIds of data to calculate length
   * @param {String} options.pageMode indicates if scroll method is on whole page or on specific container
   * @param {Function} updateCallBack
   */
  _initialize(options, updateCallBack) {
    // param data
    this._options = options;
    this._updateCallBack = updateCallBack;
    // size data
    this._sizes = new Map();
    this._firstRangeTotalSize = 0;
    this._firstRangeAverageSize = 0;
    this._wrapperOffsetWidth = 0;
    this._lastCalcIndex = 0;
    this._fixedSizeValue = 0;
    this._calcType = CALC_TYPE.INIT;
    // scroll data
    this._offset = 0;
    this._direction = '';
    // range data
    this._range = {};
    this._estimatedDocumentPosition = 0;

    if (options) {
      this._checkRange(0, options.keeps - 1);
    }
  }

  /**
   *  reset internal objects and variables
   */
  destroy() {
    this._initialize(null, null);
  }

  /**
   * Get current range values
   * @returns {Object} range
   */
  getRange() {
    const range = {};
    range.start = this._range.start;
    range.end = this._range.end;
    range.padFront = this._range.padFront;
    range.padBehind = this._range.padBehind;
    range.uniqueIds = this._options.uniqueIds;
    return range;
  }

  /**
   * Calculate estimated starting index based on scroll
   */
  _getViewportStartIndex() {
    const offset =
      this._offset -
      (this._options.pageMode && this._estimatedDocumentPosition);
    if (offset <= 0) {
      return 0;
    }

    if (this._isFixedType()) {
      return Math.floor(offset / this._fixedSizeValue);
    }

    let low = 0;
    let middle = 0;
    let middleOffset = 0;
    let high = this._options.uniqueIds.length;

    while (low <= high) {
      middle = low + Math.floor((high - low) / 2);
      middleOffset = this._getIndexOffset(middle);

      if (middleOffset === offset) {
        return middle;
      } else if (middleOffset < offset) {
        low = middle + 1;
      } else if (middleOffset > offset) {
        high = middle - 1;
      }
    }

    return low > 0 ? --low : 0;
  }

  /**
   * @param {Number} givenIndex
   * @returns {Number} offset
   */
  _getIndexOffset(givenIndex) {
    if (!givenIndex) {
      return 0;
    }

    let offset = 0;
    let indexSize = 0;
    for (let index = 0; index < givenIndex; index++) {
      indexSize = this._sizes.get(this._options.uniqueIds[index]);
      offset = offset + (indexSize || this.getEstimateSize());
    }
    // store last calculated index
    this._lastCalcIndex = Math.max(this._lastCalcIndex, givenIndex - 1);
    this._lastCalcIndex = Math.min(
      this._lastCalcIndex,
      this._getUniqueIdsLastIndex()
    );

    return offset;
  }

  isDownward() {
    return this._direction === DIRECTION_TYPE.DOWN_SCROLL;
  }

  isForward() {
    return this._direction === DIRECTION_TYPE.TOP_SCROLL;
  }

  /**
   * @param {*} key
   * @param {*} value data/footer offset height size
   */
  updateParam(key, value) {
    if (this._options && key in this._options) {
      this._options[key] = value;
    }
  }

  /**
   * @param {Id} id of the element
   * @param {Number} size of the element
   * store element size
   */
  saveSize(id, size, rootElPosition) {
    this._estimatedDocumentPosition = rootElPosition;
    this._sizes.set(id, size);
    if (this._calcType === CALC_TYPE.INIT) {
      this._fixedSizeValue = size;
      this._calcType = CALC_TYPE.FIXED;
    } else if (
      this._calcType === CALC_TYPE.FIXED &&
      this._fixedSizeValue !== size
    ) {
      this._calcType = CALC_TYPE.DYNAMIC;
      delete this._fixedSizeValue;
    }

    // calculate the average size only in the first range
    if (this._sizes.size <= this._options.keeps) {
      this._firstRangeTotalSize = this._firstRangeTotalSize + size;
      this._firstRangeAverageSize = Math.round(
        this._firstRangeTotalSize / this._sizes.size
      );
    } else {
      delete this._firstRangeTotalSize;
    }
  }

  /**
   * render next range including buffer depending on the current scroll direction (up/down)
   */
  dataListChangeEvent() {
    let start = this._range.start;
    if (this.isForward()) {
      start = start - START_LEADING_BUFFER;
    } else if (this.isDownward()) {
      start = start + START_LEADING_BUFFER;
    }
    start = Math.max(start, 0);
    this._updateRange(this._range.start, this._getEndByStart(start));
  }

  /**
   * Re-calculate starting point of index and update range
   */
  footerSizeChangeEvent() {
    this.dataListChangeEvent();
  }

  /**
   * @param {Number} offset of current scrolling position
   */
  handleScroll(offset) {
    this._direction =
      offset < this._offset
        ? DIRECTION_TYPE.TOP_SCROLL
        : DIRECTION_TYPE.DOWN_SCROLL;
    this._offset = offset;
    if (this._direction === DIRECTION_TYPE.TOP_SCROLL) {
      this._handleTopScroll();
    } else if (this._direction === DIRECTION_TYPE.DOWN_SCROLL) {
      this._handleDownScroll();
    }
  }

  /**
   * check if type is fixed
   * @returns {Boolean} CalculationType
   */
  _isFixedType() {
    return this._calcType === CALC_TYPE.FIXED;
  }

  /**
   * total top padding
   * @return {TotalPadTop}
   */
  _getPadTop() {
    if (this._isFixedType()) {
      return this._fixedSizeValue * this._range.start;
    } else {
      return this._getIndexOffset(this._range.start);
    }
  }

  /**
   * Validate range on top scroll
   */
  _handleTopScroll() {
    const overs = this._getViewportStartIndex();
    // should not change range if start doesn't exceed overs
    if (overs > this._range.start) {
      return;
    }
    // move up start by a buffer length, and make sure its valid
    const start = Math.max(overs - this._options.buffer, 0);
    this._checkRange(start, this._getEndByStart(start));
  }

  /**
   * Validate range on down scroll
   */
  _handleDownScroll() {
    const overs = this._getViewportStartIndex();
    // range stays the same if within buffer value
    if (overs < this._range.start + this._options.buffer) {
      return;
    }
    this._checkRange(overs, this._getEndByStart(overs));
  }

  /**
   * total bottom padding
   * @return {Number} TotalPadBottom
   */
  _getPadDown() {
    const end = this._range.end;
    const lastIndex = this._getUniqueIdsLastIndex();
    if (this._isFixedType()) {
      return (lastIndex - end) * this._fixedSizeValue;
    }

    // if lasCalcIndex is equals then calculate the relevant padding \
    if (this._lastCalcIndex === lastIndex) {
      return this._getIndexOffset(lastIndex) - this._getIndexOffset(end);
    } else {
      // if not, use a estimated value
      return (lastIndex - end) * this.getEstimateSize();
    }
  }

  /**
   * set the start and end point of the index
   *
   * @param {Number} start index of the data array
   * @param {Number} end index of the data array
   */
  _checkRange(start, end) {
    const keeps = this._options.keeps;
    const total = this._options.uniqueIds.length;

    // data size is less than keeps, render all
    if (total <= keeps) {
      start = 0;
      end = this._getUniqueIdsLastIndex();
    } else if (end - start < keeps - 1) {
      // if current range is more than the allowed keeps then assign start to previous end
      start = end - keeps + 1;
    }
    if (this._range.start !== start) {
      this._updateRange(start, end);
    }
  }

  /**
   * set start and end point of the index and calculated paddings
   *
   * @param {Number} start index of data array
   * @param {Number} end index of data array
   */
  _updateRange(start, end) {
    this._range.start = start;
    this._range.end = end;
    this._range.padFront = this._getPadTop();
    this._range.padBehind = this._getPadDown();
    this._updateCallBack(this.getRange());
  }

  /**
   * Calculate end index
   *
   * @param {Number} start index of data array
   * @returns {Number} estimated ending index of data array
   */
  _getEndByStart(start) {
    const end = start + this._options.keeps;
    const calcEnd = Math.min(end, this._getUniqueIdsLastIndex());
    return calcEnd;
  }

  getEstimateSize() {
    return this._firstRangeAverageSize || 0;
  }

  _getUniqueIdsLastIndex() {
    return this._options.uniqueIds.length - 1;
  }

  /**
   * Calculate position of a specific element
   *
   * @param {Number} start index of data array
   * @returns {Number} offset value from the start index provided
   */
  getOffset(start) {
    return (
      (start < 1 ? 0 : this._getIndexOffset(start)) +
      this._estimatedDocumentPosition
    );
  }
}
