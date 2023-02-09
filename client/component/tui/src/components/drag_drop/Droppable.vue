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
import { orderBy } from 'tui/util';
import { Point } from 'tui/geometry';
import {
  defaultDragDropManager,
  getSourceIdName,
  getDroppableApiName,
  PHASE_IDLE,
  PHASE_DRAGGING,
  PHASE_DROP_ANIMATING,
  INTERACTION_MOUSE,
} from '../../js/internal/drag_drop';
import { getViewportRect, getDocumentPosition } from '../../js/dom/position';

/**
 * @typedef {Object} DraggableInterface
 * @property {(point: Point) => void} setOffset
 * @property {() => Element} getElement
 * @property {(enabled: bool) => void} setAnimationEnabled
 * @property {() => *} getIndex
 */

/**
 * @typedef {Object} DropInfo
 * @property {{type, value}} item Dropped item.
 * @property {{index: number, sourceId: string}} destination Destination of dropped item.
 * @property {{index: number, sourceId: string}} source Source of dropped item.
 */

export default {
  provide() {
    return {
      [getSourceIdName]: () => this.computedSourceId,
      [getDroppableApiName]: () => this.droppableApi,
    };
  },

  props: {
    acceptDrop: Function,
    sourceId: String,
    sourceName: {
      type: String,
      required: true,
    },
    disabled: Boolean,
    axis: {
      type: String,
      default: 'vertical',
      validate: x => x == 'vertical' || x == 'horizontal',
    },
    layoutInteraction: {
      type: String,
      default: 'move',
      validate: x => ['move', 'grid-line'].includes(x),
    },
    reorderOnly: Boolean,
  },

  data() {
    return {
      paused: false,
      draggingItem: null,
      draggingItemAccepted: true,
      anyDraggingItem: null,
      dropIndex: 0,
      dragMode: null,
      impact: null,
      draggingPos: null,
      isActive: false,
      offsetActive: false,
      relevantDrag: false,
    };
  },

  computed: {
    computedSourceId() {
      return this.sourceId || this.$id('source-id');
    },

    sourceInterface() {
      return {
        sourceId: this.computedSourceId,
        sourceName: this.sourceName,
        axis: this.axis,
        layoutInteraction: this.layoutInteraction,
        reorderOnly:
          this.reorderOnly ||
          // future improvement: grid-line layout interaction is not compatible
          // with move, so we disable drag and drop betwen droppables for now
          this.layoutInteraction !== 'move',
        handleDrop: this.handleDrop,
        handleDropOut: this.handleDropOut,
        handleValidateDrop: this.handleValidateDrop,
      };
    },

    isDropValid() {
      return !!(this.isActive && this.draggingItemAccepted);
    },
  },

  watch: {
    computedSourceId(val, old) {
      defaultDragDropManager.unregisterSource(old, this.sourceInterface);
      defaultDragDropManager.registerSource(val, this.sourceInterface);
    },

    sourceInterface(val, old) {
      if (val.draggableKey !== old.draggableKey) {
        defaultDragDropManager.unregisterSource(old.sourceId);
      }
      defaultDragDropManager.registerSource(val.sourceId, val);
    },

    isActive(val) {
      if (val) {
        window.addEventListener('scroll', this.handleWindowScroll);
      } else {
        window.removeEventListener('scroll', this.handleWindowScroll);
      }
    },
  },

  created() {
    defaultDragDropManager.registerSource(
      this.computedSourceId,
      this.sourceInterface
    );

    this.draggables = {};
    this.droppableApi = {
      /**
       * Register a draggable inside this droppable.
       *
       * @param {string} key
       * @param {DraggableInterface} draggable
       */
      registerDraggable: (key, draggable) => {
        this.draggables[key] = draggable;
      },

      /**
       * Register a draggable inside this droppable.
       *
       * @param {string} key
       * @param {DraggableInterface} draggable
       */
      unregisterDraggable: (key, draggable) => {
        if (this.draggables[key] == draggable) {
          delete this.draggables[key];
        }
      },
    };

    this.$_stateUnsubscribe = defaultDragDropManager.stateSubscribe(
      this.$_handleStateUpdate
    );
  },

  beforeDestroy() {
    if (this.$_stateUnsubscribe) {
      this.$_stateUnsubscribe();
    }
    defaultDragDropManager.droppableMouseOut(this.computedSourceId);
    defaultDragDropManager.unregisterSource(this.computedSourceId);
    this.$_updateDropLine(null);
    window.removeEventListener('scroll', this.handleWindowScroll);
  },

  methods: {
    $_handleStateUpdate(state) {
      const thisDroppable =
        state.dropDesc && state.dropDesc.sourceId === this.computedSourceId;

      this.isActive = thisDroppable;

      this.dragMode =
        state.dragInteraction === INTERACTION_MOUSE ? 'MOUSE' : 'STEP';

      this.anyDraggingItem = state.dragItem;
      this.relevantDrag = !!state.dragItem;
      this.impact = state.impact;
      this.draggingPos = state.draggingPos;

      if (state.phase === PHASE_DRAGGING && thisDroppable) {
        // item dragged in
        if (this.draggingItem != state.dragItem) {
          this.$_startDraggingResponse(state.dragItem, state.dropDesc);
        }

        // move items out of the way of target index
        if (
          state.dropDesc &&
          state.dropDesc.sourceId === this.computedSourceId
        ) {
          if (this.dropIndex != state.dropDesc.index) {
            this.dropIndex = state.dropDesc.index;
            this.$_updatePositions();
          }
        }
      }

      // no longer dragging in this droppable
      if (
        state.phase === PHASE_DRAGGING &&
        this.draggingItem &&
        !thisDroppable
      ) {
        this.draggingItem = null;
        this.dropIndex = 0;
        this.$_updatePositions();
      }

      this.paused = state.phase === PHASE_DROP_ANIMATING;

      // dragging ended
      if (state.phase === PHASE_IDLE && this.offsetActive) {
        this.$_endDraggingResponse(state.dragItem);
      }
    },

    /**
     * Get the drop index from a mouse event.
     *
     * @private
     * @param {MouseEvent} e
     * @returns {number}
     */
    $_dropIndexFromMouseEvent(e) {
      const draggables = orderBy(Object.entries(this.draggables), ([, value]) =>
        value.getIndex()
      );

      // work out what index it would be dropped at
      const viewport = getViewportRect();
      let index = null;
      const mouse =
        this.axis === 'vertical'
          ? e.pageY - viewport.top
          : e.pageX - viewport.left;
      const mouseCrossAxis =
        this.axis === 'vertical'
          ? e.pageX - viewport.left
          : e.pageY - viewport.top;

      const axisFiltered =
        this.layoutInteraction == 'grid-line'
          ? draggables.filter(([, draggable]) => {
              /** @type {Rect} */
              const marginBox = draggable.getEntry().getDimensions()
                .pageUntransformed.marginBox;
              const crossAxisZone =
                this.axis === 'vertical'
                  ? {
                      start: marginBox.left - viewport.left,
                      end: marginBox.left + marginBox.width - viewport.left,
                    }
                  : {
                      start: marginBox.top - viewport.top,
                      end: marginBox.top + marginBox.height - viewport.top,
                    };
              if (
                mouseCrossAxis < crossAxisZone.start ||
                mouseCrossAxis > crossAxisZone.end
              ) {
                return false;
              }
              return true;
            })
          : draggables;

      // default index to first item in row
      if (axisFiltered.length > 0) {
        index = axisFiltered[0][1].getIndex();
        // special case, fix fencepost error when dragging from earlier in the
        // list to the very beginning of a later row
        if (
          this.anyDraggingItem.descriptor.sourceId != this.computedSourceId ||
          index > this.anyDraggingItem.descriptor.index
        ) {
          index--;
        }
      }

      axisFiltered.forEach(([key, draggable]) => {
        if (key === this.anyDraggingItem.draggableKey) {
          return;
        }
        const clientRect = draggable.getElement().getBoundingClientRect();
        const centerpoint =
          this.axis === 'vertical'
            ? clientRect.top + clientRect.height / 2
            : clientRect.left + clientRect.width / 2;
        if (mouse > centerpoint) {
          index = draggable.getIndex();
          if (
            this.anyDraggingItem.descriptor.sourceId != this.computedSourceId ||
            index < this.anyDraggingItem.descriptor.index
          ) {
            index = index + 1;
          }
        }
      });

      return index;
    },

    /**
     * Displace draggables to match impact.
     *
     * @private
     */
    $_updatePositions() {
      if (!this.impact) {
        Object.values(this.draggables).forEach(draggable => {
          draggable.setOffset(new Point(0, 0));
        });
        this.$_updateDropLine(null);
        return;
      }

      // TODO: update positions when a draggable is registered or its index changed
      Object.entries(this.draggables).forEach(([key, draggable]) => {
        if (this.draggingItem && key === this.draggingItem.draggableKey) return;
        const displaced = this.impact.displaced.includes(key);
        if (this.draggingItem && displaced) {
          const diff = this.impact.displacedBy.px;
          draggable.setOffset(new Point(diff.x, diff.y));
        } else {
          draggable.setOffset(new Point(0, 0));
        }
      });

      this.$_updateDropLine(this.draggingItem, this.draggingPos);
    },

    /**
     * Start responding to an item dragged in to zone.
     *
     * @private
     * @param {DraggableEntry} info
     * @param {DropDesc} dropDesc
     */
    $_startDraggingResponse(info, dropDesc) {
      this.draggingItem = info;
      this.offsetActive = true;
      this.draggingItemAccepted = defaultDragDropManager.canDrop(
        info.descriptor,
        dropDesc
      );
      if (info.descriptor.sourceId == this.computedSourceId) {
        this.$_updatePositions();
      }
      this.$_eachDraggable(draggable => draggable.getElement().offsetHeight);
      this.$_eachDraggable(
        (draggable, key) =>
          key != this.draggingItem.draggableKey &&
          draggable.setAnimationEnabled(true)
      );
    },

    /**
     * Stop responding (dragging ended) and return to normal.
     *
     * @private
     */
    $_endDraggingResponse() {
      this.draggingItem = null;
      this.offsetActive = false;
      this.$_eachDraggable(draggable => draggable.setAnimationEnabled(false));
      // invalidate all in a single step to avoid layout thrashing
      this.$_eachDraggable(draggable => draggable.getElement().offsetHeight);
      this.$_eachDraggable(draggable => draggable.setOffset(null));
      this.$_updateDropLine(null);
    },

    /**
     * Validate that a drop is valid
     *
     * @param {DraggableDescriptor} info
     * @param {DropDesc} dropDesc
     * @returns {boolean}
     */
    handleValidateDrop(info, dropDesc) {
      return (
        !this.acceptDrop ||
        !!this.acceptDrop(this.$_makeEventDetails(info, dropDesc))
      );
    },

    /**
     * Handle an item being dropped in this droppable.
     *
     * @param {DraggableDescriptor} info
     * @param {DropDesc} dropDesc
     * @returns {boolean}
     */
    handleDrop(info, dropDesc) {
      this.$emit('drop', this.$_makeEventDetails(info, dropDesc));
    },

    /**
     * Handle an item being dropped out of this droppable.
     *
     * @param {DraggableDescriptor} info
     * @param {DropDesc} dropDesc
     * @returns {boolean}
     */
    handleDropOut(info, dropDesc) {
      this.$emit('remove', this.$_makeEventDetails(info, dropDesc));
    },

    /**
     * Execute the provided function for each draggable in this.draggables.
     *
     * @private
     * @param {(draggable: DraggableInterface, key: string) => any} fn
     */
    $_eachDraggable(fn) {
      for (const key in this.draggables) {
        fn(this.draggables[key], key);
      }
    },

    /**
     * Create event details from DraggableDescriptor and DropDesc.
     *
     * @private
     * @param {DraggableDescriptor} info
     * @param {DropDesc} dropDesc
     * @returns {DropInfo}
     */
    $_makeEventDetails(info, dropDesc) {
      return {
        item: {
          type: info.type,
          value: info.value,
        },
        destination: {
          index: dropDesc.index,
          sourceId: dropDesc.sourceId,
        },
        source: {
          index: info.index,
          sourceId: info.sourceId,
        },
      };
    },

    /**
     * Reposition drop line
     *
     * @param {?DraggableEntry} entry Item we are dragging. Null to hide drop line.
     * @param {{px: Point}} draggingPos
     */
    $_updateDropLine(entry, draggingPos) {
      // vue3: use portals or return from render()
      if (!entry || this.layoutInteraction === 'move') {
        if (this.dropLine) {
          this.dropLine.remove();
          this.dropLine = null;
        }
        return;
      }
      if (!this.dropLine) {
        const el = document.createElement('div');
        this.dropLine = el;
        Object.assign(el.style, {
          position: 'fixed',
        });
        document.body.appendChild(el);
      }
      this.dropLine.className = `tui-droppable__dropLine tui-droppable__dropLine--${this.axis}`;
      const lengthName = this.axis == 'vertical' ? 'width' : 'height';
      const viewport = getViewportRect();
      Object.assign(this.dropLine.style, {
        top: draggingPos.px.y - viewport.top + 'px',
        left: draggingPos.px.x - viewport.left + 'px',
        [lengthName]:
          entry.getDimensions().pageUntransformed.marginBox[lengthName] + 'px',
      });
    },

    /**
     * @param {MouseEvent} e
     */
    handleMouseEnter(e) {
      if (
        !this.disabled &&
        !this.paused &&
        this.anyDraggingItem &&
        this.dragMode === INTERACTION_MOUSE
      ) {
        const valid = defaultDragDropManager.canDrop(
          this.anyDraggingItem.descriptor,
          {
            sourceId: this.computedSourceId,
            index: null,
          }
        );
        // don't react if the item can't be dropped in this list
        if (valid) {
          const index = this.$_dropIndexFromMouseEvent(e);
          defaultDragDropManager.setDropAt(this.computedSourceId, index);
        }
      }
    },

    handleMouseLeave() {
      if (this.dragMode === INTERACTION_MOUSE) {
        defaultDragDropManager.droppableMouseOut(this.computedSourceId);
      }
    },

    handleMouseMove(e) {
      if (
        this.anyDraggingItem &&
        !this.disabled &&
        !this.paused &&
        this.dragMode === 'MOUSE'
      ) {
        const valid = defaultDragDropManager.canDrop(
          this.anyDraggingItem.descriptor,
          {
            sourceId: this.computedSourceId,
            index: null,
          }
        );
        if (valid) {
          const index = this.$_dropIndexFromMouseEvent(e);
          defaultDragDropManager.setDropAt(this.computedSourceId, index);
        }
      }
    },

    handleWindowScroll() {
      this.$_updateDropLine(this.draggingItem, this.draggingPos);
    },
  },

  render(h) {
    const nativeEvents = {
      mouseenter: this.handleMouseEnter,
      mouseleave: this.handleMouseLeave,
      mousemove: this.handleMouseMove,
    };

    let dropTarget = h('div', { style: { display: 'none' } });
    if (
      this.draggingItem &&
      this.draggingPos &&
      this.layoutInteraction === 'move' &&
      this.dragMode !== 'STEP' &&
      this.$el
    ) {
      const pos = this.draggingPos.px;
      const pageBox = this.draggingItem.getDimensions().pageUntransformed;
      const op = this.$el.offsetParent;
      const opPos = getDocumentPosition(op);
      const top = pos.y - opPos.y + pageBox.margin.top;
      const left = pos.x - opPos.x + pageBox.margin.left;
      dropTarget = h('div', {
        style: {
          position: 'absolute',
          width: pageBox.borderBox.width + 'px',
          height: pageBox.borderBox.height + 'px',
          top: '0px',
          left: '0px',
          transform: `translate(${left}px, ${top}px)`,
        },
        attrs: {
          'data-tui-droppable-location-indicator': true,
        },
      });
    }

    const placeholder =
      this.draggingItem &&
      this.layoutInteraction === 'move' &&
      h('div', {
        style: {
          height: this.draggingItem.getDimensions().size.height + 'px',
        },
        attrs: {
          'data-tui-draggable-placeholder': true,
        },
      });

    return this.$scopedSlots.default({
      attrs: {
        'data-tui-droppable-active': this.isActive,
      },
      events: nativeEvents,
      nativeEvents,
      dragging: !!this.relevantDrag,
      isActive: this.isActive,
      isDropValid: this.isDropValid,
      dropTarget,
      placeholder,
    });
  },
};
</script>

<style lang="scss">
.tui-droppable {
  &__dropLine {
    z-index: var(--zindex-draggable);
    background: var(--color-secondary);
    pointer-events: none;

    &--vertical {
      height: var(--border-width-normal);
      margin-top: calc(var(--border-width-normal) * -0.5);
    }

    &--horizontal {
      width: var(--border-width-normal);
      margin-left: calc(var(--border-width-normal) * -0.5);
    }
  }
}
[data-tui-droppable-location-indicator] {
  background-color: var(--color-neutral-5);
}
</style>
