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

  @author Arshad Anwer <arshad.anwer@totaralearning.com>
  @module totara_notification
-->

<template>
  <Popover
    :data-key="placeholderKey"
    :data-label="displayName"
    :triggers="['hover']"
    class="tui-placeholder"
  >
    <template v-slot:trigger>
      <span slot="trigger" class="tui-placeholder__text">
        {{ displayName }}</span
      >
    </template>

    <template>
      <span>
        <!--
          Note that the bracket is what we make user to think of how it looks like
          when it got rendered to as content. Something for machine to understand
          not human readable content. Hence no point to do i18n for it.
        -->
        [{{ placeholderKey }}]
      </span>
    </template>
  </Popover>
</template>

<script>
import Popover from 'tui/components/popover/Popover';

export default {
  components: {
    Popover,
  },
  props: {
    placeholderKey: {
      type: String,
      required: true,
    },
    displayName: {
      type: String,
      required: true,
    },
  },
};
</script>

<style lang="scss">
.tui-placeholder {
  display: inline-block;
  white-space: normal;

  &__text {
    padding: 0 var(--gap-1);
    border: var(--border-width-thin) solid var(--color-border);
  }
}
</style>
