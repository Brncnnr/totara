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

  @author Riana Rossouw <riana.rossouw@totaralearning.com>
  @module hierarchy_goal
-->

<template>
  <div
    class="tui-goalLinkedReviewChangeStatus"
    :class="{ 'tui-goalLinkedReviewChangeStatus--print': fromPrint }"
  >
    <h3 class="tui-goalLinkedReviewChangeStatus__title">
      <span
        :id="$id('status_title')"
        class="tui-goalLinkedReviewChangeStatus__title-text"
      >
        {{ $str('goal_status', 'hierarchy_goal') }}
      </span>
      <span
        v-if="required"
        class="tui-goalLinkedReviewChangeStatus__title-required"
      >
        <span aria-hidden="true">*</span>
        <span class="sr-only">{{ $str('required', 'core') }}</span>
      </span>

      <HelpIcon
        v-if="!fromPrint"
        class="tui-goalLinkedReviewChangeStatus__title-help"
        :desc-id="$id('change_status-help')"
        :helpmsg="$str('goal_change_status_help', 'hierarchy_goal')"
      />
    </h3>

    <!-- Status display -->
    <div v-if="status" class="tui-goalLinkedReviewChangeStatus__response">
      <slot name="display" />
    </div>

    <!-- Form display -->
    <div v-else class="tui-goalLinkedReviewChangeStatus__form">
      <slot name="form" :title-id="$id('status_title')" />
    </div>
  </div>
</template>

<script>
import HelpIcon from 'tui/components/form/HelpIcon';

export default {
  components: {
    HelpIcon,
  },

  props: {
    fromPrint: Boolean,
    status: Object,
    required: Boolean,
  },
};
</script>

<lang-strings>
{
  "core": [
    "required"
  ],
  "hierarchy_goal": [
    "goal_status",
    "goal_change_status_help"
  ]
}
</lang-strings>

<style lang="scss">
.tui-goalLinkedReviewChangeStatus {
  max-width: 1200px;
  padding: var(--gap-4) 0;
  border-top: var(--border-width-thin) solid var(--color-neutral-7);
  border-bottom: var(--border-width-normal) solid var(--color-neutral-7);

  &--print {
    border-top: none;
  }

  & > * + * {
    margin-top: var(--gap-4);
  }

  &__form {
    & > * + * {
      margin-top: var(--gap-2);
    }
  }

  &__title {
    margin: 0;
    @include tui-font-heading-x-small;

    &-help {
      padding-left: var(--gap-1);
    }

    &-required {
      @include tui-font-heading-label();
      color: var(--color-prompt-alert);
    }
  }
}
</style>
