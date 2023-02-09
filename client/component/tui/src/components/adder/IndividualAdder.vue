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
  @module tui
-->

<template>
  <Adder
    class="tui-individualsAdder"
    :existing-items="existingItems"
    :has-loading-preview="true"
    :loading="$apollo.loading && !showLoadingBtn"
    :open="open"
    :show-load-more="nextPage"
    :show-loading-btn="showLoadingBtn"
    :title="$str('select_individuals', 'totara_core')"
    @added="closeWithData($event)"
    @cancel="$emit('cancel')"
    @load-more="loadMoreItems()"
    @selected-tab-active="updateSelectedItems($event)"
  >
    <template v-if="$scopedSlots.notices" v-slot:notices>
      <slot name="notices" />
    </template>

    <!-- Filters -->
    <template v-slot:browse-filters>
      <FilterBar
        :has-top-bar="false"
        :title="$str('filter_individuals', 'totara_core')"
      >
        <template v-slot:filters-left="{ stacked }">
          <SearchFilter
            v-model="searchDebounce"
            :debounce-input="false"
            :label="$str('filter_individuals_search_label', 'totara_core')"
            :placeholder="$str('search', 'totara_core')"
            :show-label="false"
            :stacked="stacked"
          />
        </template>
      </FilterBar>
    </template>

    <!-- View Browse individuals datatable -->
    <template v-slot:browse-list="{ disabledItems, selectedItems, update }">
      <Responsive
        :breakpoints="[
          { name: 'vertical', boundaries: [0, 715] },
          { name: 'horizontal', boundaries: [715, 715] },
        ]"
      >
        <template v-slot="{ currentBoundaryName }">
          <SelectTable
            :border-bottom-hidden="true"
            checkbox-v-align="center"
            :data="listItems"
            :disabled-ids="disabledItems"
            :large-check-box="true"
            :loading-overlay-active="true"
            :loading-preview="
              !closingWithData && listLoading && listItems.length === 0
            "
            :loading-preview-rows="Math.max(listItems.length, 5)"
            :no-label-offset="true"
            :select-all-enabled="true"
            :stack-at="0"
            :value="selectedItems"
            @input="update($event)"
          >
            <template v-slot:header-row>
              <HeaderCell ref="headerCell" size="12" valign="center">
                {{ $str('users', 'totara_core') }}
              </HeaderCell>
            </template>

            <template v-slot:row="{ row, id }">
              <Cell :ref="'individual' + id" size="12" valign="center">
                <MiniProfileCard
                  :display="row.card_display"
                  :horizontal="currentBoundaryName !== 'vertical'"
                  :no-border="true"
                  :no-padding="true"
                  :read-only="true"
                />
              </Cell>
            </template>
          </SelectTable>
        </template>
      </Responsive>
    </template>

    <!-- View selected individuals datatable -->
    <template v-slot:basket-list="{ disabledItems, selectedItems, update }">
      <Responsive
        :breakpoints="[
          { name: 'vertical', boundaries: [0, 715] },
          { name: 'horizontal', boundaries: [715, 715] },
        ]"
      >
        <template v-slot="{ currentBoundaryName }">
          <SelectTable
            :border-bottom-hidden="true"
            checkbox-v-align="center"
            :data="individualSelectedItems"
            :disabled-ids="disabledItems"
            :large-check-box="true"
            :loading-overlay-active="true"
            :loading-preview="!closingWithData && selectedLoading"
            :no-label-offset="true"
            :select-all-enabled="true"
            :stack-at="0"
            :value="selectedItems"
            @input="update($event)"
          >
            <template v-slot:header-row>
              <HeaderCell size="12" valign="center">
                {{ $str('users', 'totara_core') }}
              </HeaderCell>
            </template>

            <template v-slot:row="{ row }">
              <Cell size="12" valign="center">
                <MiniProfileCard
                  :display="row.card_display"
                  :horizontal="currentBoundaryName !== 'vertical'"
                  :no-border="true"
                  :no-padding="true"
                  :read-only="true"
                />
              </Cell>
            </template>
          </SelectTable>
        </template>
      </Responsive>
    </template>
  </Adder>
</template>

<script>
// Components
import Adder from 'tui/components/adder/Adder';
import Cell from 'tui/components/datatable/Cell';
import FilterBar from 'tui/components/filters/FilterBar';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import MiniProfileCard from 'tui/components/profile/MiniProfileCard';
import SearchFilter from 'tui/components/filters/SearchFilter';
import SelectTable from 'tui/components/datatable/SelectTable';
import Responsive from 'tui/components/responsive/Responsive';
// Utils
import { debounce } from 'tui/util';
// Queries
import users from 'core/graphql/users';

