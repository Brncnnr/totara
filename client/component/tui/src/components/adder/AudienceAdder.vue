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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module tui
-->

<template>
  <Adder
    :open="open"
    :title="$str('select_audiences', 'totara_core')"
    :existing-items="existingItems"
    :loading="($apollo.loading || forceResultsLoading) && !showLoadingBtn"
    :show-load-more="nextPage"
    :show-loading-btn="showLoadingBtn"
    @added="closeWithData($event)"
    @cancel="$emit('cancel')"
    @load-more="loadMoreItems()"
    @selected-tab-active="updateSelectedItems($event)"
  >
    <template v-if="$scopedSlots.notices" v-slot:notices>
      <slot name="notices" />
    </template>
    <template v-slot:browse-filters>
      <FilterBar
        :has-top-bar="false"
        :title="$str('filter_audiences', 'totara_core')"
      >
        <template v-slot:filters-left="{ stacked }">
          <SearchFilter
            v-model="searchDebounce"
            :label="$str('filter_audiences_search_label', 'totara_core')"
            :show-label="false"
            :placeholder="$str('search', 'totara_core')"
            :stacked="stacked"
          />
        </template>
      </FilterBar>
    </template>
    <template v-slot:browse-list="{ disabledItems, selectedItems, update }">
      <SelectTable
        :large-check-box="true"
        :no-label-offset="true"
        :value="selectedItems"
        :data="audiences && audiences.items ? audiences.items : []"
        :disabled-ids="disabledItems"
        checkbox-v-align="center"
        :select-all-enabled="true"
        :border-bottom-hidden="true"
        @input="update($event)"
      >
        <template v-slot:header-row>
          <HeaderCell size="12" valign="center">
            {{ $str('cohortname', 'totara_cohort') }}
          </HeaderCell>
          <HeaderCell size="4" valign="center">
            {{ $str('shortname', 'totara_cohort') }}
          </HeaderCell>
        </template>

        <template v-slot:row="{ row }">
          <Cell
            size="12"
            :column-header="$str('cohortname', 'totara_cohort')"
            valign="center"
          >
            {{ row.name }}
          </Cell>

          <Cell
            size="4"
            :column-header="$str('shortname', 'totara_cohort')"
            valign="center"
          >
            {{ row.idnumber }}
          </Cell>
        </template>
      </SelectTable>
    </template>

    <template v-slot:basket-list="{ disabledItems, selectedItems, update }">
      <SelectTable
        :large-check-box="true"
        :no-label-offset="true"
        :value="selectedItems"
        :data="audienceSelectedItems"
        :disabled-ids="disabledItems"
        checkbox-v-align="center"
        :border-bottom-hidden="true"
        :select-all-enabled="true"
        @input="update($event)"
      >
        <template v-slot:header-row>
          <HeaderCell size="12" valign="center">
            {{ $str('cohortname', 'totara_cohort') }}
          </HeaderCell>
          <HeaderCell size="4" valign="center">
            {{ $str('shortname', 'totara_cohort') }}
          </HeaderCell>
        </template>

        <template v-slot:row="{ row }">
          <Cell
            size="12"
            :column-header="$str('cohortname', 'totara_cohort')"
            valign="center"
          >
            {{ row.name }}
          </Cell>

          <Cell
            size="4"
            :column-header="$str('shortname', 'totara_cohort')"
            valign="center"
          >
            {{ row.idnumber }}
          </Cell>
        </template>
      </SelectTable>
    </template>
  </Adder>
</template>

<script>
// Components
import Adder from 'tui/components/adder/Adder';
import Cell from 'tui/components/datatable/Cell';
import FilterBar from 'tui/components/filters/FilterBar';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import SearchFilter from 'tui/components/filters/SearchFilter';
import SelectTable from 'tui/components/datatable/SelectTable';
// Queries
import cohorts from 'core/graphql/cohorts';
import { debounce } from 'tui/util';

