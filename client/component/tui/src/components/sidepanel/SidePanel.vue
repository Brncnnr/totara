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

  @author Dave Wallace <dave.wallace@totaralearning.com>
  @module tui
-->

<template>
  <div
    ref="sidePanel"
    class="tui-sidePanel"
    :class="{
      'tui-sidePanel--animated': animated,
      'tui-sidePanel--flush': flush,
      'tui-sidePanel--overflows': overflows,
      'tui-sidePanel--sticky': sticky,
      'tui-sidePanel--hasButtonControl': showButtonControl,
      'tui-sidePanel--open': isOpen && !closing,
      'tui-sidePanel--closed': !isOpen && !opening,
      'tui-sidePanel--opening': opening,
      'tui-sidePanel--closing': closing,
      'tui-sidePanel--ltr': direction === 'ltr',
      'tui-sidePanel--rtl': direction === 'rtl',
    }"
    :style="{
      minHeight: minHeight ? minHeight + 'px' : 'initial',
    }"
  >
    <div
      v-if="direction === 'ltr'"
      ref="sidePanel__inner"
      class="tui-sidePanel__inner"
    >
      <div ref="sidePanel__content" class="tui-sidePanel__content">
        <slot ref="removableContent" />
      </div>
    </div>

    <ButtonIcon
      v-if="showButtonControl"
      :disabled="buttonDisabled"
      :aria-disabled="buttonDisabled"
      :aria-label="$str('sidepanel', 'totara_core')"
      :aria-expanded="isOpen ? 'true' : 'false'"
      class="tui-sidePanel__outsideClose"
      @click.prevent="isOpen ? collapse() : expand()"
    >
      <CollapseIcon v-if="isOpen" />
      <ExpandIcon v-else />
    </ButtonIcon>

    <div
      v-if="direction === 'rtl'"
      ref="sidePanel__inner"
      class="tui-sidePanel__inner"
    >
      <div
        ref="sidePanel__content"
        class="tui-sidePanel__content"
        :style="{ width: contentWidth }"
      >
        <slot ref="removableContent" />
      </div>
    </div>
  </div>
</template>

<script>
import { waitForTransitionEnd } from 'tui/dom/transitions';
import CollapseIcon from 'tui/components/icons/Collapse';
import ExpandIcon from 'tui/components/icons/Expand';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';

