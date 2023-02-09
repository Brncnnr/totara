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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module mod_perform
-->

<template>
  <div v-if="total" class="tui-performUserActivitiesCount">
    <div
      class="tui-performUserActivitiesCount__count"
      :class="{
        'tui-performUserActivitiesCount__count--hidden': loading,
      }"
    >
      {{
        $str('showing_activities', 'mod_perform', {
          shown: displayedCount,
          total: total,
        })
      }}
    </div>

    <div v-if="sortByOptions" class="tui-performUserActivitiesCount__sort">
      <SelectFilter
        :value="value"
        :label="$str('sortby')"
        :show-label="true"
        :options="sortByFilterOptions"
        @input="$emit('input', $event)"
      />
    </div>
  </div>
</template>

<script>
import SelectFilter from 'tui/components/filters/SelectFilter';

export default {
  components: {
    SelectFilter,
  },

  props: {
    aboutOthers: Boolean,
    displayedCount: Number,
    loading: Boolean,
    sortByOptions: Array,
    total: Number,
    value: String,
  },

  computed: {
    /**
     * Get the available options for sort by filter
     *
     * @return {Array}
     */
    sortByFilterOptions() {
      let options = this.sortByOptions;
      if (!this.aboutOthers) {
        options = options.filter(item => 'subject_name' !== item.id);
      }

      return options;
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "sortby"
    ],
    "mod_perform": [
      "showing_activities" 
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performUserActivitiesCount {
  align-items: center;
  margin-top: var(--gap-6);

  & > * + * {
    margin-top: var(--gap-4);
  }

  &__sort {
    margin-left: auto;
  }

  &__count {
    @include tui-font-heading-x-small;
    flex-grow: 1;

    &--hidden {
      visibility: hidden;
    }
  }
}

@media (min-width: $tui-screen-sm) {
  .tui-performUserActivitiesCount {
    display: flex;

    & > * + * {
      margin-top: 0;
    }
  }
}
</style>
