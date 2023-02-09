<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Arshad Anwer <arshad.anwer@totaralearning.com>
  @module tui
-->

<template>
  <Adder
    class="tui-hierarchicalAdder"
    :open="open"
    :title="adderTitle"
    :existing-items="existingItems"
    :loading="$apollo.loading && !showLoadingBtn"
    :show-load-more="nextPage"
    :show-loading-btn="showLoadingBtn"
    @added="closeWithData($event)"
    @cancel="cancel"
    @load-more="loadMoreItems()"
    @selected-tab-active="updateSelectedItems($event)"
  >
    <template v-if="$scopedSlots.notices" v-slot:notices>
      <slot name="notices" />
    </template>
    <template v-slot:browse-filters>
      <FilterBar :has-top-bar="false" :title="filterTitle">
        <template v-slot:filters-left="{ stacked }">
          <SelectFilter
            v-model="filters.framework_id"
            :label="$str('hierarchy_framework', 'totara_core')"
            :show-label="true"
            :options="frameworkList"
            :stacked="stacked"
            @input="onChange"
          />
        </template>
        <template v-slot:filters-right="{ stacked }">
          <SearchFilter
            v-model="searchDebounce"
            :label="$str('search_hierarchy', 'totara_core')"
            :show-label="false"
            :placeholder="$str('search', 'totara_core')"
            :stacked="stacked"
            @input="searchOnChange"
          />
        </template>
      </FilterBar>
    </template>

    <template v-if="!filters.name && currentParent" v-slot:pre-list>
      <HierarchicalParentButton
        :label="currentParent.fullname"
        @click="goBackToParent"
      />
    </template>

    <template v-slot:browse-list="{ disabledItems, selectedItems, update }">
      <SelectTable
        :large-check-box="true"
        :no-label-offset="true"
        :value="selectedItems"
        :data="
          hierarchicalList && hierarchicalList.items
            ? hierarchicalList.items
            : []
        "
        :disabled-ids="disabledItems"
        checkbox-v-align="center"
        :select-all-enabled="true"
        :border-bottom-hidden="true"
        @input="update($event)"
      >
        <template v-slot:header-row>
          <HeaderCell size="11" valign="center">{{
            tableHeaderName
          }}</HeaderCell>
          <HeaderCell size="1" valign="center" />
        </template>

        <template v-slot:row="{ row }">
          <Cell size="11" :column-header="tableHeaderName" valign="center">{{
            row.fullname
          }}</Cell>
          <Cell size="1" align="end"
            ><HasChildrenCell
              v-if="row.children && row.children.length > 0"
              :current-parent="row"
              @gotochild="goToChild(row)"
          /></Cell>
        </template>
      </SelectTable>
    </template>

    <template v-slot:basket-list="{ disabledItems, selectedItems, update }">
      <SelectTable
        :large-check-box="true"
        :no-label-offset="true"
        :value="selectedItems"
        :data="selectedHierarchicalList"
        :disabled-ids="disabledItems"
        checkbox-v-align="center"
        :border-bottom-hidden="true"
        :select-all-enabled="true"
        @input="update($event)"
      >
        <template v-slot:header-row>
          <HeaderCell size="12" valign="center">{{
            tableHeaderName
          }}</HeaderCell>
        </template>

        <template v-slot:row="{ row }">
          <Cell size="12" :column-header="tableHeaderName" valign="center">{{
            row.fullname
          }}</Cell>
        </template>
      </SelectTable>
    </template>
  </Adder>
</template>

<script>
import { debounce } from 'tui/util';

// Components
import Adder from 'tui/components/adder/Adder';
import Cell from 'tui/components/datatable/Cell';
import HasChildrenCell from 'tui/components/datatable/HasChildrenCell';
import FilterBar from 'tui/components/filters/FilterBar';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import SearchFilter from 'tui/components/filters/SearchFilter';
import SelectTable from 'tui/components/datatable/SelectTable';
import SelectFilter from 'tui/components/filters/SelectFilter';
import HierarchicalParentButton from 'tui/components/adder/HierarchicalParentButton';

