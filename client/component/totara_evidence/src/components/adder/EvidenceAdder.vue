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

  @author Brian Barnes <brian.barnes@totaralearning.com>
  @package totara_evidence
-->

<template>
  <Adder
    :open="open"
    :title="$str('select_evidence', 'totara_evidence')"
    :existing-items="existingItems"
    :loading="$apollo.loading"
    :show-load-more="nextPage"
    :show-loading-btn="showLoadingBtn"
    @added="closeWithData($event)"
    @cancel="cancelAdder()"
    @load-more="loadMoreItems()"
    @selected-tab-active="updateSelectedItems($event)"
  >
    <template v-slot:browse-filters>
      <FilterBar
        :has-top-bar="false"
        :title="$str('filter_evidence', 'totara_evidence')"
      >
        <template v-slot:filters-left="{ stacked }">
          <SelectFilter
            v-model="selectedEvidenceTypeId"
            :label="$str('evidence_type', 'totara_evidence')"
            :show-label="true"
            :options="evidenceTypeFilterOptions"
            :stacked="stacked"
          />
        </template>
        <template v-slot:filters-right="{ stacked }">
          <SearchFilter
            v-model="searchDebounce"
            :label="
              $str('filter_evidence_items_search_label', 'totara_evidence')
            "
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
        :data="evidence && evidence.items ? evidence.items : []"
        :disabled-ids="disabledItems"
        checkbox-v-align="center"
        :select-all-enabled="true"
        :border-bottom-hidden="true"
        @input="update($event)"
      >
        <template v-slot:header-row>
          <HeaderCell size="8" valign="center">
            {{ $str('header_evidence', 'totara_evidence') }}
          </HeaderCell>
          <HeaderCell size="4" valign="center">
            {{ $str('evidence_type', 'totara_evidence') }}
          </HeaderCell>
          <HeaderCell size="4" valign="center">
            {{ $str('date_created', 'totara_evidence') }}
          </HeaderCell>
        </template>

        <template v-slot:row="{ row }">
          <Cell
            size="8"
            valign="center"
            :column-header="$str('header_evidence', 'totara_evidence')"
          >
            {{ row.name }}
          </Cell>

          <Cell
            size="4"
            valign="center"
            :column-header="$str('evidence_type', 'totara_evidence')"
          >
            {{ row.type.name }}
          </Cell>
          <Cell
            :column-header="$str('date_created', 'totara_evidence')"
            valign="center"
            size="4"
          >
            {{ row.created_at }}
          </Cell>
        </template>
      </SelectTable>
    </template>

    <template v-slot:basket-list="{ disabledItems, selectedItems, update }">
      <SelectTable
        :large-check-box="true"
        :no-label-offset="true"
        :value="selectedItems"
        :data="evidenceSelectedItems"
        :disabled-ids="disabledItems"
        checkbox-v-align="center"
        :border-bottom-hidden="true"
        :select-all-enabled="true"
        @input="update($event)"
      >
        <template v-slot:header-row>
          <HeaderCell size="8" valign="center">
            {{ $str('header_evidence', 'totara_evidence') }}
          </HeaderCell>
          <HeaderCell size="4" valign="center">
            {{ $str('evidence_type', 'totara_evidence') }}
          </HeaderCell>
          <HeaderCell size="4" valign="center">
            {{ $str('date_created', 'totara_evidence') }}
          </HeaderCell>
        </template>

        <template v-slot:row="{ row }">
          <Cell
            size="8"
            valign="center"
            :column-header="$str('header_evidence', 'totara_evidence')"
          >
            {{ row.name }}
          </Cell>

          <Cell
            size="4"
            valign="center"
            :column-header="$str('evidence_type', 'totara_evidence')"
          >
            {{ row.type.name }}
          </Cell>
          <Cell
            :column-header="$str('date_created', 'totara_evidence')"
            valign="center"
            size="4"
          >
            {{ row.created_at }}
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

