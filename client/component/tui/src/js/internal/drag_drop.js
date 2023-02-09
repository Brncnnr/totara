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

/*

Dragging state is managed by the DragDropMananger.

Rather than performing interactions directly, Draggable items and Droppable
areas send actions to DragDropManager, which then mutates the state and
publishes a state update.

The components then react to the changed state, similar to a state store like
Redux (though less pure in the functional sense).

Debugging suggestion: uncomment the console.log in _setState

*/

import Vue from 'vue';
// eslint-disable-next-line no-unused-vars
import { Size, Point } from 'tui/geometry';
import { pull, orderBy } from 'tui/util';
import DragDropAnnouncer from './drag_drop_announcer';
import { uniqueId } from './util/misc';
import { langString, getString } from 'tui/i18n';

export const getSourceIdName = 'tui_dragDrop_getSourceId';
export const getDroppableApiName = 'tui_dragDrop_getDroppableApi';

export const PHASE_IDLE = 'IDLE';
export const PHASE_DRAGGING = 'DRAGGING';
export const PHASE_DROP_ANIMATING = 'DROP_ANIMATING';

export const INTERACTION_MOUSE = 'MOUSE';
export const INTERACTION_KEYBOARD = 'KEYBOARD';

const PREV_KEYS = ['ArrowUp', 'Up', 'ArrowLeft', 'Left'];
const NEXT_KEYS = ['ArrowDown', 'Down', 'ArrowRight', 'Right'];

/**
 * @typedef {Object} DragDropState
 * @property {(PHASE_IDLE|PHASE_DRAGGING|PHASE_DROP_ANIMATING)} phase
 * @property {?DraggableEntry} dragItem
 * @property {number} dragId
 * @property {?(INTERACTION_MOUSE|INTERACTION_KEYBOARD)} dragInteraction Sensor
 * @property {?DropDesc} dropDesc
 * @property {?Impact} impact
 * @property {?{px: Point}} draggingPos
 * @property {?('vertical'|'horizontal')} axis
 * @property {?('move'|'grid-line')} layoutInteraction
 */

/**
 * @typedef {Object} DraggableEntry
 * @property {string} draggableKey
 * @property {DraggableDescriptor} descriptor
 * @property {() => DraggableDimensions} getDimensions
 * @property {() => void} focus
 */

/**
 * @typedef {Object} DraggableDescriptor
 * @property {string} draggableKey
 * @property {string} draggableKey
 * @property {*} type
 * @property {*} value
 * @property {number} index
 * @property {string} sourceId
 */

/**
 * @typedef {Object} DraggableDimensions
 * @property {Size} size
 * @property {{ marginBox: Rect, borderBox: Rect }} pageUntransformed
 */

/**
 * @typedef {Object} DropDesc
 * @property {string} sourceId
 * @property {?number} index Null when querying if an item is valid at all.
 */

/**
 * @typedef {Object} Impact
 * @property {string[]} displaced IDs of displaced draggables.
 * @property {Point} px Amount in pixels by which they are displaced.
 */

/**
 * @typedef {Object} DragEndInfo
 * @property {boolean} drop Was the droppable dropped on the droppable?
 */

/**
 * @typedef {Object} Source
 * @property {string} sourceId
 * @property {string} sourceName
 * @property {('horizontal'|'vertical')} axis
 * @property {('move'|'grid-line')} layoutInteraction
 * @property {string} sourceId
 * @property {(info: DraggableDescriptor, dropDesc: DropDesc) => void} handleDrop Called when item dropped on source.
 * @property {(info: DraggableDescriptor, dropDesc: DropDesc) => void} handleDropOut Called when a droppable from this source is dropped on another source.
 * @property {(info: DraggableDescriptor, dropDesc: DropDesc) => boolean} handleValidateDrop Called to validate drop is accepable. Should return true/false
 */

