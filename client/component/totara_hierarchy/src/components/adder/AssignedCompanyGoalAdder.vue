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

  @author Murali Nair <murali.nair@totaralearning.com>
  @package totara_hierarchy
-->

<template>
  <Adder
    :open="open"
    :title="$str('assigned_company_goal_adder_title', 'totara_hierarchy')"
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
        :title="
          $str('assigned_company_goal_adder_filter_title', 'totara_hierarchy')
        "
      >
        <template v-slot:filters-left="{ stacked }">
          <SelectFilter
            v-if="hasFrameworkFilterOptions"
            v-model="filters.framework"
            :label="
              $str(
                'assigned_company_goal_adder_filter_framework',
                'totara_hierarchy'
              )
            "
            :show-label="true"
            :options="companyGoalFrameworksFilterOptions"
            :stacked="stacked"
          />
          <SelectFilter
            v-if="hasTypeFilterOptions"
            v-model="filters.type"
            :label="
              $str(
                'assigned_company_goal_adder_filter_type',
                'totara_hierarchy'
              )
            "
            :show-label="true"
            :options="companyGoalTypesFilterOptions"
            :stacked="stacked"
          />
          <SearchFilter
            v-model="searchDebounce"
            :label="
              $str(
                'assigned_company_goal_adder_filter_name',
                'totara_hierarchy'
              )
            "
            :show-label="false"
            :placeholder="
              $str(
                'assigned_company_goal_adder_filter_name',
                'totara_hierarchy'
              )
            "
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
        :data="goals && goals.items ? goals.items : []"
        :disabled-ids="disabledItems"
        checkbox-v-align="center"
        :select-all-enabled="true"
        :border-bottom-hidden="true"
        :no-items-text="
          $str('assigned_company_goal_adder_no_items', 'totara_hierarchy')
        "
        @input="update($event)"
      >
        <template v-slot:header-row>
          <HeaderCell size="8" valign="center">
            {{
              $str('assigned_company_goal_adder_label_name', 'totara_hierarchy')
            }}
          </HeaderCell>
          <HeaderCell size="4" valign="center">
            {{ $str('assigned_company_goal_adder_type', 'totara_hierarchy') }}
          </HeaderCell>
          <HeaderCell v-if="showMainTargetDates()" size="4" valign="center">
            {{
              $str(
                'assigned_company_goal_adder_label_target_date',
                'totara_hierarchy'
              )
            }}
          </HeaderCell>
        </template>

        <template v-slot:row="{ row }">
          <Cell
            size="8"
            :column-header="
              $str('assigned_company_goal_adder_label_name', 'totara_hierarchy')
            "
            valign="center"
          >
            {{ row.goal.full_name }}
          </Cell>

          <Cell
            size="4"
            :column-header="
              $str('assigned_company_goal_adder_type', 'totara_hierarchy')
            "
            valign="center"
          >
            {{ row.goal.type_name }}
          </Cell>

          <Cell
            v-if="showMainTargetDates()"
            size="4"
            :column-header="
              $str(
                'assigned_company_goal_adder_label_target_date',
                'totara_hierarchy'
              )
            "
            valign="center"
          >
            {{ formatTargetDate(row.goal.target_date) }}
          </Cell>
        </template>
      </SelectTable>
    </template>

    <template v-slot:basket-list="{ disabledItems, selectedItems, update }">
      <SelectTable
        :large-check-box="true"
        :no-label-offset="true"
        :value="selectedItems"
        :data="goalSelectedItems"
        :disabled-ids="disabledItems"
        checkbox-v-align="center"
        :border-bottom-hidden="true"
        :select-all-enabled="true"
        @input="update($event)"
      >
        <template v-slot:header-row>
          <HeaderCell size="8" valign="center">
            {{
              $str('assigned_company_goal_adder_label_name', 'totara_hierarchy')
            }}
          </HeaderCell>
          <HeaderCell size="4" valign="center">
            {{ $str('assigned_company_goal_adder_type', 'totara_hierarchy') }}
          </HeaderCell>
          <HeaderCell v-if="showSelectedTargetDates()" size="4" valign="center">
            {{
              $str(
                'assigned_company_goal_adder_label_target_date',
                'totara_hierarchy'
              )
            }}
          </HeaderCell>
        </template>

        <template v-slot:row="{ row }">
          <Cell
            size="8"
            :column-header="
              $str('assigned_company_goal_adder_label_name', 'totara_hierarchy')
            "
            valign="center"
          >
            {{ row.goal.full_name }}
          </Cell>

          <Cell
            size="4"
            :column-header="
              $str('assigned_company_goal_adder_type', 'totara_hierarchy')
            "
            valign="center"
          >
            {{ row.goal.type_name }}
          </Cell>

          <Cell
            v-if="showSelectedTargetDates()"
            size="4"
            :column-header="
              $str(
                'assigned_company_goal_adder_label_target_date',
                'totara_hierarchy'
              )
            "
            valign="center"
          >
            {{ formatTargetDate(row.goal.target_date) }}
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
import { debounce } from 'tui/util';

