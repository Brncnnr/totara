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

  @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
  @module mod_perform
-->

<template>
  <div class="tui-performManageActivityFilters">
    <FilterBar
      v-model="selectedFilters"
      :title="$str('perform:manage_activity', 'mod_perform')"
      class="tui-performManageActivityFilters__filter"
    >
      <template v-slot:filters-left="{ stacked }">
        <SelectFilter
          v-model="selectedFilters.type"
          :label="$str('manage_activity_list_filter_type', 'mod_perform')"
          :show-label="true"
          :options="options.types"
          :stacked="stacked"
        />

        <SelectFilter
          v-model="selectedFilters.status"
          :label="$str('manage_activity_list_filter_status', 'mod_perform')"
          :show-label="true"
          :options="options.statuses"
          :stacked="stacked"
        />
      </template>

      <template v-slot:filters-right="{ stacked }">
        <SearchFilter
          v-model="selectedFilters.name"
          :label="$str('manage_activity_list_filter_name', 'mod_perform')"
          :placeholder="
            $str('manage_activity_list_filter_name_placeholder', 'mod_perform')
          "
          drop-label
          :stacked="stacked"
        />
      </template>
    </FilterBar>

    <div class="tui-performManageActivityFilters__after">
      <strong v-if="total" class="tui-performManageActivityFilters__total">
        {{ $str('showing_activities', 'mod_perform', { shown, total }) }}
      </strong>

      <div class="tui-performManageActivityFilters__sortFilter">
        <SelectFilter
          v-model="selectedSorting"
          :label="$str('sortby')"
          :show-label="true"
          :options="options.sorts"
        />
      </div>
    </div>
  </div>
</template>

<script>
import FilterBar from 'tui/components/filters/FilterBar';
import SearchFilter from 'tui/components/filters/SearchFilter';
import SelectFilter from 'tui/components/filters/SelectFilter';
import {
  ACTIVITY_STATUS_ACTIVE,
  ACTIVITY_STATUS_DRAFT,
} from 'mod_perform/constants';

export default {
  components: {
    FilterBar,
    SearchFilter,
    SelectFilter,
  },

  props: {
    types: {
      type: Array,
      required: true,
    },
    shown: {
      type: Number,
      default: 0,
    },
    total: {
      type: Number,
      default: 0,
    },
  },

  data() {
    return {
      options: {
        /* Type options: 'All' is always first, then alphabetically sorted types afterwards. */
        types: [
          {
            id: null,
            label: this.$str('all'),
          },
        ].concat(this.types),
        /* Status options: Hard coded order, with 'All' being the first option. */
        statuses: [
          {
            id: null,
            label: this.$str('all'),
          },
          {
            id: ACTIVITY_STATUS_DRAFT,
            label: this.$str('activity_status_draft', 'mod_perform'),
          },
          {
            id: ACTIVITY_STATUS_ACTIVE,
            label: this.$str('activity_status_active', 'mod_perform'),
          },
        ],
        /* Sorting options: Creation date and name, sorted alphabetically.
         * The id of each sorting option directly correlates to the sort_query_by_ methods in the data provider. */
        sorts: [
          {
            id: 'creation_date',
            label: this.$str(
              'manage_activity_list_sort_creation_date',
              'mod_perform'
            ),
          },
          {
            id: 'name',
            label: this.$str('manage_activity_list_sort_name', 'mod_perform'),
          },
        ].sort((a, b) => a.label.localeCompare(b.label)),
      },
      selectedFilters: {
        type: null,
        status: null,
        name: '',
      },
      selectedSorting: 'creation_date',
    };
  },

  watch: {
    selectedFilters: {
      deep: true,
      handler: 'update',
    },
    selectedSorting: 'update',
  },

  methods: {
    /**
     * Notify the parent list component of different filters being selected.
     */
    update() {
      this.$emit('update', {
        filters: this.selectedFilters,
        sorting: this.selectedSorting,
      });
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "activity_status_active",
      "activity_status_draft",
      "manage_activity_list_filter_name",
      "manage_activity_list_filter_name_placeholder",
      "manage_activity_list_filter_status",
      "manage_activity_list_filter_type",
      "manage_activity_list_sort_creation_date",
      "manage_activity_list_sort_name",
      "perform:manage_activity",
      "showing_activities"
    ],
    "core": [
      "all",
      "sortby"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performManageActivityFilters {
  & > * + * {
    margin-top: var(--gap-8);
  }

  &__sortFilter {
    margin-top: var(--gap-4);
  }
}

@media (min-width: $tui-screen-sm) {
  .tui-performManageActivityFilters {
    &__after {
      display: flex;
      align-items: center;
    }

    &__sortFilter {
      margin-top: 0;
      margin-left: auto;
    }
  }
}
</style>
