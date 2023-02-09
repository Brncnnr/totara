<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Simon Tegg <simon.tegg@totaralearning.com>
  @module mod_approval
-->
<!-- Close copy of tui/components/PageHeading with a smaller title  -->
<!-- Used when the page has large header and left nav -->
<template>
  <div class="tui-mod_approval-subPageHeading">
    <h3 class="tui-mod_approval-subPageHeading__title">
      {{ title }}
    </h3>
    <template v-if="$slots.buttons">
      <div class="tui-mod_approval-subPageHeading__buttons">
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
.tui-mod_approval-subPageHeading {
  &__title {
    margin: 0;
    @include tui-font-heading-small-regular;
  }

  &__buttons {
    display: flex;
    & > * {
      margin: var(--gap-3) 0 0 var(--gap-2);
    }
  }
}

@media (min-width: $tui-screen-sm) {
  .tui-mod_approval-subPageHeading {
    display: flex;

    &__buttons {
      margin-left: auto;
      & > * {
        margin-top: 0;
      }
    }
  }
}
</style>
