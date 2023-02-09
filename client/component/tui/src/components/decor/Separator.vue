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
    v-if="this.$slots['default']"
    class="tui-separator tui-separator--wrapped"
    :class="[
      normal ? 'tui-separator--normal' : '',
      thick ? 'tui-separator--thick' : '',
      spread ? 'tui-separator--spread' : '',
    ]"
  >
    <span class="tui-separator-content">
      <slot />
    </span>
    <hr class="sr-only" />
  </div>
  <hr
    v-else
    class="tui-separator tui-separator-rule"
    :class="[
      normal ? 'tui-separator--normal' : '',
      thick ? 'tui-separator--thick' : '',
      spread ? 'tui-separator--spread' : '',
    ]"
  />
</template>
<script>
export default {
  props: {
    /**
     * When provided, a modifier className is added to the element that
     * references a SCSS variable to set a normal visible horizontal rule
     **/
    normal: {
      type: Boolean,
      default: function() {
        return false;
      },
    },

    /**
     * When provided, a modifier className is added to the element that
     * references a SCSS variable to set a thicker visible horizontal rule
     **/
    thick: {
      type: Boolean,
      default: function() {
        return false;
      },
    },

    /**
     * When provided, a modifier className is added to the element that
     * references a SCSS variable to set increased vertical margins
     **/
    spread: {
      type: Boolean,
      default: function() {
        return false;
      },
    },
  },
};
</script>

<style lang="scss">
.tui-separator {
  margin: var(--gap-4) 0;

  &--spread {
    margin: var(--gap-8) 0;
  }

  // simple implementation using a horizontal rule element
  &-rule {
    height: var(--border-width-thin);
    line-height: var(--border-width-thin);
    background-color: var(--color-neutral-5);
    border: none;

    &.tui-separator--thick {
      height: var(--border-width-thick);
      line-height: var(--border-width-thick);
    }

    &.tui-separator--normal {
      height: var(--border-width-normal);
      line-height: var(--border-width-normal);
    }
  }

  // alternative implementation with wrapper markup to center slotted content
  // horizontal rule visible only to screen readers, the visual lines added with
  // pseudo selectors
  &--wrapped {
    display: flex;
    align-items: center;

    .tui-separator-content {
      margin: 0 var(--gap-4);
      white-space: nowrap;
      text-align: center;
    }

    &:before,
    &:after {
      width: 50%;
      height: var(--border-width-thin);
      line-height: var(--border-width-thin);
      background-color: var(--color-neutral-5);
      content: '';
    }

    &.tui-separator--thick:before,
    &.tui-separator--thick:after {
      height: var(--border-width-thick);
      line-height: var(--border-width-thick);
    }

    &.tui-separator--normal:before,
    &.tui-separator--normal:after {
      height: var(--border-width-normal);
      line-height: var(--border-width-normal);
    }
  }
}
</style>
