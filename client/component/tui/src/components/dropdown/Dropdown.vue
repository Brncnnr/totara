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

  @author Alvin Smith <alvin.smith@totaralearning.com>
  @author Simon Chester <simon.chester@totaralearning.com>
  @module tui
-->

<template>
  <div
    class="tui-dropdown"
    :class="{
      'tui-dropdown--open': isOpen,
      'tui-dropdown--disabled': disabled,
      'tui-dropdown--flexibleWidth': matchWidth,
      'tui-dropdown--fixedHeight': fixedHeight,
    }"
  >
    <div
      v-if="$scopedSlots.trigger"
      :id="$id('trigger')"
      ref="trigger"
      class="tui-dropdown__trigger"
    >
      <slot name="trigger" :toggle="toggle" :isOpen="isOpen" />
    </div>

    <component
      :is="inlineMenu ? 'passthrough' : 'PopoverPositioner'"
      v-if="!disabled && $scopedSlots.default"
      :position="position"
      :open="isOpen"
      :reference-element="$refs.trigger"
      :match-width="matchWidth"
      transition="dropdown"
      :context-mode="contextMode"
    >
      <div
        ref="dropdownMenu"
        class="tui-dropdown__menu"
        :class="{
          'tui-dropdown__menu--open': isOpen,
        }"
        :aria-hidden="!isOpen"
        :aria-labelledby="$id('trigger')"
        aria-orientation="vertical"
        :role="role"
      >
        <div
          ref="dropdownContent"
          class="tui-dropdown__content"
          :class="{
            'tui-dropdown__content--separator': separator,
          }"
        >
          <PropsProvider :provide="provide">
            <slot />
          </PropsProvider>
        </div>
      </div>
    </component>
  </div>
</template>

<script>
import PropsProvider from 'tui/components/util/PropsProvider';
import PopoverPositioner from 'tui/components/popover/PopoverPositioner';
import { getFocusableElements, getTabbableElements } from 'tui/dom/focus';
import { addListener, removeListener } from 'tui/dom/keyboard_stack';

const DEFAULT_CLOSE_OPTIONS = ['escape', 'outside'];