export class DragDropManager {
  constructor() {
    /** @type {DragDropState} */
    this.state = {
      phase: PHASE_IDLE,
      dragItem: null,
      dragId: 1,
      dragInteraction: null,
      dropDesc: null,
      impact: null,
    };

    // internal
    /** @type {Source} */
    this._finalDropTarget = null;
    /** @type {Array<(state: DragDropState) => void>} */
    this._listeners = [];
    /** @type {Object.<string, Source>} */
    this._sources = {};
    /** @type {Object.<string, DraggableEntry>} */
    this._draggableMap = {};
    this._isIdle = true;
    /** @type {Element} */
    this._dragInstructions = null;
    /** @type {HTMLStyleElement} */
    this._styleEl = null;
    /** @type {string} */
    this._currentStyle = '';

    this._handleWindowKeydown = this._handleWindowKeydown.bind(this);
    this._handleWindowScroll = this._handleWindowScroll.bind(this);
    this._handleWindowKeydownKbMove = this._handleWindowKeydownKbMove.bind(
      this
    );

    this._announcer = new DragDropAnnouncer(this);

    this._setupStateHandlers();
  }

  /**
   * Register state change listener.
   *
   * @param {(state: DragDropState) => void} listener Called when state changes.
   * @returns {() => void} Unsubscribe function.
   */
  stateSubscribe(listener) {
    this._listeners.push(listener);
    return () => pull(this._listeners, listener);
  }

  /**
   * Register a new source.
   *
   * @param {string} id
   * @param {Source} inst
   */
  registerSource(id, inst) {
    this._sources[id] = inst;
  }

  /**
   * Unregister source.
   *
   * @param {string} id
   * @param {Source} inst
   */
  unregisterSource(id, inst) {
    if (this._sources[id] === inst) {
      delete this._sources[id];
    }
  }

  /**
   * @param {DraggableEntry} draggable
   */
  registerDraggable(draggable) {
    this._draggableMap[draggable.draggableKey] = draggable;
  }

  /**
   * @param {DraggableEntry} draggable
   */
  unregisterDraggable(draggable) {
    delete this._draggableMap[draggable.draggableKey];
  }

  /**
   * Update the state object and notify listeners.
   *
   * this.state and objects inside it should not be mutated directly.
   *
   * @private
   * @param {Object} stateUpdate Object to merge with this.state.
   */
  _setState(stateUpdate) {
    const oldState = this.state;
    this.state = Object.assign({}, this.state, stateUpdate);
    // console.log(this.state);
    this._stateHandlers.forEach(h => h(this.state, oldState));
    const clonedState = Object.assign({}, this.state);
    this._listeners.forEach(l => l(clonedState));
  }

  /**
   * Set up handlers to react to state changes.
   *
   * @private
   */
  _setupStateHandlers() {
    this._stateHandlers = [
      // register global events when not idle
      observeStateValue(
        state => state.phase !== PHASE_IDLE,
        value => {
          toggleEventListener(
            window,
            'keydown',
            this._handleWindowKeydown,
            value
          );
          toggleEventListener(
            window,
            'scroll',
            this._handleWindowScroll,
            value
          );
        }
      ),

      // add global keyboard interaction helpers
      observeStateValue(
        state =>
          state.phase === PHASE_DRAGGING &&
          state.dragInteraction === INTERACTION_KEYBOARD,
        value => {
          toggleEventListener(
            window,
            'keydown',
            this._handleWindowKeydownKbMove,
            value
          );
        }
      ),

      // update global body attrs for styling
      observeStateValue(
        state => state.phase !== PHASE_IDLE,
        value => {
          if (value) {
            document.body.setAttribute('data-tui-droppable-any-active', true);
          } else {
            document.body.removeAttribute('data-tui-droppable-any-active');
          }
        }
      ),

      // control cursor indication
      () => this._updateStyle(),
    ];
  }

