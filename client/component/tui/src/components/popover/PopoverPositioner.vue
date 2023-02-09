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

<template>
  <transition
    :name="transition && 'tui-popoverPositioner--transition-' + transition"
    @enter="transitionEnter"
    @after-enter="transitionEnterEnd"
    @enter-cancelled="transitionEnterEnd"
    @leave="transitionLeave"
    @after-leave="transitionLeaveEnd"
    @leave-cancelled="transitionLeaveEnd"
  >
    <div
      v-show="shouldBeOpen"
      ref="popover"
      class="tui-popoverPositioner"
      :class="[
        transition && 'tui-popoverPositioner--transition-' + transition,
        isFixed && 'tui-popoverPositioner--fixed',
      ]"
      :style="style"
    >
      <slot
        :side="computedSide || position"
        :arrow-distance="arrowDistance"
        :is-open="shouldBeOpen"
      />
    </div>
  </transition>
</template>

<script>
import Vue from 'vue';
import ResizeObserver from 'tui/polyfills/ResizeObserver';
import { throttle } from 'tui/util';
import {
  getDocumentPosition,
  getViewportRect,
  getBox,
  getBoundingClientRect,
  getContainingBlockInfo,
} from 'tui/dom/position';
import { getClosestScrollable } from 'tui/dom/scroll';
import { position } from 'tui/lib/popover';
import { Point, Size, Rect } from 'tui/geometry';
import pending from 'tui/pending';
import { getTabbableElements } from 'tui/dom/focus';

const ContextType = {
  CONTAINED: 'contained',
  UNCONTAINED: 'uncontained',
};

/**
 * Wrapper for pending that removes the task while the page is not visible
 * (as transitions are paused when pages are hidden).
 *
 * @param {string} name
 * @returns {() => void}
 */
function pendingTransition(name) {
  let done = pending(name);
  const handler = () => {
    if (document.visibilityState === 'visible') {
      done(); // in case we recieve 2 visible events in a row
      done = pending(name);
    } else {
      done();
    }
  };
  window.addEventListener('visibilitychange', handler);
  return () => {
    window.removeEventListener('visibilitychange', handler);
    done();
  };
}

