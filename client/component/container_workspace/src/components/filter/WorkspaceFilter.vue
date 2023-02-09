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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module container_workspace
-->
<template>
  <div class="tui-workspaceFilter">
    <template v-if="!$apollo.loading">
      <FilterBar
        v-model="selection"
        :title="$str('find_spaces', 'container_workspace')"
        class="tui-workspaceFilter__filter"
      >
        <template v-slot:filters-left="{ stacked }">
          <SelectFilter
            v-model="selection.access"
            :label="$str('workspace_access', 'container_workspace')"
            :show-label="true"
            :options="options.accesses"
            :stacked="stacked"
          />

          <SelectFilter
            v-model="selection.source"
            :label="$str('membership', 'container_workspace')"
            :show-label="true"
            :options="options.sources"
            :stacked="stacked"
          />
        </template>

        <template v-slot:filters-right="{ stacked }">
          <SearchFilter
            v-model="selection.searchTerm"
            :label="$str('search_spaces', 'container_workspace')"
            drop-label
            :placeholder="$str('search_spaces', 'container_workspace')"
            :stacked="stacked"
          />
        </template>
      </FilterBar>

      <div class="tui-workspaceFilter__sortFilter">
        <div v-if="!spacesIsLoading" class="tui-workspaceFilter__total">
          <template v-if="spacesCursor.total === 0 || spacesCursor.total > 1">
            {{
              $str('total_space_x', 'container_workspace', spacesCursor.total)
            }}
          </template>
          <template v-else-if="spacesCursor.total === 1">
            {{ $str('single_space', 'container_workspace') }}
          </template>
        </div>

        <SelectFilter
          v-model="selection.sort"
          class="tui-workspaceFilter__filter"
          :label="$str('sortby', 'core')"
          :show-label="true"
          :options="options.sorts"
        />
      </div>
    </template>
  </div>
</template>

<script>
import FilterBar from 'tui/components/filters/FilterBar';
import SelectFilter from 'tui/components/filters/SelectFilter';
import SearchFilter from 'tui/components/filters/SearchFilter';

// GraphQL queries
import getFilterOptions from 'container_workspace/graphql/workspace_filter_options';

export default {
  components: {
    FilterBar,
    SelectFilter,
    SearchFilter,
  },

  props: {
    selectedSource: {
      type: String,
      default: null,
    },

    selectedSort: {
      type: String,
      default: null,
    },

    searchTerm: {
      type: String,
      default: '',
    },

    selectedAccess: {
      type: String,
      default: null,
    },

    spacesCursor: {
      type: Object,
      default() {
        return { total: 0, next: '' };
      },
    },

    spacesIsLoading: Boolean,
  },

  apollo: {
    options: {
      query: getFilterOptions,
      /**
       *
       * @param {Array} sources
       * @param {Array} sorts
       * @param {Array} accesses
       */
      update({ sources, sorts, accesses }) {
        accesses = [
          {
            label: this.$str('all', 'core'),
            value: null,
          },
        ].concat(accesses);

        return {
          sources: Array.prototype.map.call(sources, ({ value, label }) => {
            return {
              id: value,
              label: label,
            };
          }),

          sorts: Array.prototype.map.call(sorts, ({ value, label }) => {
            return {
              id: value,
              label: label,
            };
          }),

          accesses: Array.prototype.map.call(accesses, ({ value, label }) => {
            return {
              id: value,
              label: label,
            };
          }),
        };
      },
    },
  },

  data() {
    return {
      options: {},

      selection: {
        source: this.selectedSource,
        sort: this.selectedSort,
        access: this.selectedAccess,
        searchTerm: this.searchTerm,
      },
    };
  },
  watch: {
    selection: {
      deep: true,
      handler() {
        this.$emit('filter', this.selection);
      },
    },
  },
  methods: {
    submitSearch() {
      this.$emit('submit-search', this.selection);
    },
  },
};
</script>

<lang-strings>
  {
    "container_workspace": [
      "membership",
      "search_spaces",
      "total_space_x",
      "single_space",
      "find_spaces",
      "workspace_access"
    ],

    "core": [
      "all",
      "sortby"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-workspaceFilter {
  &__sortFilter {
    display: flex;
    justify-content: space-between;
    margin-top: var(--gap-8);
  }

  &__total {
    align-self: center;
    @include tui-font-heading-x-small;
  }

  &__filter {
    margin-left: auto;
  }
}
</style>