  /**
   * Start a drag operation.
   *
   * Returns a drag ID that can be used when performing other actions on the drag.
   *
   * @param {string} draggableKey
   * @param {(INTERACTION_MOUSE|INTERACTION_KEYBOARD)} interaction
   * @returns {number} Drag ID.
   */
  startDrag(draggableKey, interaction) {
    this._flushPending();
    if (this.state.phase !== PHASE_IDLE) {
      this._setIdle();
    }
    const entry = this._draggableMap[draggableKey];
    if (!entry) return;
    const descriptor = entry.descriptor;
    const { sourceId, index } = descriptor;
    const droppable = this._sources[sourceId];

    const dropDesc = { sourceId, index };

    const valid = this.canDrop(entry.descriptor, dropDesc);

    // source and index are unchanged at this point, but we need to calculate
    // impact as the dragged item will be taken out of the flow
    const impact = getImpact(
      this._getDroppableDraggables(sourceId),
      sourceId,
      sourceId,
      index,
      index,
      entry,
      droppable.layoutInteraction,
      droppable.axis
    );

    this._setState({
      phase: PHASE_DRAGGING,
      dragId: this.state.dragId + 1,
      dragItem: entry,
      dragInteraction: interaction,
      dropDesc,
      axis: droppable.axis,
      layoutInteraction: droppable.layoutInteraction,
      impact,
      valid,
      draggingPos: {
        px: entry.getDimensions().pageUntransformed.marginBox.getPosition(),
      },
    });

    this._announcer.handleDragStart({ dropDesc: this.state.dropDesc });

    return this.state.dragId;
  }

  /**
   * Begin the end of a drag operation.
   *
   * Will not end immediately as it may trigger an animation.
   *
   * @param {number} dragId
   */
  endDrag(dragId) {
    if (this.state.dragId !== dragId) {
      return;
    }
    this._endDrag();
  }

  /**
   * Begin the end of a drag operation.
   *
   * Will not end immediately as it may trigger an animation.
   *
   * @private
   */
  _endDrag({ animate = true } = {}) {
    if (!this.state.dragItem) {
      this._setIdle();
      return;
    }

    const descriptor = this.state.dragItem.descriptor;
    // is this a successful drop? or are we ending without dropping?
    const drop = this.canDrop(descriptor, this.state.dropDesc);
    const canAnimate = this.state.layoutInteraction == 'move';
    const nullMove = this._announcer.isNullMove({
      dragItem: this.state.dragItem,
      dropDesc: this.state.dropDesc,
    });

    if (drop && !nullMove) {
      if (canAnimate && animate) {
        this._setState({
          phase: PHASE_DROP_ANIMATING,
        });
      } else {
        this._commitDrop();
      }
    } else {
      this._cancelDrag();
    }
  }

  /**
   * Cancel the drag and return to idle phase.
   *
   * @param {number} dragId
   */
  cancelDrag(dragId) {
    if (this.state.dragId !== dragId) {
      return;
    }

    this._cancelDrag();
  }

  /**
   * Cancel the drag and return to idle phase.
   *
   * @private
   */
  _cancelDrag() {
    if (
      this.state.phase !== PHASE_IDLE &&
      this.state.dragItem &&
      this.state.dropDesc
    ) {
      this._announcer.handleDragEnd({
        dragItem: this.state.dragItem,
        dropDesc: this.state.dropDesc,
        drop: false,
      });
    }
    this._setIdle();
  }

  /**
   * Reset state and return to idle phase.
   *
   * @private
   */
  _setIdle() {
    this._setState({
      phase: PHASE_IDLE,
      dragItem: null,
      dragInteraction: null,
      dropDesc: null,
      impact: null,
      draggingPos: null,
      valid: false,
    });
  }

  /**
   * End drop animation and commit drop.
   *
   * @param {number} dragId
   */
  endDropAnimation(dragId) {
    if (this.state.dragId === dragId) {
      this._endDropAnimation();
    }
  }

  /**
   * End drop animation and commit drop.
   *
   * @private
   */
  _endDropAnimation() {
    if (this.state.phase !== PHASE_DROP_ANIMATING) {
      return;
    }
    this._commitDrop();
  }

