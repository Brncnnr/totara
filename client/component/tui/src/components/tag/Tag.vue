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
  <a v-if="href" :href="href" class="tui-tag tui-tag__content">{{ text }}</a>

  <div v-else class="tui-tag" :class="{ 'tui-tag--primary': primary }">
    <span
      class="tui-tag__content"
      :class="{ 'tui-tag__content--withButton': $slots.button }"
      >{{ text }}</span
    >
    <slot name="button" />
  </div>
</template>

<script>
export default {
  props: {
    primary: Boolean,
    text: {
      type: String,
      required: true,
    },
    href: String,
  },
};
</script>

<style lang="scss">
.tui-tag {
  display: inline-flex;
  flex-shrink: 0;
  align-items: center;
  height: var(--tag-height);
  white-space: nowrap;
  background-color: var(--tag-bg-color);
  border: var(--border-width-thin) solid var(--tag-border-color);
  border-radius: var(--tag-border-radius);

  &__content {
    padding: 0 var(--gap-2);
    font-size: var(--font-size-13);

    &--withButton {
      padding-right: 0;
      color: var(--tag-button-color);
    }
  }

  &--primary {
    text-transform: uppercase;
    background-color: var(--color-state);
  }

  &--primary &__content {
    color: var(--color-neutral-1);
  }
}
</style>
