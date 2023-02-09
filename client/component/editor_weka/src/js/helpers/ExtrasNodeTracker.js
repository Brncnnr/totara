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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module editor_weka
 */

import { getBoundingClientRect, getBox } from 'tui/dom/position';
import { getClosestScrollable } from 'tui/dom/scroll';
import { Point } from 'tui/geometry';
import { isRtl } from 'tui/i18n';
import ResizeObserver from 'tui/polyfills/ResizeObserver';

/** @typedef {'top-start' | 'top-end' | 'bottom-start' | 'bottom-end'} Quadrant */

/**
 * @typedef {object} ExtrasNodeTrackerOptions
 * @property {HTMLElement} opt.trackedEl
 * @property {HTMLElement} opt.viewExtrasEl
 * @property {Quadrant} opt.trackedElAnchor Which corner of the tracked element to place the anchor.
 * @property {Quadrant} opt.positionedElAnchor Which corner of the positioned element to place the anchor.
 */

const logicalSideToPhysicalLtr = { start: 'left', end: 'right' };
const logicalSideToPhysicalRtl = { start: 'right', end: 'left' };

/**
 * Position a node in extras by tracking a reference element.
 */
export default class ExtrasNodeTracker {
  /**
   * @param {HTMLElement} el
   * @param {ExtrasNodeTrackerOptions} opt
   */
  constructor(el, opt) {
    this._el = el;
    this._trackedEl = opt.trackedEl;
    this._trackedElAnchor = opt.trackedElAnchor;
    this._positionedElAnchor = opt.positionedElAnchor;
    this._viewExtrasEl = opt.viewExtrasEl;
    this._captured = false;
    this._shown = false;
    this._destroyed = false;
    /** @type {HTMLElement[] | null} */
    this._scrollableContainers = null;

    this._handleDOMUpdate = this._handleDOMUpdate.bind(this);
  }

  /**
   * Capture the element (remove it from its original location)
   */
  captureNode() {
    if (this._captured || this._destroyed) {
      return;
    }
    if (this._el.parentNode && this._el.parentNode != this._viewExtrasEl) {
      const marker = document.createComment('captured node');
      this._el.parentNode.insertBefore(marker, this._el);
      this._el.prevLocationMarker = marker;
      this._el.remove();
    }
    this._captured = true;
  }

  /**
   * Release the element (return it to its original location)
   */
  releaseNode() {
    if (!this._captured || this._destroyed) {
      return;
    }
    this._hide();
    if (this._el.prevLocationMarker) {
      this._el.prevLocationMarker.parentNode.replaceChild(
        this._el,
        this._el.prevLocationMarker
      );
      delete this._el.prevLocationMarker;
    } else {
      this._el.remove();
    }
    this._captured = false;
  }

  /**
   * Get whether the element is currently captured.
   *
   * @returns {boolean}
   */
  getCaptured() {
    return this._captured;
  }

  /**
   * Set whether to capture the element.
   *
   * @param {boolean} value
   */
  setCaptured(value) {
    if (value) {
      this.captureNode();
    } else {
      this.releaseNode();
    }
  }

  /**
   * Get whether the element is currently visible.
   *
   * @returns {boolean}
   */
  getVisible() {
    return this._shown;
  }

  /**
   * Set whether the element should be visible.
   *
   * @param {boolean} value
   */
  setVisible(value) {
    if (value) {
      this._show();
    } else {
      this._hide();
    }
  }

  /**
   * Show the element and start tracking.
   */
  _show() {
    if (this._shown || !this._captured || this._destroyed) {
      return;
    }
    this._viewExtrasEl.appendChild(this._el);

    this._resizeOberver = new ResizeObserver(this._handleDOMUpdate);
    this._resizeOberver.observe(this._el);
    this._resizeOberver.observe(this._trackedEl);

    this._scrollableContainers = [];
    let scrollable = getClosestScrollable(this._trackedEl);
    while (scrollable) {
      this._scrollableContainers.push(scrollable);
      scrollable.addEventListener('scroll', this._handleDOMUpdate);
      scrollable = getClosestScrollable(scrollable.parentNode);
    }

    this._shown = true;
    this._handleDOMUpdate();
  }

  /**
   * Hide the element.
   */
  _hide() {
    if (!this._shown || this._destroyed) {
      return;
    }

    this._resizeOberver.disconnect();
    this._resizeOberver = null;

    if (this._scrollableContainers) {
      this._scrollableContainers.forEach(x =>
        x.removeEventListener('scroll', this._handleDOMUpdate)
      );
      this._scrollableContainers = null;
    }

    this._el.remove();

    Object.assign(this._el.style, {
      left: null,
      top: null,
      width: null,
    });

    this._shown = false;
  }

  /**
   * Clean up and release resources for this tracker instance.
   */
  destroy() {
    this.releaseNode();
    this._destroyed = true;
  }

  /**
   * Callback for when something we care about changes in the DOM.
   */
  _handleDOMUpdate() {
    this._updatePosition();
  }

  /**
   * Update the position of the element.
   */
  _updatePosition() {
    if (!this._shown) {
      return;
    }

    // handle when editor is hidden
    if (!this._viewExtrasEl.offsetParent) {
      this._el.style.display = 'none';
      return;
    }

    const offsetRefPos = getBoundingClientRect(
      this._viewExtrasEl.offsetParent
    ).getPosition();
    const refRect = getBoundingClientRect(this._trackedEl).sub(offsetRefPos);

    const [trackedAnchorV, trackedAnchorH] = this._trackedElAnchor.split('-');
    const trackedAnchorPos = new Point(
      refRect[this._logicalSideToPhysical(trackedAnchorH)],
      refRect[trackedAnchorV]
    );

    // check if the anchor point is visible (not scrolled away)
    const anchorVisible = this._scrollableContainers.every(container =>
      getBoundingClientRect(container)
        .sub(offsetRefPos)
        .contains(trackedAnchorPos)
    );

    this._el.style.display = anchorVisible ? '' : 'none';

    if (!anchorVisible) {
      return;
    }

    const elBox = getBox(this._el);

    const [elAnchorV, elAnchorH] = this._positionedElAnchor.split('-');
    let location = trackedAnchorPos.sub(
      new Point(
        this._logicalSideToPhysical(elAnchorH) === 'right'
          ? elBox.marginBox.width
          : 0,
        elAnchorV === 'bottom' ? elBox.marginBox.height : 0
      )
    );

    this._el.style.left = location.x + 'px';
    this._el.style.top = location.y + 'px';

    // adjust width if needed to avoid horizontal scrolling
    const docWidth = document.documentElement.clientWidth;
    this._el.style.width = null;
    if (offsetRefPos.x + location.x + elBox.marginBox.width > docWidth) {
      this._el.style.width = docWidth - (offsetRefPos.x + location.x);
    }
  }

  _logicalSideToPhysical(side) {
    return (isRtl() ? logicalSideToPhysicalRtl : logicalSideToPhysicalLtr)[
      side
    ];
  }
}