export default {
  components: {
    ButtonIcon,
    CollapseIcon,
    ExpandIcon,
  },

  props: {
    /**
     * Whether the SidePanel should is intended to be opened from the left or
     * right side of a page. Expected values are 'ltr' and 'rtl'
     **/
    direction: {
      type: String,
      default: 'ltr',
      validator: str => ['ltr', 'rtl'].includes(str),
    },

    /**
     * Whether the SidePanel's inner content should have a fixed width when its state is expanding or collapsing
     * When set to true, a fixed-width will be applied, preventing reflow of SidePanel contents during transitions
     **/
    fixContentWidth: {
      type: Boolean,
      default: true,
    },

    /**
     * Whether the SidePanel should be open when it is first rendered
     **/
    initiallyOpen: {
      type: Boolean,
      default: false,
    },

    /**
     * Whether transition lifecycles should be managed for CSS-based animations
     **/
    animated: {
      type: Boolean,
      default: true,
    },

    /**
     * Whether the SidePanel should remain wholly in the viewport when a long
     * page is scrolled
     **/
    sticky: {
      type: Boolean,
      default: true,
    },

    /**
     * Pixel based value that the SidePanel will respect with short viewports
     **/
    minHeight: {
      type: [Number, String],
      default: 'initial', // assumed a px based calculation
    },

    /**
     * Whether to assume the SidePanel is flush to the page header and footer
     **/
    flush: {
      type: Boolean,
      default: true,
    },

    /**
     * Whether the SidePanel inner container should invoke a scrollbar if its
     * contents exceed its available height
     **/
    overflows: {
      type: Boolean,
      default: true,
    },

    /**
     * Whether to render the expand/collapse SidePanel toggle control
     **/
    showButtonControl: {
      type: Boolean,
      default: true,
    },
  },

  data() {
    return {
      /**
       * Toggle value indicating the SidePanel is completely open or closed.
       * Includes after an expand or collapse transition lifecycle has completed,
       * or immediately in the case where no transition is used
       **/
      isOpen: false,

      /**
       * Toggle value indicating the SidePanel is currently moving between a
       * completely open state to a completely closed state
       **/
      closing: false,

      /**
       * Toggle value indicating the SidePanel is currently moving between a
       * completely closed state to a completely open state
       **/
      opening: false,

      /**
       * Width of the content preventing reflow of SidePanel contents during transitions
       **/
      contentWidth: 'auto',
    };
  },

  computed: {
    shouldBeOpen() {
      return this.initiallyOpen;
    },

    buttonDisabled() {
      return this.opening || this.closing;
    },
  },

  watch: {
    shouldBeOpen(open) {
      if (open) {
        this.expand();
      } else {
        this.collapse();
      }
    },
  },
  mounted() {
    // expand as soon as the SidePanel mounts, if configured so
    if (this.shouldBeOpen) {
      this.expand();
    }
  },

  methods: {
    expand() {
      if (this.isOpen || this.opening || this.closing) {
        return;
      }
      this.opening = true;
      this.$emit('sidepanel-expanding');

      this.contentWidth = 'auto';

      this.$_animate().then(() => {
        this.opening = false;
        this.isOpen = true;

        this.$emit('sidepanel-expanded');
      });
    },

    collapse() {
      if (!this.isOpen || this.opening || this.closing) {
        return;
      }

      this.closing = true;
      this.$emit('sidepanel-collapsing');

      this.contentWidth = this.getContentWidth();

      this.$_animate().then(() => {
        this.closing = false;
        this.isOpen = false;

        this.$emit('sidepanel-collapsed');
      });
    },

    getContentWidth() {
      if (this.fixContentWidth)
        return (
          this.$refs.sidePanel__content.getBoundingClientRect().width + 'px'
        );
    },

    async $_animate() {
      if (this.animated) {
        // only interested in tracking transitions on content holding container
        const transitionEls = [this.$refs.sidePanel__content].filter(Boolean);

        await waitForTransitionEnd(transitionEls);
      }
    },
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "sidepanel"
  ]
}
</lang-strings>

<style lang="scss">
:root {
  --tui-sidepanel-button-width: 30px;
  --tui-sidepanel-button-height: 66px;
  --tui-sidepanel-border-width: 1px;
}

