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

  @author Rodney Cruden-Powell <rodney.cruden-powell@totaralearning.com>
  @module mod_perform
-->

<template>
  <div class="tui-activityProgressTrackerItem">
    <p
      v-if="availability"
      class="tui-activityProgressTrackerItem__availability"
      :title="availability"
    >
      {{ availability }}
    </p>
    <ProgressTrackerButton
      :selected="isSelectedItem"
      :text="text"
      @click="$emit('select')"
    />
  </div>
</template>

<script>
import ProgressTrackerButton from 'tui/components/progresstracker/ProgressTrackerButton';

export default {
  components: {
    ProgressTrackerButton,
  },

  props: {
    // An optional availability string that will be used to display the current availability of this section
    availability: String,
    id: {
      type: [String, Number],
      required: true,
    },
    selectedId: [Number, String],
    text: {
      type: String,
      required: true,
    },
  },

  computed: {
    /**
     * Check if this is the selected item
     *
     * @return {Boolean}
     */
    isSelectedItem() {
      return this.selectedId == this.id;
    },
  },
};
</script>

<style lang="scss">
.tui-activityProgressTrackerItem {
  &__availability {
    @include tui-font-body-small();
    margin: 0;
    padding-left: var(--gap-2);
    overflow: hidden;
    color: var(--color-neutral-6);
    line-height: var(--font-body-line-height);
    text-overflow: ellipsis;
  }
}
</style>
