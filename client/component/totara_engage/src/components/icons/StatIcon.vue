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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module totara_engage
-->

<template>
  <div class="tui-engageStatIcon">
    <span
      class="tui-engageStatIcon__iconContainer"
      role="img"
      :aria-label="title"
    >
      <span class="tui-engageStatIcon__icon" :class="iconCss">
        <slot />
      </span>
    </span>

    <span class="tui-engageStatIcon__statNumber" aria-hidden="true">
      {{ statNumber }}
    </span>
  </div>
</template>

<script>
export default {
  props: {
    title: {
      required: true,
      type: String,
    },

    statNumber: {
      required: true,
      type: [String, Number],
    },

    iconSize: {
      type: [String, Number],
    },
  },

  computed: {
    iconCss() {
      if (this.clickable) {
        return 'tui-engageStatIcon__icon--clickable';
      }

      return '';
    },
  },
};
</script>

<style lang="scss">
.tui-engageStatIcon {
  display: flex;
  flex-direction: column;
  align-items: center;

  &__statNumber {
    font-size: var(--font-size-12);
  }

  &__icon {
    display: flex;
    align-items: center;
    outline: none;
    cursor: default;
    &--clickable {
      cursor: pointer;
    }
  }
}
</style>
