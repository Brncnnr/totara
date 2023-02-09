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
  @module tui
-->

<template>
  <div
    v-focus-within
    class="tui-card"
    :class="[
      (clickable || url) && 'tui-card--clickable',
      noBorder && 'tui-card--noBorder',
      hasHoverShadow && 'tui-card--hasHoverShadow',
      hasShadow && 'tui-card--hasShadow',
    ]"
    @click="clickCard"
  >
    <a
      v-if="url"
      class="tui-card__link"
      :aria-label="urlLabel"
      :aria-hidden="urlAriaHidden"
      :href="url"
      :tabindex="!urlTabbable ? -1 : false"
    />
    <slot />
  </div>
</template>

<script>
export default {
  props: {
    clickable: {
      type: Boolean,
    },
    hasHoverShadow: {
      type: Boolean,
    },
    hasShadow: {
      type: Boolean,
    },
    noBorder: {
      type: Boolean,
    },
    // Full card link URL
    url: {
      type: String,
    },
    // Hidden on screen readers (when link is repeated inside card)
    urlAriaHidden: {
      type: Boolean,
    },
    // Label displayed to screen readers
    urlLabel: {
      type: String,
    },
    // The card should only be tabbable when no action included within the card
    urlTabbable: {
      type: Boolean,
    },
  },
  methods: {
    clickCard() {
      if (!this.clickable) return;
      this.$emit('click');
    },
  },
};
</script>

<style lang="scss">
.tui-card {
  position: relative;
  display: flex;
  border: 1px solid var(--card-border-color);
  border-radius: var(--card-border-radius);
  outline: none;

  &__link {
    position: absolute;
    width: 100%;
    height: 100%;
  }

  &--noBorder {
    border: none;
    &:focus,
    &:hover {
      border: none;
    }
  }

  &--hasHoverShadow:focus,
  &--hasHoverShadow:hover {
    box-shadow: var(--shadow-2);
  }

  &--hasShadow {
    box-shadow: var(--shadow-2);
  }

  &--clickable {
    transition: box-shadow var(--transition-form-function)
      var(--transition-form-duration);

    &.tui-focusWithin {
      box-shadow: var(--shadow-2);
    }
  }

  &--clickable:hover,
  &--clickable:focus {
    box-shadow: var(--shadow-2);
    cursor: pointer;
  }
}
</style>
