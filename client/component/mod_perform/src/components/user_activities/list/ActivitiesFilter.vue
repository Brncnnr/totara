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

  @author Riana Rossouw <riana.rossouw@totaralearning.com>
  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module mod_perform
-->
<template>
  <div class="tui-performUserActivitiesFilter">
    <FilterBar
      v-model="value"
      :title="$str('user_activities_filter', 'mod_perform')"
      @active-count-changed="filtersUpdated"
    >
      <template v-slot:filters-left="{ stacked }">
        <!-- Activity type select -->
        <SelectFilter
          v-if="filterOptions.activityTypes"
          v-model="value.activityType"
          :label="$str('user_activities_filter_type', 'mod_perform')"
          :options="activityTypeFilterOptions"
          :show-label="true"
          :stacked="stacked"
        />

        <!-- Your progress select -->
        <SelectFilter
          v-if="filterOptions.progressOptions"
          v-model="value.ownProgress"
          :label="$str('user_activities_filter_own_progress', 'mod_perform')"
          :options="progressFilterOptions"
          :show-label="true"
          :stacked="stacked"
        />
      </template>

      <template v-slot:filters-right="{ stacked }">
        <SearchFilter
          v-model="value.search"
          :label="
            $str(
              aboutOthers
                ? 'user_activities_filter_search_others'
                : 'user_activities_filter_search',
              'mod_perform'
            )
          "
          :placeholder="
            $str(
              aboutOthers
                ? 'user_activities_filter_search_others_placeholder'
                : 'user_activities_filter_search_placeholder',
              'mod_perform'
            )
          "
          drop-label
          :stacked="stacked"
        />
      </template>
    </FilterBar>

    <div
      v-if="hasOverdue || hasCompleted"
      class="tui-performUserActivitiesFilter__toggles"
    >
      <!-- Toggle overdue only -->
      <ToggleSwitch
        v-if="hasOverdue"
        v-model="value.overdueOnly"
        :text="$str('user_activities_filter_overdue_only', 'mod_perform')"
        :toggle-first="true"
      />

      <!-- Toggle exclude completed activities only -->
      <ToggleSwitch
        v-if="hasCompleted"
        v-model="value.excludeCompleted"
        :text="$str('user_activities_filter_exclude_completed', 'mod_perform')"
        :toggle-first="true"
      />
    </div>
  </div>
</template>

<script>
import FilterBar from 'tui/components/filters/FilterBar';
import SearchFilter from 'tui/components/filters/SearchFilter';
import SelectFilter from 'tui/components/filters/SelectFilter';
import ToggleSwitch from 'tui/components/toggle/ToggleSwitch';

export default {
  components: {
    FilterBar,
    SearchFilter,
    SelectFilter,
    ToggleSwitch,
  },

  props: {
    aboutOthers: Boolean,
    filterOptions: Object,
    value: Object,
    hasCompleted: Boolean,
    hasOverdue: Boolean,
  },

  computed: {
    /**
     * Get the available options for activity type filter
     *
     * @return {Array}
     */
    activityTypeFilterOptions() {
      return this.mapFilterOptions(this.filterOptions.activityTypes);
    },

    /**
     * Get the available options for progress filter
     *
     * @return {Array}
     */
    progressFilterOptions() {
      return this.mapFilterOptions(this.filterOptions.progressOptions);
    },
  },

  methods: {
    /**
     * Emit updated filter flag
     *
     */
    filtersUpdated(activeFilters) {
      this.$emit('filter-change', activeFilters);
    },

    /**
     * Map filter options to required format
     *
     * @param {Object} source
     * @return {Object}
     */
    mapFilterOptions(source) {
      let filters = source;

      filters = Object.keys(filters).map(id => {
        return {
          id: id,
          label: filters[id],
        };
      });

      filters.unshift({
        id: null,
        label: this.$str('all', 'core'),
      });

      return filters;
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "all"
    ],
    "mod_perform": [
      "user_activities_filter",
      "user_activities_filter_exclude_completed",
      "user_activities_filter_overdue_only",
      "user_activities_filter_own_progress",
      "user_activities_filter_search",
      "user_activities_filter_search_others",
      "user_activities_filter_search_others_placeholder",
      "user_activities_filter_search_placeholder",
      "user_activities_filter_type"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performUserActivitiesFilter {
  & > * + * {
    margin-top: var(--gap-4);
  }

  &__toggles {
    & > * + * {
      margin-top: var(--gap-2);
    }
  }
}

@media (min-width: $tui-screen-sm) {
  .tui-performUserActivitiesFilter {
    &__toggles {
      display: flex;

      & > * + * {
        margin-top: 0;
        margin-left: var(--gap-4);
      }
    }
  }
}
</style>
