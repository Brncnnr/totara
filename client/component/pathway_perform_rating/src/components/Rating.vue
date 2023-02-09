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

  @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
  @module totara_competency
-->

<template>
  <div
    class="tui-competencyLinkedReviewRating"
    :class="{ 'tui-competencyLinkedReviewRating--print': fromPrint }"
  >
    <h3 class="tui-competencyLinkedReviewRating__title">
      <span
        :id="$id('rating_title')"
        class="tui-competencyLinkedReviewRating__title-text"
      >
        {{ $str('rating_to_be_submitted', 'pathway_perform_rating') }}
      </span>
      <span
        v-if="required"
        class="tui-competencyLinkedReviewRating__title-required"
      >
        <span aria-hidden="true">*</span>
        <span class="sr-only">{{ $str('required', 'core') }}</span>
      </span>

      <HelpIcon
        v-if="!rating && !fromPrint"
        class="tui-competencyLinkedReviewRating__title-help"
        :desc-id="$id('activation-help')"
        :helpmsg="
          $str('rating_only_one_rating_submitted', 'pathway_perform_rating')
        "
      />
    </h3>

    <!-- Rating display -->
    <div v-if="rating" class="tui-competencyLinkedReviewRating__response">
      <slot name="display" />
    </div>

    <!-- Form display -->
    <div v-else class="tui-competencyLinkedReviewRating__form">
      <slot name="form" :title-id="$id('rating_title')" />
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
    rating: Object,
    required: Boolean,
  },
};
</script>

<lang-strings>
{
  "core": [
    "required"
  ],
  "pathway_perform_rating": [
    "rating_only_one_rating_submitted",
    "rating_to_be_submitted"
  ]
}
</lang-strings>

<style lang="scss">
.tui-competencyLinkedReviewRating {
  padding: var(--gap-4) 0;
  border-top: var(--border-width-thin) solid var(--color-neutral-7);
  border-bottom: var(--border-width-normal) solid var(--color-neutral-7);

  &--print {
    border-top: none;
  }

  & > * + * {
    margin-top: var(--gap-4);
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