  /**
   * Commit drop - notify sources that item has been dropped and update state.
   *
   * @private
   */
  _commitDrop() {
    if (this.state.dragItem && this.state.dropDesc) {
      this._notifyDrop(this.state.dragItem, this.state.dropDesc);
    }
    this._setIdle();
  }

  /**
   * Notify sources that item has been dropped
   *
   * @private
   * @param {DraggableEntry} dragItem
   * @param {DropDesc} dropDesc
   */
  _notifyDrop(dragItem, dropDesc) {
    const source = this._sources[dropDesc.sourceId];
    if (!source) return;
    const dragging = dragItem.descriptor;
    const drop = this.canDrop(dragging, dropDesc);
    if (drop) {
      this._announcer.handleDragEnd({
        dragItem,
        dropDesc,
        drop,
      });
      // find source and notify that it has been dropped on another droppable
      if (source.sourceId != dragging.sourceId) {
        const source = this._sources[dragging.sourceId];
        if (source) {
          source.handleDropOut(dragging, dropDesc);
        }
      }
      source.handleDrop(dragging, dropDesc);
    }
  }

  /**
   * Set the drop position of the current draggable.
   *
   * @param {?string} sourceId
   * @param {?number} index
   */
  setDropAt(sourceId, index) {
    const { dragItem, dropDesc } = this.state;

    if (sourceId == null || index == null) {
      if (dropDesc) {
        this._announcer.handleDragMove({
          dragItem,
          dropDesc: null,
          valid: false,
        });
        this._setState({
          dropDesc: null,
          impact: null,
          valid: false,
        });
      }
      return;
    }

    // not dragging / invalid source
    if (!dragItem || !this._sources[sourceId]) {
      return;
    }

    // no change
    if (
      dropDesc &&
      sourceId === dropDesc.sourceId &&
      index === dropDesc.index
    ) {
      return;
    }

    const draggables = this._getDroppableDraggables(sourceId);

    const newDroppable = this._sources[sourceId];
    const newDropDesc = { sourceId, index };
    const valid = this.canDrop(dragItem.descriptor, newDropDesc);

    const impact = getImpact(
      draggables,
      dragItem.descriptor.sourceId,
      sourceId,
      dragItem.descriptor.index,
      index,
      dragItem,
      newDroppable.layoutInteraction,
      newDroppable.axis
    );

    this._setState({
      dropDesc: newDropDesc,
      axis: newDroppable.axis,
      layoutInteraction: newDroppable.layoutInteraction,
      impact,
      valid,
      // TODO: use this instead of calling defaultDragDropManager.getFinalPagePosition()?
      draggingPos: {
        px: getFinalPagePosition(
          dragItem,
          this._draggableMap,
          draggables,
          impact
        ),
      },
    });
    this._announcer.handleDragMove({
      dragItem,
      dropDesc: this.state.dropDesc,
      valid,
    });
  }

  /**
   * Deselect source as drop target (if mouse interaction).
   *
   * @param {string} sourceId
   */
  droppableMouseOut(sourceId) {
    const { dropDesc, dragItem, dragInteraction } = this.state;
    if (
      dragInteraction === INTERACTION_MOUSE &&
      dropDesc &&
      sourceId === dropDesc.sourceId &&
      // must not execute in DROP_ANIMATING phase otherwise info will not be there at the end.
      this.state.phase === PHASE_DRAGGING
    ) {
      if (dropDesc) {
        this._announcer.handleDragMove({
          dragItem,
          dropDesc: null,
          valid: false,
        });
      }
      this._setState({
        dropDesc: null,
        impact: null,
        valid: false,
      });
    }
  }

  /**
   * Get offset for final resting position for draggable.
   *
   * @param {string} draggableKey
   */
  getFinalPagePosition(draggableKey) {
    if (
      this.state.dropDesc &&
      this.state.dragItem &&
      this.state.dragItem.draggableKey === draggableKey
    ) {
      const draggables = this._getDroppableDraggables(
        this.state.dropDesc.sourceId
      );
      return getFinalPagePosition(
        this.state.dragItem,
        this._draggableMap,
        draggables,
        this.state.impact
      );
    }
  }

