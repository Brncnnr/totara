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
  <div
    class="tui-formRowStack"
    :class="spacing ? 'tui-formRowStack--spacing-' + spacing : null"
  >
    <slot />
  </div>
</template>

<script>
export default {
  props: {
    spacing: {
      type: String,
      validator: x => !x || x == 'large',
    },
  },
};
</script>

<style lang="scss">
.tui-formRowStack {
  @include tui-stack-vertical(var(--gap-4));

  &--spacing {
    &-large {
      @include tui-stack-vertical(var(--gap-8));
    }
  }
}
</style>