export default {
  components: {
    Adder,
    Cell,
    FilterBar,
    HeaderCell,
    MiniProfileCard,
    SearchFilter,
    SelectTable,
    Responsive,
  },

  props: {
    // Provide a custom query when the selection is from a subset of individuals
    customQuery: Object,
    // customQueryFilters needs to be passed when a custom query requires additional filters
    customQueryFilters: Object,
    // customQueryKey needs to be passed if customQuery is provided
    customQueryKey: String,
    // Array of previously selected individuals IDs
    existingItems: {
      type: Array,
      default: () => [],
    },
    // If the adder modal is currently open
    open: Boolean,
    // Display loading spinner on Add button
    showLoadingBtn: Boolean,
  },

  data() {
    return {
      closingWithData: false,
      filters: {
        search: '',
      },
      individuals: null,
      individualSelectedItems: [],
      nextPage: false,
      skipQueries: true,
      searchDebounce: '',
    };
  },

  computed: {
    /**
     * Return the query indicating which graphql call to invoke.
     *
     * @return {Object}
     */
    query() {
      return this.customQuery ? this.customQuery : users;
    },

    /**
     * Return the query key to use when extracting data via graphql calls.
     *
     * @return {String}
     */
    query_key() {
      return this.customQueryKey ? this.customQueryKey : 'core_users';
    },

    listLoading() {
      return this.$apollo.queries.individuals.loading;
    },

    listItems() {
      return (this.individuals && this.individuals.items) || [];
    },

    selectedLoading() {
      return this.$apollo.queries.selectedIndividuals.loading;
    },
  },

  watch: {
    /**
     * On opening of adder, unblock query
     */
    open() {
      if (this.open) {
        this.searchDebounce = '';
        this.skipQueries = false;
      } else {
        this.skipQueries = true;
      }
    },

    searchDebounce(newValue) {
      this.updateFilterDebounced(newValue);
    },
  },

  /**
   * Apollo queries have been registered here to provide support for custom queries
   */
  created() {
    /**
     * Request all individuals query, by default the core query is used but a custom query
     * can be provided
     *
     * @return {Object} Returns an object containing an array of individuals
     */
    this.$apollo.addSmartQuery('individuals', {
      query: this.query,
      fetchPolicy: 'network-only',
      skip() {
        return this.skipQueries;
      },
      variables() {
        return {
          query: {
            filters: this.core_filters(),
          },
        };
      },

      update({ [this.query_key]: individuals }) {
        this.nextPage = individuals.next_cursor
          ? individuals.next_cursor
          : false;

        return individuals;
      },
    });

    /**
     * Selected individuals query
     */
    this.$apollo.addSmartQuery('selectedIndividuals', {
      query: this.query,
      skip() {
        return this.skipQueries;
      },
      variables() {
        return {
          query: {
            filters: this.reviewSelectionFilters(null),
          },
        };
      },

      update({ [this.query_key]: individuals }) {
        this.individualSelectedItems = individuals.items;
        return individuals;
      },
    });
  },

  methods: {
    /**
     * Formulate the final filters to use for a search query.
     */
    core_filters() {
      return this.individualFilters({
        name: this.filters.search,
      });
    },

    /**
     * Formulate the final filters to use for the get by ids query.
     *
     * @param {Array} ids ids to filter by.
     */
    reviewSelectionFilters(ids) {
      return this.individualFilters({
        ids: ids,
      });
    },

    /**
     * Formulate the final individual filters to use for a query.
     *
     * @param {Object} coreFilters the core user filters to use.
     */
    individualFilters(coreFilters) {
      if (this.customQueryFilters) {
        let filters = Object.assign(this.customQueryFilters, {});
        filters.core_filters = coreFilters;
        return filters;
      } else {
        return coreFilters;
      }
    },

    /**
     * Load addition items and append to list
     */
    async loadMoreItems() {
      if (!this.nextPage) {
        return;
      }

      await this.$apollo.queries.individuals.fetchMore({
        variables: {
          query: {
            cursor: this.nextPage,
            filters: this.core_filters(),
          },
        },

        updateQuery: (previousResult, { fetchMoreResult }) => {
          const oldData = previousResult[this.query_key];
          const newData = fetchMoreResult[this.query_key];
          const newList = oldData.items.concat(newData.items);

          return {
            [this.query_key]: {
              items: newList,
              next_cursor: newData.next_cursor,
              total: newData.total,
            },
          };
        },
      });
    },

    /**
     * Close the adder, returning the selected items data
     *
     * @param {Array} selection
     */
    async closeWithData(selection) {
      let data;

      this.closingWithData = true;

      this.$emit('add-button-clicked');

      try {
        data = await this.updateSelectedItems(selection);
      } catch (error) {
        console.error(error);
        return;
      }
      this.$emit('added', { ids: selection, data: data });

      this.closingWithData = false;
    },

    /**
     * Update the selected items data
     *
     * @param {Array} selection
     * @return {Array}
     */
    async updateSelectedItems(selection) {
      try {
        await this.$apollo.queries.selectedIndividuals.refetch({
          query: {
            filters: this.reviewSelectionFilters(selection),
            result_size: selection.length,
          },
        });
      } catch (error) {
        console.error(error);
      }
      return this.individualSelectedItems;
    },

    /**
     * Update the search filter (which re-triggers the query)
     * if the user stopped typing >500 milliseconds ago.
     *
     * @param {String} input Value from search filter input
     */
    updateFilterDebounced: debounce(function(input) {
      this.filters.search = input;
    }, 400),
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "filter_individuals",
    "filter_individuals_search_label",
    "select_individuals",
    "search",
    "users"
  ]
}
</lang-strings>
