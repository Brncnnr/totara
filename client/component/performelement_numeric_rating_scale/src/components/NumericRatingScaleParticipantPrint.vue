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

  @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
  @package performelement_numeric_rating_scale
-->
<template>
  <div
    class="tui-numericRatingScaleParticipantPrint"
    :class="{
      'tui-numericRatingScaleParticipantPrint--withDescription':
        element.data.descriptionEnabled,
    }"
  >
    <Range
      :value="formattedResponse"
      :no-thumb="!hasBeenAnswered"
      :default-value="element.data.defaultValue"
      :show-labels="false"
      :min="min"
      :max="max"
    />
    <RenderedContent
      v-if="element.data.descriptionEnabled"
      class="tui-numericRatingScaleParticipantPrint__description"
      :content-html="element.data.descriptionHtml"
    />
    <div
      v-if="hasBeenAnswered"
      class="tui-numericRatingScaleParticipantPrint__formattedResponse"
    >
      {{ formattedResponse }}
    </div>
    <NotepadLines
      v-else
      class="tui-numericRatingScaleParticipantPrint__notepadLines"
      :char-length="10"
    />
  </div>
</template>

<script>
import Range from 'tui/components/form/Range';
import RenderedContent from 'tui/components/editor/RenderedContent';
import NotepadLines from 'tui/components/form/NotepadLines';

export default {
  components: {
    Range,
    RenderedContent,
    NotepadLines,
  },
  props: {
    data: Array,
    element: {
      type: Object,
      required: true,
    },
  },
  computed: {
    /**
     * The minimum value that can be selected.
     *
     * @return {number}
     */
    min() {
      return parseInt(this.element.data.lowValue, 10);
    },
    /**
     * The maximum value that can be selected.
     *
     * @return {number}
     */
    max() {
      return parseInt(this.element.data.highValue, 10);
    },

    /**
     * Has this question been answered.
     *
     * @return {boolean}
     */
    hasBeenAnswered() {
      return this.formattedResponse !== null;
    },

    /**
     * Has this question been answered.
     *
     * @return {string|null}
     */
    formattedResponse() {
      if (this.data.length > 0) {
        return this.data[0];
      }

      return null;
    },
  },
};
</script>

<style lang="scss">
.tui-numericRatingScaleParticipantPrint {
  &__description {
    margin-top: var(--gap-2);
  }

  &__formattedResponse {
    margin-top: var(--gap-8);
  }

  &__notepadLines {
    margin-top: var(--gap-4);
  }

  &--withDescription {
    .tui-numericRatingScaleParticipantPrint {
      &__formattedResponse {
        margin-top: var(--gap-12);
      }

      &__notepadLines {
        margin-top: var(--gap-8);
      }
    }
  }
}
</style>
