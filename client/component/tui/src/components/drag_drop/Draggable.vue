<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Simon Chester <simon.chester@totaralearning.com>
  @module tui
-->

<script>
import { Point, Size } from 'tui/geometry';
import {
  defaultDragDropManager,
  getSourceIdName,
  getDroppableApiName,
  PHASE_IDLE,
  PHASE_DRAGGING,
  PHASE_DROP_ANIMATING,
  INTERACTION_KEYBOARD,
  INTERACTION_MOUSE,
  DragDropManager,
} from '../../js/internal/drag_drop';
import { waitForTransitionEnd } from 'tui/dom/transitions';
import { getBox, getViewportRect } from 'tui/dom/position';
import { toVueRequirements } from 'tui/i18n';
import { pick } from 'tui/util';
import DraggableMoveMenu from 'tui/components/drag_drop/DraggableMoveMenu';

/*

Dragging state is managed by the DragDropMananger.

To begin or end a drag we send a command to the DragDropManager, which updates
its internal state and sends our component the new state.

As such, most actions are multi-step: we store any needed data, send the command
to the manager, then the manager sends us a new state which we react to match.

Some state transitions (for example, to PHASE_IDLE) may happen without any
trigger from this component.

*/

const MIN_MOVEMENT_PX = 5;

const STYLE_PROPERTIES = ['width', 'height', 'top', 'left', 'transform'];

const clamp = (val, min, max) => (val < min ? min : val > max ? max : val);

