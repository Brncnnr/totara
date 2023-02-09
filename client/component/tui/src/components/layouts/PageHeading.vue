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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module tui
-->

<template>
  <div class="tui-pageHeading">
    <h2 class="tui-pageHeading__title">
      {{ title }}
    </h2>

    <template v-if="$slots.buttons">
      <div class="tui-pageHeading__buttons">
        <slot name="buttons" />
      </div>
    </template>
  </div>
</template>

<script>
export default {
  props: {
    title: {
      required: true,
      type: String,
    },
  },
};
</script>

<style lang="scss">
.tui-pageHeading {
  display: flex;
  flex-direction: column;
  flex-wrap: wrap;

  &__title {
    @include tui-font-heading-page-title;
    @include tui-wordbreak--hard;
  }

  &__buttons {
    & > * {
      margin: var(--gap-3) 0 0 var(--gap-2);
    }
  }
}

@media (min-width: $tui-screen-sm) {
  .tui-pageHeading {
    flex-direction: row;

    &__buttons {
      margin-left: auto;
      & > * {
        margin-top: 0;
      }
    }
  }
}
</style>