export default {
  props: {
    position: String,
    open: Boolean,
    /* eslint-disable-next-line vue/require-prop-types */
    referenceElement: {},
    transition: {
      type: String,
      default: 'default',
    },
    // allow popover to slide along the side in preference to moving to different side
    // if this is true, it will still slide if it can't fit on any side
    preferSlide: Boolean,
    // match the width of the reference element
    matchWidth: Boolean,
    // uncontained injects the element on the root level
    contextMode: {
      type: String,
      default: ContextType.CONTAINED,
      validator: function(value) {
        return Object.values(ContextType).includes(value);
      },
    },
  },

  data() {
    return {
      location: new Point(0, 0),
      size: new Size(0, 0),
      referenceWidth: null,
      innerPadding: 0,
      shouldBeOpen: false,
      computedSide: null,
      arrowDistance: 0,
      isFixed: false,
    };
  },

  computed: {
    style() {
      const left = Math.round(this.location.x);
      const top = Math.round(this.location.y);
      const style = {
        // use translate rather than top/left to avoid popover getting
        // compressed if it's near the right edge of the viewport
        transform: `translate3d(${left}px, ${top}px, 0)`,
      };

      if (this.matchWidth && this.referenceWidth != null) {
        style.width = this.referenceWidth + 'px';
      }

      return style;
    },
  },

  watch: {
    open: {
      immediate: true,
      handler(open) {
        if (open) {
          Vue.nextTick(() => {
            this.shouldBeOpen = true;
            this.$_setupOpen();
            this.handleResize();
          });
        } else {
          this.$_closeCleanup();
          this.shouldBeOpen = false;
        }
      },
    },

    referenceElement() {
      if (this.shouldBeOpen) {
        this.handleResize();
      }
    },

    contextMode() {
      this.$_closeCleanup();
    },
  },

  mounted() {
    this.handleResizeThrottled = throttle(this.handleResize, 150);

    this.resizeObserver = new ResizeObserver(this.handleResizeThrottled);
    this.resizeObserver.observe(this.$el);
    if (this.referenceElement) {
      this.resizeObserver.observe(this.referenceElement);
    }
  },

  destroyed() {
    this.$_closeCleanup();
    this.resizeObserver.disconnect();

    if (this.enterDone) {
      this.enterDone();
    }
    if (this.leaveDone) {
      this.leaveDone();
    }
  },

  methods: {
    updatePosition() {
      const refEl = this.referenceElement;
      if (!refEl) return;

      if (this.matchWidth && this.referenceWidth != refEl.offsetWidth) {
        this.referenceWidth = refEl.offsetWidth;
      }

      let refRect;
      let viewport = null;
      if (this.isFixed) {
        const containingBlock = getContainingBlockInfo(this.$el, {
          position: 'fixed',
        });
        if (process.env.NODE_ENV !== 'production') {
          const referenceContainingBlock = getContainingBlockInfo(refEl, {
            position: 'fixed',
          });
          if (containingBlock.el != referenceContainingBlock.el) {
            console.warn(
              '[PopoverPositioner] Reference element and PopoverPositioner are not in the same containing block.'
            );
            console.log('Reference element', refEl);
            console.log(
              'Reference element containing block',
              referenceContainingBlock.el
            );
            console.log('PopoverPositioner', this.$el);
            console.log(
              'PopoverPositioner containing block',
              containingBlock.el
            );
          }
        }
        refRect = getBox(refEl).borderBox;
        viewport = new Rect(
          0,
          0,
          containingBlock.rect.width,
          containingBlock.rect.height
        );
      } else {
        if (!refEl.offsetParent) {
          return;
        }
        if (
          process.env.NODE_ENV !== 'production' &&
          this.$el.offsetParent &&
          refEl.offsetParent != this.$el.offsetParent &&
          this.contextMode === ContextType.CONTAINED
        ) {
          console.warn(
            '[PopoverPositioner] Reference element and PopoverPositioner are not in the same offset parent.'
          );
          console.log('Reference element', refEl);
          console.log('Reference element offset parent', refEl.offsetParent);
          console.log('PopoverPositioner', this.$el);
          console.log('PopoverPositioner offset parent', this.$el.offsetParent);
        }

        // Switch depending on context mode
        let offsetRef =
          this.contextMode === ContextType.UNCONTAINED
            ? document.body
            : refEl.offsetParent;

        // using offsetTop etc doesn't account for scrolling of intermediate elements
        refRect = getBoundingClientRect(refEl).sub(
          getBoundingClientRect(offsetRef).getPosition()
        );
        const offsetParentPosition = getDocumentPosition(offsetRef);
        viewport = getViewportRect().sub(offsetParentPosition);
      }

      const pos = position({
        position: this.position.split('-'),
        ref: refRect,
        viewport,
        size: this.size,
        padding: this.innerPadding,
      });

      this.computedSide = pos.side;
      this.location = pos.location;
      this.arrowDistance = pos.arrowDistance;
    },

    handleResize() {
      if (!this.shouldBeOpen) {
        return;
      }
      Vue.nextTick(() => {
        this.size = new Size(this.$el.offsetWidth, this.$el.offsetHeight);
        // padding is required to be equal on all sides
        const child = this.$el.children[0];
        this.innerPadding = child
          ? (this.size.width +
              this.size.height -
              (child.offsetWidth + child.offsetHeight)) /
            4
          : 0;
        this.updatePosition();
      });
    },

    $_handleDocumentKeyDown(e) {
      switch (e.key) {
        case 'Tab':
          this.$_customFocus(this.$refs.popover, e);
          break;
      }
    },

    $_customFocus(el, e) {
      const tabbableElements = getTabbableElements(el);
      if (tabbableElements.length === 0) {
        return;
      }

      // Check if referenceElement is the active element
      const isRefElActive = document.activeElement === this.referenceElement;

      if (isRefElActive && !e.shiftKey) {
        tabbableElements[0].focus();
        e.preventDefault();
      } else {
        const index = tabbableElements.indexOf(document.activeElement);

        if (!e.shiftKey) {
          // Focus on the element after referenceElement if reached the end of tabbableElements
          if (index === tabbableElements.length - 1) {
            const allElements = getTabbableElements(document.body);
            const afterRefEl = allElements.indexOf(this.referenceElement) + 1;

            if (allElements[afterRefEl]) {
              allElements[afterRefEl].focus();
              e.preventDefault();
            }
          }
        } else {
          // Focus on referenceElement if tabbing has reached the beginning of tabbableElements (in reverse)
          if (index === 0) {
            this.referenceElement.focus();
            e.preventDefault();
          }

          const allElements = getTabbableElements(document.body);
          const afterRefEl = allElements.indexOf(this.referenceElement) + 1;

          // Focus on the last element in tabbableElements if activeElement is the element after
          // referenceElement (in reverse)
          if (
            allElements[afterRefEl] &&
            document.activeElement === allElements[afterRefEl]
          ) {
            tabbableElements[tabbableElements.length - 1].focus();
            e.preventDefault();
          }
        }
      }
    },

    transitionEnter() {
      if (this.enterDone) {
        this.enterDone();
      }
      this.enterDone = pendingTransition('popover-positioner-enter');
    },

    transitionEnterEnd() {
      if (this.enterDone) {
        this.enterDone();
        this.enterDone = null;
      }
    },

    transitionLeave() {
      if (this.leaveDone) {
        this.leaveDone();
      }
      this.leaveDone = pendingTransition('popover-positioner-leave');
    },

    transitionLeaveEnd() {
      if (this.leaveDone) {
        this.leaveDone();
        this.leaveDone = null;
      }
    },

    $_setupOpen() {
      this.$_closeCleanup();
      this.$_activateContextMode();

      window.addEventListener('resize', this.handleResizeThrottled);
      window.addEventListener('scroll', this.handleResizeThrottled, {
        passive: true,
      });

      this.isFixed = this.$_useFixedPositioning();

      // Add keydown event for uncontained mode to handle accessibility
      if (this.contextMode === ContextType.UNCONTAINED) {
        window.addEventListener('keydown', this.$_handleDocumentKeyDown);
      }

      this.scrollableContainers = [];
      let scrollable = getClosestScrollable(this.referenceElement);
      while (scrollable) {
        this.scrollableContainers.push(scrollable);
        scrollable.addEventListener('scroll', this.handleResize);
        scrollable = getClosestScrollable(scrollable.parentNode);
      }
    },

    $_closeCleanup() {
      window.removeEventListener('resize', this.handleResizeThrottled);
      window.removeEventListener('scroll', this.handleResizeThrottled);

      if (this.scrollableContainers) {
        this.scrollableContainers.forEach(x =>
          x.removeEventListener('scroll', this.handleResize)
        );
        this.scrollableContainers = null;
      }

      window.removeEventListener('keydown', this.$_handleDocumentKeyDown);

      this.$_removeElements();
    },

    $_useFixedPositioning() {
      return (
        this.contextMode === ContextType.CONTAINED &&
        !!this.$el.closest('.tui-modalContent') &&
        !this.$el.closest('.tui-weka')
      );
    },

    /**
     * If context mode is uncontained then append popover in the root
     */
    $_activateContextMode() {
      if (this.contextMode === ContextType.UNCONTAINED) {
        this.$_uncontainedEl = this.$refs.popover;
        document.body.appendChild(this.$refs.popover);
      }
    },

    $_removeElements() {
      if (this.$_uncontainedEl) {
        this.$_uncontainedEl.remove();
        this.$_uncontainedEl = null;
      }
    },
  },
};
</script>

<style lang="scss">
.tui-popoverPositioner {
  // note: position: absolute + z-index triggers a new stacking context
  // this allows us to escape from overflow: hidden.
  // because stacking contexts can be nested, this will still work correctly
  // even inside modals, which have a higher z-index
  position: absolute;
  top: 0;
  /*!rtl:ignore*/
  left: 0;
  z-index: var(--zindex-popover);
  width: auto;
  @include tui-font-body();

  &--fixed {
    position: fixed;
  }

  &--transition-default {
    transition: opacity 0.2s;
  }

  &--transition-default-enter,
  &--transition-default-leave-to {
    opacity: 0;
  }

  &--transition-dropdown {
    transition: opacity 0s;
  }

  &--transition-dropdown-enter,
  &--transition-dropdown-leave-to {
    opacity: 0;
  }

  &--transition-dropdown-leave-to {
    transition: opacity 0.15s;
  }
}

.tui-modal--animated .tui-popoverPositioner {
  display: none;
}
.tui-modal--animated.tui-modal--in .tui-popoverPositioner {
  display: block;
}
</style>