export default {
  components: {
    PropsProvider,
    PopoverPositioner,
  },

  props: {
    // eslint-disable-next-line vue/require-prop-types
    value: {
      default: null,
    },
    disabled: Boolean,
    role: {
      type: String,
      default: 'menu',
    },
    position: {
      type: String,
      default: 'bottom-left',
      validator: x =>
        ['top-right', 'top-left', 'bottom-left', 'bottom-right'].includes(x),
    },
    separator: {
      type: Boolean,
      default: true,
    },
    animation: {
      type: String,
      default: 'default',
    },
    multiple: Boolean,
    closeOnClick: {
      type: Boolean,
      default: true,
    },
    canClose: {
      type: [Array, Boolean],
      default: true,
    },
    open: Boolean,
    matchWidth: Boolean,
    /** Show the menu inline (rather than as a popover/overlay) */
    inlineMenu: Boolean,
    fixedHeight: Boolean,
    contextMode: {
      type: String,
      default: 'contained',
    },
  },

  data() {
    return {
      toggleOpen: false,
      activeNodeIndex: null,
    };
  },

  computed: {
    cancelOptions() {
      return typeof this.canClose === 'boolean'
        ? this.canClose
          ? DEFAULT_CLOSE_OPTIONS
          : []
        : this.canClose;
    },
    isOpen() {
      return this.open || this.toggleOpen;
    },
  },

  watch: {
    isOpen: {
      handler() {
        if (this.isOpen) {
          document.addEventListener('keydown', this.$_keyPress);
          document.addEventListener(
            'click',
            this.$_clickedOutsideCapture,
            true
          );
          document.addEventListener('click', this.$_clickedOutside);
          document.addEventListener('contextmenu', this.$_clickedOutside);
          addListener('Escape', this.$_handleEscape);
        } else {
          document.removeEventListener('keydown', this.$_keyPress);
          document.removeEventListener(
            'click',
            this.$_clickedOutsideCapture,
            true
          );
          document.removeEventListener('click', this.$_clickedOutside);
          document.removeEventListener('contextmenu', this.$_clickedOutside);
          removeListener('Escape', this.$_handleEscape);
          this.activeNodeIndex = null;
        }
      },
      immediate: true,
    },
  },

  beforeDestroy() {
    if (typeof document !== 'undefined') {
      document.removeEventListener('click', this.$_clickedOutsideCapture, true);
      document.removeEventListener('click', this.$_clickedOutside);
      document.removeEventListener('keydown', this.$_keyPress);
      document.removeEventListener('contextmenu', this.$_clickedOutside);
    }
  },

  methods: {
    provide() {
      return {
        props: {
          disabled: this.disabled,
        },
      };
    },

    $_clickedOutsideCapture(event) {
      if (!this.$refs.dropdownMenu) {
        return;
      }

      // store target for later so we can tell if it was inside the
      // menu if it gets removed from the document
      if (this.$refs.dropdownMenu.contains(event.target)) {
        this.targetInMenu = event.target;
      } else {
        this.targetInMenu = null;
      }
    },

    /**
     * Close dropdown if clicked outside.
     */
    $_clickedOutside(event) {
      if (!this.$refs.dropdownMenu) {
        return;
      }
      // work around bug in Bootstrap < 3.3.5: https://github.com/twbs/bootstrap/issues/16090
      if (event.target !== this.$refs.dropdownMenu) {
        if (!this.cancelOptions.includes('outside')) return;

        const targetInMenu =
          this.targetInMenu === event.target ||
          this.$refs.dropdownMenu.contains(event.target);

        if (
          !this.$refs.trigger ||
          // treat direct click on trigger div (*not* content) as click outside
          this.$refs.trigger === event.target ||
          !this.$refs.trigger.contains(event.target)
        ) {
          if (!this.closeOnClick) {
            // not close after click when we set the closeOnClick prop to false
            if (targetInMenu) {
              return;
            }
          }

          // return focus to the trigger if a dropdown item has focus.
          // check where the focus is to avoid returning focus to the trigger if
          // clicking on the item has placed focus elsewhere (e.g. a modal)
          if (
            this.$refs.dropdownMenu.contains(document.activeElement) ||
            // also handle the case where we click on something non-focusable
            // inside the dropdown (which just shifts focus to the body)
            (targetInMenu && document.activeElement == document.body)
          ) {
            this.$_focusTrigger();
          }
          this.dismiss();
        }
      }
    },

    /**
     * Focus the trigger.
     */
    $_focusTrigger() {
      if (!this.$refs.trigger) {
        return;
      }
      const tabbable = getTabbableElements(this.$refs.trigger)[0];
      if (tabbable) {
        tabbable.focus();
      }
    },

    /**
     * Check and prevent pressing escape key propagation when a dropdown is expanded
     *
     * @param {Object} event The (slightly modified) KeyboardEvent event to handle
     */
    $_handleEscape(event) {
      if (this.isOpen && this.cancelOptions.includes('escape')) {
        this.$_focusTrigger();
        this.dismiss();
        event.stopPropagation();
        event.preventDefault();
      }
    },

    /**
     * Keypress event that is bound to the document
     */
    $_keyPress(event) {
      if (!this.$refs.dropdownContent || !this.isOpen) {
        return;
      }

      let contentNodeList = getFocusableElements(this.$refs.dropdownContent);
      // filter out items that are children of other items, or are disabled
      contentNodeList = contentNodeList.filter(
        x =>
          !contentNodeList.some(y => x != y && y.contains(x)) &&
          x.ariaDisabled !== 'true'
      );
      const contentNodeCount = contentNodeList.length;
      switch (event.key) {
        case 'ArrowDown':
        case 'Down':
          event.preventDefault();
          if (this.activeNodeIndex === contentNodeCount - 1) break;

          this.activeNodeIndex =
            this.activeNodeIndex !== null ? this.activeNodeIndex + 1 : 0;
          if (contentNodeCount > 0) {
            contentNodeList[this.activeNodeIndex].focus();
          }
          break;
        case 'ArrowUp':
        case 'Up':
          event.preventDefault();
          if (!this.activeNodeIndex) break;

          this.activeNodeIndex -= 1;
          if (contentNodeCount > 0)
            contentNodeList[this.activeNodeIndex].focus();
          break;
        case 'Tab':
          if (this.activeNodeIndex === null && !event.shiftKey) {
            this.activeNodeIndex = 0;
            contentNodeList[this.activeNodeIndex].focus();
            event.preventDefault();
          } else if (this.isOpen) {
            this.dismiss();
          }
          break;
      }
    },

    dismiss() {
      this.toggleOpen = false;
      this.$emit('dismiss');
    },

    /**
     * Toggle dropdown if it's not disabled.
     */
    toggle() {
      if (this.disabled) return;

      if (!this.toggleOpen) {
        this.$emit('open');
        // if not active, toggle after the clickOutside event
        this.$nextTick(() => {
          const value = !this.toggleOpen;
          this.toggleOpen = value;
        });
      } else {
        this.toggleOpen = !this.toggleOpen;
      }
    },
  },
};
</script>

<style lang="scss">
.tui-dropdown {
  &__menu {
    z-index: var(--zindex-dropdown-menu);
    min-width: 15rem;
    max-width: 35rem;
    max-height: 40vh;
    overflow-y: auto;
    background-color: var(--dropdown-menu-bg-color);
    background-clip: padding-box;
    border: var(--border-width-thin) solid var(--dropdown-menu-border-color);
    box-shadow: var(--shadow-2);
  }

  &--flexibleWidth &__menu {
    min-width: 0;
    max-width: none;
  }

  &__content {
    display: flex;
    flex-direction: column;
    &--separator > * + *,
    // work around specificity issues with button
    &--separator > * + .tui-dropdownButton,
    &--separator > * + .tui-dropdownButton:hover,
    &--separator > * + .tui-dropdownButton:focus,
    &--separator > * + .tui-dropdownButton:active,
    &--separator > * + .tui-dropdownButton:focus:active {
      border-top: var(--border-width-thin) solid
        var(--dropdown-menu-border-color);
    }
  }

  &--fixedHeight {
    .tui-dropdown__content {
      height: 100%;
    }

    .tui-dropdown__menu {
      height: 38vh;
      overflow-y: visible;
    }
  }

  &--disabled {
    cursor: not-allowed;
    .tui-dropdown__trigger {
      pointer-events: none;
    }
  }
}
</style>