export default {
  components: {
    Adder,
    Cell,
    HasChildrenCell,
    FilterBar,
    HeaderCell,
    SearchFilter,
    SelectTable,
    SelectFilter,
    HierarchicalParentButton,
  },

  props: {
    existingItems: {
      type: Array,
      default: () => [],
    },
    open: Boolean,
    customQuery: {
      type: Object,
      required: true,
    },
    /**
     * custom query key needs to be passed
     * if customQuery is passed
     */
    customQueryKey: {
      type: String,
      required: true,
    },
    customFrameworkQuery: {
      type: Object,
      required: true,
    },
    /**
     * custom framework query key needs to be passed
     * if customFrameworkQuery is passed
     */
    customFrameworkQueryKey: {
      type: String,
      required: true,
    },
    adderTitle: {
      type: String,
      required: true,
    },
    filterTitle: {
      type: String,
      required: true,
    },
    // Display loading spinner on Add button
    showLoadingBtn: Boolean,
    tableHeaderName: {
      type: String,
      required: true,
    },
  },

  data() {
    return {
      filters: {
        framework_id: 1,
        parent_id: 0,
        name: '',
      },
      nextPage: false,
      skipQueries: true,
      currentParent: null,
      frameworkList: [],
      selectedFrameworkId: 1,
      hierarchicalList: [],
      selectedHierarchicalList: [],
      cachedFilters: {},
      searchDebounce: '',
    };
  },

  watch: {
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

  created() {
    this.$apollo.addSmartQuery('frameworkList', {
      query: this.customFrameworkQuery,
      skip() {
        return this.skipQueries;
      },
      update({ [this.customFrameworkQueryKey]: frameworks }) {
        if (frameworks.length > 0) {
          this.filters.framework_id = frameworks[0].id;
        }

        frameworks = frameworks.map(({ id, fullname }) => {
          return {
            id: id,
            value: id,
            label: fullname,
          };
        });

        return frameworks;
      },
    });

    this.$apollo.addSmartQuery('hierarchicalList', {
      query: this.customQuery,
      skip() {
        return this.frameworkList.length <= 0; //skip untill framework is loaded
      },
      variables() {
        return {
          query: {
            filters: Object.assign({}, this.filters),
          },
        };
      },
      update({ [this.customQueryKey]: data }) {
        this.nextPage = data.next_cursor ? data.next_cursor : false;
        return data;
      },
      result({ data: { [this.customQueryKey]: data } }) {
        // Update the current parent
        if (data.items.length > 0) {
          this.currentParent = data.items[0].parent && data.items[0].parent;
        }
      },
    });

    /**
     * Selected organisation query
     *
     */
    this.$apollo.addSmartQuery('selectedhierarchicalList', {
      query: this.customQuery,
      skip() {
        return this.skipQueries;
      },
      variables() {
        return {
          query: {
            filters: {
              ids: [],
            },
          },
        };
      },
      update({ [this.customQueryKey]: data }) {
        this.selectedHierarchicalList = data.items;
        return data;
      },
    });
  },

  methods: {
    /**
     * Get the current element's parent from path to navigate up the hierarchy
     */
    goBackToParent() {
      const ids = this.currentParent.path.split('/').filter(e => e.length != 0);
      ids.splice(ids.indexOf(this.currentParent.id), 1);

      // If ids have only one element then set the parent_id to 0
      // indicating the root level is framework
      if (ids.length === 0) {
        this.filters.parent_id = 0;
        this.currentParent = null;
        return;
      }
      this.filters.parent_id = ids[ids.length - 1];
    },

    goToChild(parent) {
      this.filters.parent_id = parent.id;
    },

    async loadMoreItems() {
      if (!this.nextPage) {
        return;
      }

      this.$apollo.queries.hierarchicalList.fetchMore({
        variables: {
          query: {
            cursor: this.nextPage,
            filters: {
              name: this.filters.name,
            },
          },
        },

        updateQuery: (previousResult, { fetchMoreResult }) => {
          const oldData = previousResult[this.customQueryKey];
          const newData = fetchMoreResult[this.customQueryKey];
          const newList = oldData.items.concat(newData.items);

          return {
            [this.customQueryKey]: {
              items: newList,
              next_cursor: newData.next_cursor,
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

    async updateSelectedItems(selection) {
      const numberOfItems = selection.length;
      try {
        await this.$apollo.queries.selectedhierarchicalList.refetch({
          query: {
            filters: {
              ids: selection,
            },
            result_size: numberOfItems,
          },
        });
      } catch (error) {
        console.log(error);
      }
      return this.selectedHierarchicalList;
    },

    onChange() {
      // reset to root level when framework value is changed
      this.filters.parent_id = 0;
      this.currentParent = null;
    },

    searchOnChange(value) {
      if (value.length === 1 && this.filters.parent_id !== null) {
        Object.assign(this.cachedFilters, this.filters);
      }

      if (value) {
        this.filters.parent_id = null;
      } else {
        this.filters.parent_id = this.cachedFilters.parent_id;
        this.cachedFilters = {};
      }
    },

    cancel() {
      this.filters = {
        framework_id: 1,
        parent_id: 0,
        name: '',
      };
      this.$emit('cancel');
    },

    /**
     * Update the search filter (which re-triggers the query) if the user stopped typing >500 milliseconds ago.
     *
     * @param {String} input Value from search filter input
     */
    updateFilterDebounced: debounce(function(input) {
      this.filters.name = input;
    }, 500),
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "hierarchy_framework",
    "search_hierarchy",
    "search"
  ]
}
</lang-strings>