  /**
   * Get the source with the provided ID.
   *
   * @param {string} sourceId
   * @returns {Source}
   */
  getSource(sourceId) {
    return this._sources[sourceId];
  }

  /**
   * Check if the item can be dropped at dropDesc.
   *
   * @param {DraggableDescriptor} descriptor
   * @param {DropDesc} dropDesc
   */
  canDrop(descriptor, dropDesc) {
    if (!descriptor || !dropDesc) return false;
    const fromSource = this._sources[descriptor.sourceId];
    const toSource = this._sources[dropDesc.sourceId];
    if (
      fromSource &&
      fromSource !== toSource &&
      (fromSource.reorderOnly || toSource.reorderOnly)
    ) {
      return false;
    }
    return toSource && toSource.handleValidateDrop(descriptor, dropDesc);
  }

  /**
   * Get a list of sources the provided draggable can be dropped in to.
   *
   * @param {string} draggableKey
   * @returns {{sourceId: string, sourceName: string}}
   */
  getAvailableLists(draggableKey) {
    const entry = this._draggableMap[draggableKey];
    const descriptor = entry && entry.descriptor;
    if (!descriptor) return [];
    return Object.values(this._sources)
      .filter(
        x =>
          x.sourceId != descriptor.sourceId &&
          this.canDrop(descriptor, {
            index: null,
            sourceId: x.sourceId,
          })
      )
      .map(x => ({
        sourceId: x.sourceId,
        sourceName: x.sourceName,
      }));
  }

  /**
   * Move a draggable to a different list.
   *
   * @param {string} draggableKey
   * @param {string} sourceId
   */
  moveToList(draggableKey, sourceId) {
    const draggable = this._draggableMap[draggableKey];
    const source = this._sources[sourceId];
    if (!draggable || !source) {
      console.warn('moveToList: draggable or source do not exist');
      return;
    }
    const oldIndex = draggable.descriptor.index;
    const draggables = this._getDroppableDraggables(
      draggable.descriptor.sourceId
    );
    let newFocus =
      draggables.find(x => x.descriptor.index === oldIndex - 1) ||
      draggables.find(x => x.descriptor.index === oldIndex + 1);
    if (newFocus) newFocus.focus();
    const newListMax = getIndexLimits(this._getDroppableDraggables(sourceId))
      .maxIndex;
    this._notifyDrop(draggable, { sourceId, index: newListMax + 1 });
  }

  /**
   * Get the ID of the div containing drag instructions.
   *
   * @returns {string}
   */
  getDragInstructionsId() {
    if (!this._dragInstructions) {
      const el = document.createElement('div');
      this._dragInstructions = el;
      el.id = 'uid-' + uniqueId();
      el.style.display = 'none';
      el.textContent = getString(
        'dragdrop_draggable_instructions',
        'totara_core'
      );
      document.body.appendChild(el);
    }
    return this._dragInstructions.id;
  }

  /**
   * Move dragged element in direction.
   *
   * @private
   * @param {number} change
   */
  _moveInDirection(change) {
    const { dropDesc, dragItem } = this.state;
    if (!dropDesc || !dragItem) {
      return;
    }

    let index = dropDesc.index + change;

    const draggables = this._getDroppableDraggables(dropDesc.sourceId);

    // limit index
    const { minIndex, maxIndex } = getIndexLimits(draggables);
    index = Math.max(minIndex, Math.min(maxIndex, index));

    this.setDropAt(dropDesc.sourceId, index);
  }

  /**
   * Get draggables inside the specified droppable.
   *
   * @private
   * @param {*} sourceId
   * @returns {DraggableEntry[]}
   */
  _getDroppableDraggables(sourceId) {
    return Object.values(this._draggableMap).filter(
      x => x.descriptor.sourceId === sourceId
    );
  }

  /**
   * Flush pending actions/animations and return to idle.
   *
   * @private
   */
  _flushPending() {
    if (this.state.phase === PHASE_DROP_ANIMATING) {
      this._endDropAnimation();
    }
  }

