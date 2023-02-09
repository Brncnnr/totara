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
  @author Brian Barnes <brian.barnes@totaralearning.com>
  @module totara_core
-->

<template>
  <Adder
    :open="open"
    :title="$str('select_learning', 'totara_core')"
    :existing-items="existingItems"
    :loading="$apollo.loading"
    :show-load-more="nextPage"
    :show-loading-btn="showLoadingBtn"
    @added="closeWithData($event)"
    @cancel="closeModal"
    @load-more="loadMoreItems"
    @selected-tab-active="updateSelectedItems($event)"
  >
    <template v-slot:browse-filters>
      <FilterBar
        :has-top-bar="false"
        :title="$str('filter_learning', 'totara_core')"
      >
        <template v-slot:filters-left="{ stacked }">
          <SelectFilter
            v-model="learningTypeFilter"
            :label="$str('header_learning_type', 'totara_core')"
            :show-label="true"
            :options="learningTypeFilterOptions"
            :stacked="stacked"
          />

          <SelectFilter
            v-model="progressFilter"
            :label="$str('header_learning_progress', 'totara_core')"
            :show-label="true"
            :options="progressOptions"
            :stacked="stacked"
          />
        </template>

        <template v-slot:filters-right="{ stacked }">
          <SearchFilter
            v-model="searchDebounce"
            :label="$str('filter_learning_search_label', 'totara_core')"
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
        :data="learningItems && learningItems.items ? learningItems.items : []"
        :disabled-ids="disabledItems"
        checkbox-v-align="center"
        :select-all-enabled="true"
        :border-bottom-hidden="true"
        :get-id="(row, index) => ('unique_id' in row ? row.unique_id : index)"
        @input="update($event)"
      >
        <template v-slot:header-row>
          <HeaderCell size="5" valign="center">
            {{ $str('header_learning_name', 'totara_core') }}
          </HeaderCell>
          <HeaderCell size="4" valign="center">
            {{ $str('header_learning_type', 'totara_core') }}
          </HeaderCell>
          <HeaderCell size="3" align="center" valign="center">
            {{ $str('header_learning_progress', 'totara_core') }}
          </HeaderCell>
        </template>

        <template v-slot:row="{ row }">
          <Cell
            size="5"
            :column-header="$str('header_learning_name', 'totara_core')"
            valign="center"
          >
            {{ row.fullname }}
          </Cell>

          <Cell
            size="4"
            :column-header="$str('header_learning_type', 'totara_core')"
            valign="center"
          >
            {{ typeName(row.itemtype) }}
          </Cell>

          <Cell
            size="3"
            :column-header="$str('header_learning_progress', 'totara_core')"
            align="center"
            valign="center"
          >
            <template v-if="row.progress || row.progress === 0">
              {{ $str('xpercent', 'core', row.progress) }}
            </template>
            <template v-else>
              {{ $str('statusnottracked', 'core_completion') }}
            </template>
          </Cell>
        </template>
      </SelectTable>
    </template>

    <template v-slot:basket-list="{ disabledItems, selectedItems, update }">
      <SelectTable
        :large-check-box="true"
        :no-label-offset="true"
        :value="selectedItems"
        :data="learningSelectedItems"
        :disabled-ids="disabledItems"
        checkbox-v-align="center"
        :border-bottom-hidden="true"
        :select-all-enabled="true"
        :get-id="(row, index) => ('unique_id' in row ? row.unique_id : index)"
        @input="update($event)"
      >
        <template v-slot:header-row>
          <HeaderCell size="5" valign="center">
            {{ $str('header_learning_name', 'totara_core') }}
          </HeaderCell>
          <HeaderCell size="4" valign="center">
            {{ $str('header_learning_type', 'totara_core') }}
          </HeaderCell>
          <HeaderCell size="3" align="center" valign="center">
            {{ $str('header_learning_progress', 'totara_core') }}
          </HeaderCell>
        </template>

        <template v-slot:row="{ row }">
          <Cell
            size="5"
            :column-header="$str('header_learning_name', 'totara_core')"
            valign="center"
          >
            {{ row.fullname }}
          </Cell>

          <Cell
            size="4"
            :column-header="$str('header_learning_type', 'totara_core')"
            valign="center"
          >
            {{ typeName(row.itemtype) }}
          </Cell>

          <Cell
            size="3"
            :column-header="$str('header_learning_progress', 'totara_core')"
            align="center"
            valign="center"
          >
            <template v-if="row.progress || row.progress === 0">
              {{ $str('xpercent', 'core', row.progress) }}
            </template>
            <template v-else>
              {{ $str('statusnottracked', 'core_completion') }}
            </template>
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
import SelectFilter from 'tui/components/filters/SelectFilter';
import SelectTable from 'tui/components/datatable/SelectTable';
// Queries
import learningQuery from 'totara_core/graphql/user_learning_items';
import selectedLearningQuery from 'totara_core/graphql/user_learning_items_selected';
import { debounce } from 'tui/util';

const LEARNING_TYPE_DEFAULT = 'COURSE';

