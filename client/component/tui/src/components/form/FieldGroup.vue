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

  @author Steve Barnett <steve.barnett@totaralearning.com>
  @module totara_core
-->

<template>
  <div class="tui-formFieldGroup" role="group" :aria-labelledby="labelId">
    <slot />
  </div>
</template>

<script>
export default {
  inject: {
    reformFieldContext: { default: null },
  },

  provide() {
    return {
      // prevent field context from being passed down
      reformFieldContext: null,
    };
  },

  props: {
    ariaLabelledby: {
      type: String,
      required: true,
    },
  },

  computed: {
    labelId() {
      return (
        this.ariaLabelledby ||
        (this.reformFieldContext && this.reformFieldContext.getLabelId())
      );
    },
  },
};
</script>

<style lang="scss">
.tui-formFieldGroup {
  flex-grow: 1;
  width: 100%;
  min-width: 0;
}
</style>