export default {
  components: {
    Adder,
    Cell,
    FilterBar,
    HeaderCell,
    SearchFilter,
    SelectTable,
  },

  props: {
    existingItems: {
      type: Array,
      default: () => [],
    },
    open: Boolean,
    customQuery: Object,
    /**
     * custom query key needs to be passed
     * if customQuery is passed
     */
    customQueryKey: String,
    /**
     * Pass the context id along if you want to show audiences
     * from a specific context or higher. If omitted it will
     * load all system audiences (if the current user has the capability
     * to see them)
     */
    contextId: {
      type: Number,
    },
    // Display loading spinner on Add button
    showLoadingBtn: Boolean,
    forceResultsLoading: Boolean,
    /**
     * If the provided context is part of a tenant, only return audiences from within that tenant.
     */
    tenantScope: Boolean,
  },

  data() {
    return {
      audiences: null,
      audienceSelectedItems: [],
      filters: {
        search: '',
      },
      nextPage: false,
      skipQueries: true,
      searchDebounce: '',
    };
  },

  watch: {
    /**
     * On opening of adder, unblock query
     *
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
     * All audiences query
     *
     */
    this.$apollo.addSmartQuery('audiences', {
      query: this.customQuery ? this.customQuery : cohorts,
      fetchPolicy: 'network-only',
      skip() {
        return this.skipQueries;
      },
      variables() {
        const vars = {
          query: {
            leaf_context_id: this.contextId,
            filters: {
              name: this.filters.search,
            },
          },
        };
        if (this.tenantScope) {
          vars.query.tenant_scope = true;
        }
        return vars;
      },
      update({
        [this.customQueryKey ? this.customQueryKey : 'core_cohorts']: audiences,
      }) {
        this.nextPage = audiences.next_cursor ? audiences.next_cursor : false;
        return audiences;
      },
    });

    /**
     * Selected audiences query
     *
     */
    this.$apollo.addSmartQuery('selectedAudiences', {
      query: this.customQuery ? this.customQuery : cohorts,
      skip() {
        return this.skipQueries;
      },
      variables() {
        return {
          query: {
            leaf_context_id: this.contextId,
            filters: {
              ids: [],
            },
          },
        };
      },
      update({
        [this.customQueryKey
          ? this.customQueryKey
          : 'core_cohorts']: selectedAudiences,
      }) {
        this.audienceSelectedItems = selectedAudiences.items;
        return selectedAudiences;
      },
    });
  },

  methods: {
    /**
     * Load addition items and append to list
     *
     */
    async loadMoreItems() {
      if (!this.nextPage) {
        return;
      }

      const variables = {
        query: {
          leaf_context_id: this.contextId,
          cursor: this.nextPage,
          filters: {
            name: this.filters.search,
          },
        },
      };

      if (this.tenantScope) {
        variables.query.tenant_scope = true;
      }

      this.$apollo.queries.audiences.fetchMore({
        variables,

        updateQuery: (previousResult, { fetchMoreResult }) => {
          const oldData = previousResult.core_cohorts;
          const newData = fetchMoreResult.core_cohorts;
          const newList = oldData.items.concat(newData.items);

          return {
            [this.customQueryKey ? this.customQueryKey : 'core_cohorts']: {
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

      this.$emit('add-button-clicked');

      try {
        data = await this.updateSelectedItems(selection);
      } catch (error) {
        console.error(error);
        return;
      }
      this.$emit('added', { ids: selection, data: data });
    },

    /**
     * Update the selected items data
     *
     * @param {Array} selection
     */
    async updateSelectedItems(selection) {
      const numberOfItems = selection.length;

      try {
        await this.$apollo.queries.selectedAudiences.refetch({
          query: {
            leaf_context_id: this.contextId,
            filters: {
              ids: selection,
            },
            result_size: numberOfItems,
          },
        });
      } catch (error) {
        console.error(error);
      }
      return this.audienceSelectedItems;
    },

    /**
     * Update the search filter (which re-triggers the query) if the user stopped typing >500 milliseconds ago.
     *
     * @param {String} input Value from search filter input
     */
    updateFilterDebounced: debounce(function(input) {
      this.filters.search = input;
    }, 10),
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "filter_audiences",
    "filter_audiences_search_label",
    "select_audiences",
    "search"
  ],
  "totara_cohort": [
    "cohortname",
    "shortname"
  ]
}
</lang-strings>