export default {
  components: {
    Adder,
    Cell,
    FilterBar,
    HeaderCell,
    SearchFilter,
    SelectFilter,
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
    // Display loading spinner on Add button
    showLoadingBtn: Boolean,
    userId: Number,
  },

  data() {
    return {
      learningItems: null,
      learningSelectedItems: [],
      searchFilter: '',
      nextPage: false,
      skipQueries: true,
      searchDebounce: '',
      learningTypeFilter: LEARNING_TYPE_DEFAULT,
      learningTypeFilterOptions: [
        {
          id: 'COURSE',
          label: this.$str('learning_type_course', 'totara_core'),
        },
        {
          id: 'CERTIFICATION',
          label: this.$str('learning_type_certification', 'totara_core'),
        },
        {
          id: 'PROGRAM',
          label: this.$str('learning_type_program', 'totara_core'),
        },
      ],
      progressFilter: null,
      progressOptions: [
        { id: null, label: this.$str('all', 'totara_core') },
        {
          id: 'COMPLETED',
          label: this.$str('completed', 'core_completion'),
        },
        {
          id: 'IN_PROGRESS',
          label: this.$str('inprogress', 'core_completion'),
        },
        {
          id: 'NOT_STARTED',
          label: this.$str('statusnotstarted', 'core_completion'),
        },
        {
          id: 'NOT_TRACKED',
          label: this.$str('statusnottracked', 'core_completion'),
        },
      ],
      // Default to course
      typeValue: 'COURSE',
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
     * All user learning query
     *
     */
    this.$apollo.addSmartQuery('learningItems', {
      query: this.customQuery ? this.customQuery : learningQuery,
      skip() {
        return this.skipQueries;
      },
      variables() {
        return {
          input: {
            filters: {
              search: this.searchFilter,
              type: this.learningTypeFilter,
              progress: this.progressFilter,
            },
            user_id: this.userId,
          },
        };
      },
      update({
        [this.customQueryKey
          ? this.customQueryKey
          : 'totara_core_user_learning_items']: learning,
      }) {
        this.nextPage = learning.next_cursor ? learning.next_cursor : false;
        return learning;
      },
    });

    /**
     * Selected learning items query
     *
     */
    this.$apollo.addSmartQuery('selectedLearning', {
      query: this.customQuery ? this.customQuery : selectedLearningQuery,
      skip() {
        return this.skipQueries;
      },
      variables() {
        return {
          input: {
            filters: {
              ids: [],
            },
            user_id: this.userId,
          },
        };
      },
      update({
        [this.customQueryKey
          ? this.customQueryKey
          : 'totara_core_user_learning_items_selected']: selectedLearning,
      }) {
        this.learningSelectedItems = selectedLearning.items;
        return selectedLearning;
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

      this.$apollo.queries.learningItems.fetchMore({
        variables: {
          input: {
            cursor: this.nextPage,
            filters: {
              search: this.searchFilter,
              type: this.learningTypeFilter,
              progress: this.progressFilter,
            },
            user_id: this.userId,
          },
        },

        updateQuery: (previousResult, { fetchMoreResult }) => {
          const oldData = previousResult.totara_core_user_learning_items;
          const newData = fetchMoreResult.totara_core_user_learning_items;
          const newList = oldData.items.concat(newData.items);

          return {
            [this.customQueryKey
              ? this.customQueryKey
              : 'totara_core_user_learning_items']: {
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

    /**
     * Close adder without saving
     */
    closeModal() {
      this.learningTypeFilter = LEARNING_TYPE_DEFAULT;
      this.progressFilter = null;
      this.$emit('cancel');
    },

    /**
     * Update the selected items data
     *
     * @param {Array} selection
     */
    async updateSelectedItems(selection) {
      const numberOfItems = selection.length;

      try {
        await this.$apollo.queries.selectedLearning.refetch({
          input: {
            filters: {
              ids: selection,
            },
            result_size: numberOfItems,
            user_id: this.userId,
          },
        });
      } catch (error) {
        console.error(error);
      }
      return this.learningSelectedItems;
    },

    /**
     * Update the search filter (which re-triggers the query) if the user stopped typing >500 milliseconds ago.
     *
     * @param {String} input Value from search filter input
     */
    updateFilterDebounced: debounce(function(input) {
      this.searchFilter = input;
    }, 500),

    /**
     * Returns the human readable name for the learning type
     *
     * @returns {String}
     */
    typeName(type) {
      if (!type) {
        return this.$str('learning_type_unknown', 'totara_core');
      }

      let typeName = this.$str('learning_type_' + type, 'totara_core');

      if (!typeName) {
        typeName = this.$str('learning_type_unknown', 'totara_core');
      }

      return typeName;
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "no",
      "yes",
      "xpercent"
    ],
    "core_completion": [
      "completed",
      "inprogress",
      "statusnotstarted",
      "statusnottracked"
    ],
    "totara_core": [
      "all",
      "filter_learning",
      "filter_learning_search_label",
      "header_learning_name",
      "header_learning_type",
      "header_learning_progress",
      "learning_type_certification",
      "learning_type_course",
      "learning_type_program",
      "learning_type_unknown",
      "search",
      "select_learning"
    ]
  }
</lang-strings>