  /**
   * Get the element we use to inject styles to the page.
   *
   * @private
   * @returns {HTMLStyleElement}
   */
  _getStyleEl() {
    if (!this._styleEl) {
      this._styleEl = document.createElement('style');
      document.head.appendChild(this._styleEl);
    }
    return this._styleEl;
  }

  /**
   * Set CSS styles.
   *
   * @private
   * @param {string} content
   */
  _setStyle(content) {
    if (this._currentStyle == content) return;
    const el = this._getStyleEl();
    el.innerHTML = content;
    this._currentStyle = content;
  }

  /**
   * Calculate new CSS styles to use.
   *
   * @private
   */
  _updateStyle() {
    // future improvement: if we stop using fallthrough mouse events for
    // positioning (as we will need to do for touch), we can set the cursor on
    // the draggable rather than globally.
    const { phase, valid } = this.state;
    if (phase === PHASE_IDLE) {
      this._setStyle('');
    } else {
      const cursor = valid ? 'grabbing' : 'no-drop';
      this._setStyle(`
        * {
          cursor: ${cursor} !important;
        }
        body {
          -webkit-user-select: none;
          -ms-user-select: none;
          user-select: none;
        }
      `);
    }
  }

  /**
   * Handle keydown during drag.
   *
   * @private
   * @param {KeyboardEvent} e
   */
  _handleWindowKeydown(e) {
    if (e.key == 'Escape') {
      if (this.state.phase === PHASE_DRAGGING) {
        this.cancelDrag(this.state.dragId);
      }
    }
  }

  /**
   * Handle window scrolling during drag.
   *
   * @private
   */
  _handleWindowScroll() {
    // stop keyboard drag on window scroll to avoid issue with positioning
    if (
      this.state.phase === PHASE_DRAGGING &&
      this.state.dragInteraction === INTERACTION_KEYBOARD
    ) {
      this._cancelDrag();
    }

    // flush animation on scroll to avoid misalignment
    if (this.state.phase === PHASE_DROP_ANIMATING) {
      this._flushPending();
    }
  }

  /**
   * Handle keydown during keyboard drag.
   *
   * @private
   * @param {KeyboardEvent} e
   */
  _handleWindowKeydownKbMove(e) {
    const isPrev = PREV_KEYS.includes(e.key);
    if (isPrev || NEXT_KEYS.includes(e.key)) {
      e.preventDefault();
      e.stopPropagation();
      this._moveInDirection(isPrev ? -1 : 1);
    }

    switch (e.key) {
      case ' ':
      case 'Spacebar': {
        e.preventDefault();
        const key = this.state.dragItem.draggableKey;
        this._endDrag({ animate: false });
        // refocus (after moving upwards, focus gets lost)
        Vue.nextTick(() => {
          const draggable = this._draggableMap[key];
          if (draggable) draggable.focus();
        });
        break;
      }

      case 'Tab':
        this._endDrag({ animate: false });
        break;
    }
  }
}

DragDropManager.langStrings = [
  langString('dragdrop_draggable_instructions', 'totara_core'),
].concat(DragDropAnnouncer.langStrings);

export const defaultDragDropManager = new DragDropManager();

/**
 * React to changes (!==) in a mapped state value.
 *
 * @param {(state: DragDropState)} map
 * @param {(value, oldValue, state: DragDropState, oldState: DragDropState) => void} fn
 */
function observeStateValue(map, fn) {
  return (state, oldState) => {
    const value = map(state),
      oldValue = map(oldState);
    if (value !== oldValue) {
      fn(value, oldValue, state, oldState);
    }
  };
}

/**
 * Toggle an event listener on an element.
 *
 * @param {Element} el
 * @param {string} type
 * @param {function} handler
 * @param {*} enabled
 */
function toggleEventListener(el, type, handler, enabled) {
  if (enabled) {
    el.addEventListener(type, handler);
  } else {
    el.removeEventListener(type, handler);
  }
}