//Queries
import assigned_company_goals from 'totara_hierarchy/graphql/assigned_company_goals';
import goal_filters from 'totara_hierarchy/graphql/goal_filters';

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
    excludeFilterOptionsWithoutAssociatedGoals: {
      type: Boolean,
      default: true,
    },
    existingItems: {
      type: Array,
      default: () => [],
    },
    open: Boolean,
    customQuery: Object,
    customQueryKey: String,
    showLoadingBtn: Boolean,
    userId: Number,
  },

  data() {
    return {
      companyGoalFrameworksFilterOptions: [],
      companyGoalTypesFilterOptions: [],
      filters: {
        framework: null,
        search: '',
        type: null,
      },
      goals: null,
      goalSelectedItems: [],
      nextPage: false,
      searchDebounce: '',
      skipQueries: true,
      stacked: false,
    };
  },

  computed: {
    hasFrameworkFilterOptions() {
      return this.companyGoalFrameworksFilterOptions.length > 1;
    },

    hasTypeFilterOptions() {
      return this.companyGoalTypesFilterOptions.length > 1;
    },
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
    this.$apollo.addSmartQuery('goals', {
      query: this.customQuery ? this.customQuery : assigned_company_goals,
      skip() {
        return this.skipQueries;
      },
      variables() {
        return {
          input: {
            filters: {
              framework_id: this.filters.framework,
              goal_name: this.filters.search,
              type_id: this.filters.type,
              user_id: this.userId,
            },
            order_by: 'TARGET_DATE',
            order_dir: 'DESC',
          },
        };
      },
      update({
        [this.customQueryKey
          ? this.customQueryKey
          : 'totara_hierarchy_assigned_company_goals']: goals,
      }) {
        this.nextPage = goals.next_cursor ? goals.next_cursor : false;
        return goals;
      },
    });

    this.$apollo.addSmartQuery('selectedGoals', {
      query: this.customQuery ? this.customQuery : assigned_company_goals,
      skip() {
        return this.skipQueries;
      },
      variables() {
        return {
          input: {
            filters: {
              ids: [],
              user_id: this.userId,
            },
            order_by: 'TARGET_DATE',
            order_dir: 'DESC',
          },
        };
      },
      update({
        [this.customQueryKey
          ? this.customQueryKey
          : 'totara_hierarchy_assigned_company_goals']: selectedGoals,
      }) {
        this.goalSelectedItems = selectedGoals.items;
        return selectedGoals;
      },
    });

    /**
     * Framework filters query
     */

    this.$apollo.addSmartQuery('companyGoalFrameworks', {
      query: this.customQuery ? this.customQuery : goal_filters,
      skip() {
        return this.skipQueries;
      },
      variables: {
        input: {
          check_goal_exist: this.excludeFilterOptionsWithoutAssociatedGoals,
        },
      },
      update({
        [this.customQueryKey
          ? this.customQueryKey
          : 'company_goal_frameworks']: companyGoalFrameworks,
      }) {
        this.companyGoalFrameworksFilterOptions = this.formatFilterItems(
          companyGoalFrameworks
        );

        return companyGoalFrameworks;
      },
    });

    /**
     * Types filters query
     */

    this.$apollo.addSmartQuery('companyGoalTypes', {
      query: this.customQuery ? this.customQuery : goal_filters,
      skip() {
        return this.skipQueries;
      },
      variables: {
        input: {
          check_goal_exist: this.excludeFilterOptionsWithoutAssociatedGoals,
        },
      },
      update({
        [this.customQueryKey
          ? this.customQueryKey
          : 'company_goal_types']: companyGoalTypes,
      }) {
        this.companyGoalTypesFilterOptions = this.formatFilterItems(
          companyGoalTypes
        ).concat([
          { id: 0, label: this.$str('unclassified', 'totara_hierarchy') },
        ]);

        return companyGoalTypes;
      },
    });
  },

  methods: {
    formatFilterItems({ items }) {
      let result = [];
      const filterOptions = items.map(({ fullname, id }) => {
        return {
          label: fullname,
          id: id,
        };
      });
      if (filterOptions) {
        const firstOption = [{ id: null, label: this.$str('all', 'core') }];
        result = firstOption.concat(filterOptions);
      }
      return result;
    },

    formatTargetDate(date) {
      return date ? date : '-';
    },

    showMainTargetDates() {
      const items = this.goals && this.goals.items ? this.goals.items : [];
      const dates = items.filter(item => item.goal.target_date);
      return dates.length > 0;
    },

    showSelectedTargetDates() {
      const dates = this.goalSelectedItems.filter(
        item => item.goal.target_date
      );
      return dates.length > 0;
    },

    async loadMoreItems() {
      if (!this.nextPage) {
        return;
      }
      try {
        this.$apollo.queries.goals.fetchMore({
          variables: {
            input: {
              cursor: this.nextPage,
              filters: {
                goal_name: this.filters.search,
                user_id: this.userId,
              },
              order_by: 'TARGET_DATE',
              order_dir: 'DESC',
            },
          },

          updateQuery: (previousResult, { fetchMoreResult }) => {
            const oldData =
              previousResult.totara_hierarchy_assigned_company_goals;
            const newData =
              fetchMoreResult.totara_hierarchy_assigned_company_goals;
            const newList = oldData.items.concat(newData.items);

            return {
              [this.customQueryKey
                ? this.customQueryKey
                : 'totara_hierarchy_assigned_company_goals']: {
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

    async updateSelectedItems(selection) {
      const numberOfItems = selection.length;

      try {
        await this.$apollo.queries.selectedGoals.refetch({
          input: {
            filters: {
              ids: selection,
              user_id: this.userId,
            },
            order_by: 'TARGET_DATE',
            order_dir: 'DESC',
            result_size: numberOfItems,
          },
        });
      } catch (error) {
        console.error(error);
      }

      return this.goalSelectedItems;
    },

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

    cancelAdder() {
      this.$emit('cancel');
    },

    updateFilterDebounced: debounce(function(input) {
      this.filters.search = input;
    }, 500),
  },
};
</script>

<style lang="scss">
.tui-filterBar {
  &__filters {
    &-icon {
      margin-right: var(--gap-4);
    }
  }
}
</style>

<lang-strings>
  {
    "totara_hierarchy": [
      "assigned_company_goal_adder_filter_framework",
      "assigned_company_goal_adder_filter_title",
      "assigned_company_goal_adder_filter_type",
      "assigned_company_goal_adder_filter_name",
      "assigned_company_goal_adder_label_name",
      "assigned_company_goal_adder_label_target_date",
      "assigned_company_goal_adder_no_items",
      "assigned_company_goal_adder_title",
      "assigned_company_goal_adder_type",
      "unclassified"
    ],
    "core": [
      "all"
    ]
  }
</lang-strings>