//Queries
import evidenceQuery from 'totara_evidence/graphql/user_evidence_items';
import evidenceTypesQuery from 'totara_evidence/graphql/user_evidence_types';
import { debounce } from 'tui/util';

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
    // Display loading spinner on Add button
    showLoadingBtn: Boolean,
    userId: Number,
  },

  data() {
    return {
      evidence: null,
      evidenceSelectedItems: [],
      evidenceTypes: [],
      selectedEvidenceTypeId: 0,
      filters: {
        search: '',
        typeId: '',
      },
      nextPage: false,
      skipQueries: true,
      searchDebounce: '',
    };
  },

  computed: {
    evidenceTypeFilterOptions() {
      return this.mapFilterOptions(this.evidenceTypes);
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

    selectedEvidenceTypeId(newValue) {
      this.filters.typeId = newValue;
    },
  },

  created() {
    this.$apollo.addSmartQuery('evidence', {
      query: evidenceQuery,
      skip() {
        return this.skipQueries;
      },
      variables() {
        return {
          input: {
            filters: {
              search: this.filters.search,
              type_id: this.filters.typeId,
            },
            user_id: this.userId,
          },
        };
      },
      update({ ['evidence']: evidence }) {
        this.nextPage = evidence.next_cursor ? evidence.next_cursor : false;
        return evidence;
      },
    });

    /**
     * Selected evidence query
     */
    this.$apollo.addSmartQuery('selectedEvidence', {
      query: evidenceQuery,
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
      update({ ['evidence']: selectedEvidence }) {
        this.evidenceSelectedItems = selectedEvidence.items;
        return selectedEvidence;
      },
    });

    /**
     * Evidence types
     */
    this.$apollo.addSmartQuery('evidenceTypes', {
      query: evidenceTypesQuery,
      skip() {
        return this.skipQueries;
      },
      variables() {
        return {
          input: {
            user_id: this.userId,
          },
        };
      },
      update({ ['types']: evidenceTypes }) {
        return evidenceTypes.items;
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
      try {
        this.$apollo.queries.evidence.fetchMore({
          variables: {
            input: {
              cursor: this.nextPage,
              filters: {
                search: this.filters.search,
                type_id: this.filters.typeId,
              },
              user_id: this.userId,
            },
          },

          updateQuery: (previousResult, { fetchMoreResult }) => {
            const oldData = previousResult.evidence;
            const newData = fetchMoreResult.evidence;
            const newList = oldData.items.concat(newData.items);

            return {
              ['evidence']: {
                items: newList,
                next_cursor: newData.next_cursor,
              },
            };
          },
        });
      } catch (error) {
        console.error(error);
      }
    },

    /**
     * Update the selected items data
     *
     * @param {Array} selection
     */
    async updateSelectedItems(selection) {
      const numberOfItems = selection.length;

      try {
        await this.$apollo.queries.selectedEvidence.refetch({
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
      return this.evidenceSelectedItems;
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
        this.selectedEvidenceTypeId = 0;
      } catch (error) {
        console.error(error);
        return;
      }
      this.$emit('added', { ids: selection, data: data });
    },

    /**
     * Cancel the adder
     */
    cancelAdder() {
      this.selectedEvidenceTypeId = 0;
      this.$emit('cancel');
    },

    /**
     * Map filter options to required format
     *
     * @param {Object} source
     * @return {Object}
     */
    mapFilterOptions(source) {
      let filters = source;

      filters = filters.map(f => {
        return {
          id: f.id,
          label: f.name,
        };
      });

      filters.unshift({
        id: 0,
        label: this.$str('all', 'core'),
      });

      return filters;
    },

    /**
     * Update the search filter (which re-triggers the query) if the user stopped typing >500 milliseconds ago.
     *
     * @param {String} input Value from search filter input
     */
    updateFilterDebounced: debounce(function(input) {
      this.filters.search = input;
    }, 500),
  },
};
</script>

<lang-strings>
  {
    "core": [
      "all"
    ],
    "totara_core": [
      "search"
    ],
    "totara_evidence": [
      "date_created",
      "evidence_type",
      "filter_evidence",
      "filter_evidence_items_search_label",
      "header_evidence",
      "select_evidence"
    ]
  }
</lang-strings>