export default {
  components: {
    DraggableMoveMenu,
  },

  inheritAttrs: false,

  inject: {
    [getSourceIdName]: { default: null },
    [getDroppableApiName]: { default: null },
  },

  props: {
    // eslint-disable-next-line vue/require-prop-types
    type: {
      required: true,
    },
    // eslint-disable-next-line vue/require-prop-types
    value: {
      required: true,
    },
    index: {
      type: Number,
      required: true,
    },
    disabled: Boolean,
    ariaLabel: String,
  },

  // ensure manager strings are loaded
  langStrings: toVueRequirements(DragDropManager.langStrings),

  data() {
    return {
      mouseDown: false,
      dragging: false,
      dropping: false,
      dragId: null,
      anyDragging: false,
      mouseOffset: new Point(0, 0),
      originalPagePosition: new Point(0, 0),
      originalClientPosition: new Point(0, 0),
      dragMode: null,
      styleSnapshot: null,
      availableLists: [],
      layoutInteraction: null,
      keepOriginalLocation: false,
    };
  },

  computed: {
    /**
     * @returns {DraggableDescriptor}
     */
    draggableDescriptor() {
      return {
        draggableKey: this.draggableKey, // unique key for this draggable
        type: this.type,
        value: this.value,
        index: this.index,
        sourceId: this[getSourceIdName] && this[getSourceIdName](),
      };
    },

    /**
     * @returns {DraggableEntry}
     */
    draggableEntry() {
      return {
        draggableKey: this.draggableKey, // unique key for this draggable
        descriptor: this.draggableDescriptor,
        getDimensions: this.getDimensions,
        focus: this.focus,
      };
    },

    /**
     * @returns {string}
     */
    draggableKey() {
      return this.uid;
    },
  },

  watch: {
    draggableEntry(entry, oldEntry) {
      if (entry.draggableKey !== oldEntry.draggableKey) {
        defaultDragDropManager.unregisterDraggable(oldEntry);
      }
      defaultDragDropManager.registerDraggable(entry);
    },
  },

  created() {
    if (this[getDroppableApiName]) {
      const droppableApi = this[getDroppableApiName]();
      this.draggableInterface = {
        setOffset: this.setOffset,
        setAnimationEnabled: this.setAnimationEnabled,
        getElement: () => this.$el,
        getIndex: () => this.index,
        getEntry: () => this.draggableEntry,
      };
      droppableApi.registerDraggable(
        this.draggableKey,
        this.draggableInterface
      );
    }
    defaultDragDropManager.registerDraggable(this.draggableEntry);
    this.$_stateUnsubscribe = defaultDragDropManager.stateSubscribe(
      this.$_handleStateUpdate
    );
  },

  destroyed() {
    this.$_dragCleanup();
    if (this.$_stateUnsubscribe) {
      this.$_stateUnsubscribe();
    }
    if (this[getDroppableApiName] && this.draggableInterface) {
      const da = this[getDroppableApiName]();
      da.unregisterDraggable(this.draggableKey, this.draggableInterface);
    }
    defaultDragDropManager.unregisterDraggable(this.draggableEntry);
  },

  methods: {
    /**
     * Update our component to match drag/drop state.
     *
     * @private
     */
    $_handleStateUpdate(state) {
      this.anyDragging = state.phase === PHASE_DRAGGING;

      const thisItem =
        state.dragItem && this.draggableKey === state.dragItem.draggableKey;

      this.layoutInteraction = state.layoutInteraction;
      this.keepOriginalLocation = state.layoutInteraction != 'move';

      // drag started
      if (state.phase === PHASE_DRAGGING && thisItem && !this.dragging) {
        this.dragMode =
          state.dragInteraction === INTERACTION_MOUSE ? 'MOUSE' : 'STEP';
        if (this.dragMode === 'MOUSE') {
          this.$_beginFineDrag();
        } else {
          this.$_beginStepMove();
        }
      }

      // update drag position
      if (
        state.phase === PHASE_DRAGGING &&
        thisItem &&
        this.dragging &&
        this.dragMode === 'STEP'
      ) {
        if (!this.keepOriginalLocation) {
          const viewport = getViewportRect();
          const diffX =
            state.draggingPos.px.x -
            viewport.left -
            this.originalClientPosition.x;
          const diffY =
            state.draggingPos.px.y -
            viewport.top -
            this.originalClientPosition.y;
          this.$el.style.transform = `translate(${diffX}px, ${diffY}px)`;
        }
      }

      // drag ended, animate drop
      if (
        state.phase === PHASE_DROP_ANIMATING &&
        thisItem &&
        this.dragging &&
        !this.dropping
      ) {
        this.$_animateDrop();
      }

      // drop has completed, reset
      if (state.phase === PHASE_IDLE && (this.dropping || this.dragging)) {
        this.$_finishDrop();
      }
    },

    /**
     * Get the dimensions of this draggable.
     */
    getDimensions() {
      const { offsetWidth, offsetHeight } = this.$el;
      const pageUntransformed = getBox(this.$el);
      return {
        size: new Size(offsetWidth, offsetHeight),
        pageUntransformed,
      };
    },

    /**
     * Enable/disable position animation.
     *
     * @param {boolean} enabled
     */
    setAnimationEnabled(enabled) {
      // future improvement: two methods, one for drop and one for offset?
      if (enabled) {
        this.$el.setAttribute('data-animated', true);
      } else {
        this.$el.removeAttribute('data-animated');
      }
    },

    /**
     * Offset draggable by pixels.
     *
     * @param {Point} point
     */
    setOffset(point) {
      if (point) {
        this.$el.style.transform = `translate(${point.x}px, ${point.y}px`;
      } else {
        this.$el.style.transform = '';
      }
    },

    /**
     * Focus the draggable element.
     */
    focus() {
      this.$el.focus();
    },

    /**
     * Store a snapshot of the original style properties that we're going to
     * modify, so we can restore them after the drag.
     *
     * @private
     */
    $_snapshotStyle() {
      this.styleSnapshot = pick(this.$el.style, STYLE_PROPERTIES);
    },

    /**
     * Restore the snapshotted style properties.
     *
     * @private
     */
    $_restoreStyleSnapshot() {
      Object.assign(this.$el.style, this.styleSnapshot);
      this.styleSnapshot = {};
    },

    handleMouseDown(e) {
      if (e.button !== 0) return;
      if (e.target.tagName === 'SELECT') return;

      // Stops any mouse down events from propagating from within a draggable element to outside, this allows nested draggable elements
      e.stopPropagation();

      if (this.dragging) {
        defaultDragDropManager.endDrag(this.dragId);
      }
      this.mouseDown = new Point(e.pageX, e.pageY);
      document.addEventListener('mouseup', this.handleDocumentMouseUp);
      document.addEventListener('mousemove', this.handleDocumentMouseMove);
      document.addEventListener('scroll', this.handleDocumentScroll);
    },

    handleKeyDown(e) {
      if (
        !this.dragging &&
        e.target === e.currentTarget &&
        (e.key == ' ' || e.key == 'Spacebar')
      ) {
        e.stopPropagation();
        e.preventDefault();
        this.dragId = defaultDragDropManager.startDrag(
          this.draggableKey,
          INTERACTION_KEYBOARD
        );
      }
    },

    handleMenuMove(list) {
      defaultDragDropManager.moveToList(this.draggableKey, list.sourceId);
    },

    handleFocusIn() {
      this.availableLists = defaultDragDropManager.getAvailableLists(
        this.draggableKey
      );
    },

    /**
     * @param {MouseEvent} e
     */
    handleDocumentMouseMove(e) {
      if (
        !this.dragging &&
        this.mouseDown &&
        (Math.abs(this.mouseDown.x - e.pageX) > MIN_MOVEMENT_PX ||
          Math.abs(this.mouseDown.y - e.pageY) > MIN_MOVEMENT_PX)
      ) {
        this.mouseDown = false;
        document.removeEventListener('scroll', this.handleDocumentScroll);
        this.$_requestMouseDrag(e);
      } else if (this.dragging) {
        if (!this.keepOriginalLocation) {
          const mousePos = new Point(e.clientX, e.clientY);
          const newPosX = mousePos.x - this.mouseOffset.x;
          const newPosY = mousePos.y - this.mouseOffset.y;
          const diffX = newPosX - this.originalClientPosition.x;
          const diffY = newPosY - this.originalClientPosition.y;

          this.$el.style.transform = `translate(${diffX}px, ${diffY}px)`;
        }
      }
    },

    handleDocumentMouseUp() {
      if (this.dragId) {
        defaultDragDropManager.endDrag(this.dragId);
      } else {
        this.$_dragCleanup();
      }
    },

    handleDocumentScroll() {
      // if drag has not triggered yet, abort on scroll to avoid big misalignment
      if (!this.dragId) {
        this.$_dragCleanup();
      }
    },

    /**
     * Request a mouse drag from the manager.
     *
     * @private
     * @param {MouseEvent} e
     */
    $_requestMouseDrag(e) {
      const offset = new Point(e.pageX, e.pageY);
      this.$_requestFineDrag(offset, INTERACTION_MOUSE);
    },

    /**
     * Request any sort of fine drag (currently mouse is the only supported one)
     * from the manager.
     *
     * @private
     * @param {Point} controllerPosition
     * @param {string} interaction
     */
    $_requestFineDrag(controllerPosition, interaction) {
      this.$_assignOriginalPosition();

      const offset = new Point(
        // in firefox, the position of the mouse event is sometimes outside the element!
        clamp(
          controllerPosition.x - this.originalPagePosition.x,
          0,
          this.$el.offsetWidth
        ),
        clamp(
          controllerPosition.y - this.originalPagePosition.y,
          0,
          this.$el.offsetHeight
        )
      );
      this.mouseOffset = offset; // offset relative to edge

      this.dragId = defaultDragDropManager.startDrag(
        this.draggableKey,
        interaction
      );
    },

    /**
     * Enter fine drag mode (this will happen as a result of calling
     * $_requestFineDrag()).
     *
     * @private
     */
    $_beginFineDrag() {
      const { offsetWidth, offsetHeight } = this.$el;
      this.$_assignOriginalPosition();
      this.$_snapshotStyle();

      if (!this.keepOriginalLocation) {
        this.$el.style.transform = `translate(0, 0)`;

        this.setAnimationEnabled(false);
        this.$el.setAttribute('data-dragging', true);
        this.$el.style.width = offsetWidth + 'px';
        this.$el.style.height = offsetHeight + 'px';
        this.$el.style.left = this.originalClientPosition.x + 'px';
        this.$el.style.top = this.originalClientPosition.y + 'px';
      }

      this.dragging = true;
    },

    /**
     * Enter step move mode (this will happen as a result of calling
     * DragDropManager.startDrag in the keydown handler).
     *
     * @private
     */
    $_beginStepMove() {
      const { offsetWidth, offsetHeight } = this.$el;
      this.$_assignOriginalPosition();

      this.$_snapshotStyle();

      if (!this.keepOriginalLocation) {
        this.$el.style.transform = `translate(0, 0)`;

        this.$el.offsetHeight; // reflow

        this.setAnimationEnabled(true);
        this.$el.setAttribute('data-dragging', true);
        this.$el.style.width = offsetWidth + 'px';
        this.$el.style.height = offsetHeight + 'px';
        this.$el.style.left = this.originalClientPosition.x + 'px';
        this.$el.style.top = this.originalClientPosition.y + 'px';
      }

      this.dragging = true;
    },

    /**
     * Store the original position of the draggable (client and page) for later
     * use.
     *
     * @private
     */
    $_assignOriginalPosition() {
      const box = getBox(this.$el);
      this.originalPagePosition = box.marginBox.getPosition();
      const viewportBox = getBox(this.$el, { viewport: true });
      this.originalClientPosition = viewportBox.marginBox.getPosition();
    },

    /**
     * Clean up dragging state and event listeners.
     *
     * @private
     */
    $_dragCleanup() {
      this.dragging = false;
      this.dragId = null;
      this.mouseDown = false;
      document.removeEventListener('mousemove', this.handleDocumentMouseMove);
      document.removeEventListener('mouseup', this.handleDocumentMouseUp);
      document.removeEventListener('scroll', this.handleDocumentScroll);
    },

    /**
     * Animate to final resting position, then tell the manager we have
     * finished.
     *
     * @private
     */
    async $_animateDrop() {
      const dragId = this.dragId;
      this.$_dragCleanup();
      this.dropping = true;
      const finalOffset = defaultDragDropManager.getFinalPagePosition(
        this.draggableDescriptor.draggableKey
      );
      if (finalOffset) {
        this.setAnimationEnabled(true);
        this.$el.offsetHeight; // reflow
        const viewport = getViewportRect();
        const diffX =
          finalOffset.x - viewport.left - this.originalClientPosition.x;
        const diffY =
          finalOffset.y - viewport.top - this.originalClientPosition.y;
        this.$el.style.transform = `translate(${diffX}px, ${diffY}px`;
        await waitForTransitionEnd(this.$el);
        // disable animation unless drop has ended already
        if (this.dropping && this.dragId === dragId) {
          this.setAnimationEnabled(false);
        }
      }
      if (this.dropping) {
        defaultDragDropManager.endDropAnimation(dragId);
      }
    },

    /**
     * Return to normal (not dragging) state.
     *
     * @private
     */
    $_finishDrop() {
      this.$_dragCleanup();
      this.dropping = false;
      this.$_restoreStyleSnapshot();
      this.$el.removeAttribute('data-dragging');
      this.dragId = null;
    },
  },

  render(h) {
    if (this.disabled) {
      const draggableAttrs = { 'data-tui-draggable': true };
      return this.$scopedSlots.default({
        draggableAttrs,
        attrs: draggableAttrs,
      });
    }

    const nativeDraggableEvents = {
      keydown: this.handleKeyDown,
      focusin: this.handleFocusIn,
    };

    const nativeDragHandleEvents = {
      mousedown: this.handleMouseDown,
    };

    const nativeEvents = Object.assign(
      {},
      nativeDraggableEvents,
      nativeDragHandleEvents
    );

    const draggableAttrs = {
      'data-tui-draggable': true,
      tabindex: 0,
      role: 'button',
      'aria-roledescription': this.$str(
        'dragdrop_draggable_description',
        'totara_core'
      ),
      'aria-describedby': defaultDragDropManager.getDragInstructionsId(),
      'aria-label': this.ariaLabel,
    };

    const moveMenu = h(DraggableMoveMenu, {
      props: {
        availableLists: this.availableLists,
      },
      on: {
        move: this.handleMenuMove,
      },
    });

    return this.$scopedSlots.default({
      dragging: this.dragging,
      anyDragging: this.anyDragging,
      attrs: draggableAttrs,
      draggableAttrs,
      dragHandleAttrs: {},
      events: nativeEvents,
      nativeEvents,
      nativeDraggableEvents,
      nativeDragHandleEvents,
      moveMenu,
    });
  },
};
</script>

<lang-strings>
{
  "totara_core": ["dragdrop_draggable_description"]
}
</lang-strings>

<style lang="scss">
[data-tui-draggable] {
  &[data-animated] {
    transition: transform 0.25s;
  }

  &[data-dragging] {
    position: fixed;
    z-index: var(--zindex-draggable);
    user-select: none;
    // required so droppable can get mouse events
    pointer-events: none;
  }
}
</style>