/**
 * Get final resting position for draggable.
 *
 * @param {DraggableEntry} dragEntry
 * @param {Object.<string, DraggableEntry>} draggableMap
 * @param {DraggableEntry[]} draggables
 * @param {Impact} impact
 */
function getFinalPagePosition(dragEntry, draggableMap, draggables, impact) {
  let point = null;
  const nextDraggable =
    impact.displaced[0] != null && draggableMap[impact.displaced[0]];
  if (nextDraggable) {
    point = nextDraggable
      .getDimensions()
      .pageUntransformed.marginBox.getPosition();
  } else {
    const referenceDraggables = orderBy(
      draggables.filter(d => d.draggableKey != dragEntry.draggableKey),
      value => value.descriptor.index
    );
    let draggable = referenceDraggables[referenceDraggables.length - 1];
    if (!draggable) {
      console.warn(
        'unable to to find reference draggable to position dragging item in ' +
          'last slot'
      );
      draggable = dragEntry;
    }
    const { marginBox } = draggable.getDimensions().pageUntransformed;
    point =
      impact.axis === 'vertical'
        ? new Point(marginBox.left, marginBox.bottom)
        : new Point(marginBox.right, marginBox.top);
  }

  return point;
}

/**
 * Get the impact of moving a droppable in a list.
 *
 * @param {DraggableEntry[]} draggables
 * @param {string} sourceSourceId Droppable item was originally in.
 * @param {string} destinationSourceId Droppable item will be dropped in.
 * @param {number} originalIndex Original index.
 * @param {number} dropIndex Index item will be dropped at.
 * @param {DraggableEntry} dragEntry
 * @param {string} layoutInteraction
 * @param {'vertical'|'horizontal'} axis
 * @return {Impact}
 */
function getImpact(
  draggables,
  sourceSourceId,
  destinationSourceId,
  originalIndex,
  dropIndex,
  dragEntry,
  layoutInteraction,
  axis
) {
  if (!destinationSourceId) {
    return { displaced: [], displacedBy: { px: new Point(0, 0) }, axis };
  }

  if (sourceSourceId == destinationSourceId && dropIndex >= originalIndex) {
    dropIndex++;
  }

  checkIndexes(draggables);

  const displaced = orderBy(draggables, entry => entry.descriptor.index)
    .filter(
      entry =>
        entry.draggableKey != dragEntry.draggableKey &&
        entry.descriptor.index >= dropIndex
    )
    .map(entry => entry.draggableKey);

  const { pageUntransformed } = dragEntry.getDimensions();

  return {
    displaced,
    displacedBy: {
      px:
        layoutInteraction == 'move'
          ? axis == 'vertical'
            ? new Point(0, pageUntransformed.marginBox.height)
            : new Point(pageUntransformed.marginBox.width, 0)
          : new Point(0, 0),
    },
    axis,
  };
}

/**
 * Get min/max indexes.
 *
 * @param {DraggableEntry[]} draggables
 * @returns {{ minIndex: number, maxIndex: number }}
 */
function getIndexLimits(draggables) {
  const indexes = draggables.map(x => x.descriptor.index);
  const minIndex = draggables.length > 0 ? Math.min(...indexes) : 0;
  const maxIndex = draggables.length > 0 ? Math.max(...indexes) : 0;
  return { minIndex, maxIndex };
}

/**
 * Check draggable indexes are sequential, and print an error in the console if
 * they are not.
 *
 * They do not have to start at 0, but they must be sequential - no gaps and no
 * repeated indexes.
 *
 * @param {DraggableEntry[]} draggables
 */
function checkIndexes(draggables) {
  if (process.env.NODE_ENV !== 'production' && draggables.length > 1) {
    const indexes = orderBy(
      draggables.map(x => x.descriptor.index),
      x => x
    );
    for (let i = 1; i < indexes.length; i++) {
      if (indexes[i] !== indexes[i - 1] + 1) {
        console.error(
          `Draggable indexes must be sequential. ${
            indexes[i]
          } does not follow ${indexes[i - 1]}`
        );
        break;
      }
    }
  }
}