.tui-sidePanel {
  display: flex;
  align-items: flex-start;
  height: 100%;

  // inner content alignment
  &--rtl,
  .dir-rtl .tui-sidePanel--ltr & {
    justify-content: flex-end;
  }
  &--ltr,
  .dir-rtl .tui-sidePanel--rtl & {
    justify-content: flex-start;
  }

  &--sticky {
    position: sticky;
    top: 0;
    max-height: 100vh;
    overflow: hidden;

    .ie &,
    .msedge & {
      position: relative;
      top: auto;
      max-height: initial;
    }
  }

  &--sticky&--closed {
    overflow: visible;
  }

  /**
   * Close button, somewhat complicated by the SidePanel being configurably
   * bi-directional and both of those directions also requiring RTL support
   **/
  @mixin attrs-from-right() {
    margin-right: -1px;
    border-right-width: 0;
    border-left-width: var(--tui-sidepanel-border-width);
    border-radius: var(--btn-radius) 0 0 var(--btn-radius);

    &:hover,
    &:active,
    &:active:focus,
    &:active:hover {
      border-right-width: 0;
      border-left-width: var(--tui-sidepanel-border-width);
      box-shadow: -2px 1px 4px 0 rgba(0, 0, 0, 0.2);
    }

    // FlexIcon
    .tui-iconBtn__icon {
      transform: rotate(90deg);
    }
  }
  @mixin attrs-from-left() {
    margin-left: -1px;
    border-right-width: 1px;
    border-left-width: 0;
    border-radius: 0 var(--btn-radius) var(--btn-radius) 0;

    &:hover,
    &:active,
    &:active:focus,
    &:active:hover {
      border-right-width: var(--tui-sidepanel-border-width);
      border-left-width: 0;
      box-shadow: 2px 1px 4px 0 rgba(0, 0, 0, 0.2);
    }
    // FlexIcon
    .tui-iconBtn__icon {
      transform: rotate(-90deg);
    }
  }

  &__outsideClose {
    .tui-sidePanel--sticky & {
      position: sticky;
      top: calc(50% - (var(--tui-sidepanel-button-height) / 2));
    }

    .ie & {
      // height, position and scrolling will degrade in IE11, so the toggle
      // button needs a more appropriate location than "the middle" of the
      // SidePanel, which could be very tall in IE11
      top: auto;
      align-self: flex-start;
      max-width: var(--tui-sidepanel-button-width);
      max-height: var(--tui-sidepanel-button-height);
      margin-top: var(--gap-8);
    }

    flex-grow: 0;
    min-width: var(--tui-sidepanel-button-width);
    min-height: var(--tui-sidepanel-button-height);
    margin-bottom: -1px;
    padding: var(--gap-6) var(--gap-1);
    background-color: var(--color-neutral-3);
    border-color: var(--color-neutral-5);

    .tui-sidePanel--rtl &,
    .dir-rtl .tui-sidePanel--ltr & {
      @include attrs-from-right();
    }

    .tui-sidePanel--ltr &,
    .dir-rtl .tui-sidePanel--rtl & {
      @include attrs-from-left();
    }
  }

  /**
   * A wrapper for content container, which helps with transitions on width
   * while overflowing content is still visible, and providing whitespace
   * between content and the edges of the SidePanel
   **/
  &__inner {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    flex-shrink: 1;
    width: 100%;
    height: 100%;
    background-color: var(--color-neutral-3);
    border: var(--tui-sidepanel-border-width) solid var(--color-neutral-5);

    .tui-sidePanel--flush & {
      border-top: none;
      border-bottom: none;
    }

    .ie & {
      /* put the border back, it usually wouldn't reach the footer, only on really small resources, and would otherwise look chopped off */
      border-bottom: var(--tui-sidepanel-border-width) solid
        var(--color-neutral-5);
    }

    .tui-sidePanel--open.tui-sidePanel--overflows & {
      overflow-y: auto;
    }

    .tui-sidePanel--closed & {
      max-width: 1px;
      padding-right: 0;
      padding-left: 0;
      border-left: 0;
    }

    // we have to cut off overflow during these states otherwise we'll bump
    // page scrollbars, or a containing element scrollbars
    .tui-sidePanel--closed &,
    .tui-sidePanel--closing &,
    .tui-sidePanel--opening & {
      overflow: hidden;
    }
  }

  /**
   * Transitioned container for arbitrary SidePanel content
   **/
  &__content {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    overflow: hidden;

    .ie & {
      height: 100%;
    }

    .tui-sidePanel--closed &,
    .tui-sidePanel--closing & {
      opacity: 0;
    }

    .tui-sidePanel--closed & {
      display: none;
    }

    .tui-sidePanel--open &,
    .tui-sidePanel--opening & {
      opacity: 1;
    }

    .tui-sidePanel--animated & {
      transition: opacity var(--transition-sidepanel-content-duration)
        var(--transition-sidepanel-content-function);
    }

    .tui-sidePanel--open.tui-sidePanel--overflows & {
      overflow-y: auto;
    }
  }
}
</style>
