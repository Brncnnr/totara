<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module totara_competency
-->

<template>
  <div class="tui-competencySummarySectionHeader">
    <h3 class="tui-competencySummarySectionHeader__title">
      {{ title }}
    </h3>

    <div class="tui-competencySummarySectionHeader__actions">
      <slot name="custom-actions" />

      <a :href="editUrl" :title="$str('edit', 'core')">
        <EditIcon :size="200" :alt="$str('edit', 'core')" />
      </a>
    </div>
  </div>
</template>

<script>
// Components
import EditIcon from 'tui/components/icons/Edit';

export default {
  components: {
    EditIcon,
  },

  props: {
    editUrl: {
      type: String,
      required: true,
    },
    title: {
      type: String,
      required: true,
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "edit"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-competencySummarySectionHeader {
  display: flex;
  align-items: center;
  padding-bottom: var(--gap-1);
  border-bottom: var(--border-width-thin) solid var(--color-neutral-5);

  &__title {
    margin: 0;
    @include tui-font-heading-small();
  }

  &__actions {
    display: flex;
    flex-shrink: 0;
    margin-left: auto;
    padding-left: var(--gap-4);

    & > * + * {
      margin-left: var(--gap-4);
    }
  }
}
</style>
