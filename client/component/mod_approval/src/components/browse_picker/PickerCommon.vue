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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module mod_approval
-->

<template>
  <div class="tui-mod_approval-browsePickerCommon">
    <div>
      <FilterBar
        v-if="filterFrameworks"
        :has-top-bar="true"
        :title="filterTitle"
      >
        <template v-slot:filters-left="{ stacked }">
          <SelectFilter
            v-model="filters.frameworkId"
            :label="$str('hierarchy_framework', 'totara_core')"
            :show-label="true"
            :options="frameworkOptions"
            :stacked="stacked"
          />
        </template>
        <template v-slot:filters-right="{ stacked }">
          <SearchFilter
            v-model="filters.search"
            :label="$str('search_hierarchy', 'totara_core')"
            :show-label="false"
            :placeholder="$str('search', 'totara_core')"
            :stacked="stacked"
            debounce-input
          />
        </template>
      </FilterBar>
      <FilterBar v-else :has-top-bar="true" :title="filterTitle">
        <template v-slot:filters-left="{ stacked }">
          <SearchFilter
            v-model="filters.search"
            :label="$str('search_hierarchy', 'totara_core')"
            :show-label="false"
            :placeholder="$str('search', 'totara_core')"
            :stacked="stacked"
            debounce-input
          />
        </template>
      </FilterBar>
    </div>

    <HierarchyNav
      v-if="hierarchy && !filters.name && currentParent"
      :current-name="itemName(currentParent)"
      @back="goBackToParent"
    />

    <div class="tui-mod_approval-browsePickerCommon__listScroll">
      <Table
        :class="{
          'tui-mod_approval-browsePickerCommon__table': true,
          'tui-mod_approval-browsePickerCommon__table--empty':
            resultsList.length === 0,
        }"
        :data="resultsList"
        :disabled-ids="disabledIds"
        :selection="[value]"
        :header-has-loaded="true"
        :loading-preview-rows="loadingPreviewRows"
        :loading-preview="resultsLoading"
        :loading-overlay-active="true"
        :no-items-text="$str('query_results_none', 'mod_approval')"
      >
        <template v-slot:header-row>
          <HeaderCell
            v-for="column in columns"
            :key="column.id"
            :size="String(column.size)"
            valign="center"
          >
            {{ column.label }}
          </HeaderCell>
          <HeaderCell v-if="hierarchy" size="1" />
        </template>

        <template v-slot:row="{ row }">
          <Cell
            v-for="(column, index) in columns"
            :key="column.id"
            :size="String(column.size)"
            :column-header="column.label"
            valign="center"
          >
            <Radio
              v-if="index === 0"
              :name="$id('radio')"
              :value="row"
              :checked="radioIsChecked(row)"
              :disabled="isDisabled(row.id)"
              @select="handleRadioSelect"
            >
              <slot :id="column.id" name="column" :row="row">
                {{ row[column.id] }}
              </slot>
            </Radio>
            <slot v-else :id="column.id" name="column" :row="row">
              {{ row[column.id] }}
            </slot>
          </Cell>
          <Cell v-if="hierarchy" size="1" align="end" valign="center">
            <div class="tui-mod_approval-browsePickerCommon__navigateDown">
              <ButtonIcon
                v-if="hasChildren(row)"
                :aria-label="
                  $str('navigate_down', 'totara_core', itemName(row))
                "
                :styleclass="{ transparent: true, transparentNoPadding: true }"
                @click="goToChild(row)"
              >
                <ForwardArrow />
              </ButtonIcon>
            </div>
          </Cell>
        </template>
      </Table>
      <div
        v-if="showLoadMore"
        class="tui-mod_approval-browsePickerCommon__loadMoreBar"
      >
        <Button :text="$str('loadmore', 'totara_core')" @click="loadMore" />
      </div>
      <Loader v-if="loadingMore" :loading="true" />
    </div>
    <div
      v-if="hierarchy"
      class="tui-mod_approval-browsePickerCommon__selectionInfo"
    >
      {{
        selectedName
          ? $str('item_selected_x', 'mod_approval', selectedName)
          : $str('no_item_selected', 'mod_approval')
      }}
    </div>
  </div>
</template>

<script>
import Cell from 'tui/components/datatable/Cell';
import FilterBar from 'tui/components/filters/FilterBar';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import SearchFilter from 'tui/components/filters/SearchFilter';
import Table from 'tui/components/datatable/Table';
import SelectFilter from 'tui/components/filters/SelectFilter';
import HierarchyNav from 'mod_approval/components/browse_picker/HierarchyNav';
import Radio from 'tui/components/form/Radio';
import ForwardArrow from 'tui/components/icons/ForwardArrow';
import Button from 'tui/components/buttons/Button';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import { showError } from 'tui/errors';
import ItemsLoader from './internal/ItemsLoader';
import Loader from 'tui/components/loading/Loader';

export default {
  components: {
    Cell,
    FilterBar,
    HeaderCell,
    SearchFilter,
    Table,
    SelectFilter,
    HierarchyNav,
    Radio,
    ForwardArrow,
    ButtonIcon,
    Button,
    Loader,
  },

  props: {
    // eslint-disable-next-line vue/require-prop-types
    value: {},
    filterFrameworks: Boolean,
    hierarchy: Boolean,
    filterTitle: {
      type: String,
      required: true,
    },
    forceLoading: Boolean,
    disabledIds: Array,
    getFrameworks: Function,
    getItems: {
      type: Function,
      required: true,
    },
    columns: {
      type: Array,
      required: true,
    },
    getItemName: Function,
  },

  data() {
    return {
      selectedName: null,
      selectedItemCache: null,
      filters: {
        search: null,
        frameworkId: null,
      },
      hierarchyChain: [],
      frameworkList: null,
      itemsQuery: null,
      initiallyLoaded: false,
    };
  },

  computed: {
    resultsLoading() {
      if (this.forceLoading) {
        return true;
      }

      if (this.filterFrameworks && this.frameworkList === null) {
        return true;
      }

      return this.itemsQuery.loading;
    },

    loadingMore() {
      return this.itemsQuery.loadingMore;
    },

    frameworkOptions() {
      if (!this.frameworkList) {
        return [];
      }
      return this.frameworkList.map(({ id, fullname }) => ({
        id: id,
        value: id,
        label: fullname,
      }));
    },

    resultsList() {
      return this.itemsQuery.value || [];
    },

    loadingPreviewRows() {
      const count = this.resultsList.length;
      return count === 0 ? 5 : count;
    },

    moreResults() {
      return this.itemsQuery.value && this.itemsQuery.hasMore;
    },

    currentParent() {
      return this.hierarchyChain[this.hierarchyChain.length - 1] || null;
    },

    resultsOptions() {
      return {
        filters: Object.assign({}, this.filters), // otherwise vue doesn't see this as changed
        parentId:
          this.hierarchy && !this.filters.search
            ? this.currentParent
              ? this.currentParent.id
              : -1 // = root
            : null, // = not hierarchical
      };
    },

    showLoadMore() {
      return this.itemsQuery.hasMore;
    },

    selectedFrameworkId() {
      return this.filters.frameworkId;
    },
  },

  watch: {
    resultsOptions(value) {
      this.itemsLoader.setVariables(value);
    },

    value: {
      handler(value) {
        // update selected item text

        if (!value) {
          this.selectedName = null;
          return;
        }

        if (this.selectedItemCache && this.selectedItemCache.id == value) {
          this.selectedName = this.itemName(this.selectedItemCache);
          return;
        }

        this.selectedName = '...';
        this.getItems({ ids: [value] })
          .then(result => {
            if (this.value != value) {
              return;
            }

            const item = result.items[0];
            if (item) {
              this.selectedItemCache = item;
              this.selectedName = this.itemName(item);
            } else {
              this.selectedName = null;
            }
          })
          .catch(showError);
      },
      immediate: true,
    },

    selectedFrameworkId() {
      this.hierarchyChain = [];
    },
  },

  created() {
    this.itemsLoader = new ItemsLoader({
      getItems: this.getItems,
      valueKey: 'items',
      onUpdate: () => {
        this.initiallyLoaded = true;
      },
      onError: showError,
    });
    this.itemsQuery = this.itemsLoader.state;
  },

  async mounted() {
    if (this.filterFrameworks) {
      this.frameworkList = await this.getFrameworks();
      if (this.filters.frameworkId == null && this.frameworkList[0]) {
        this.filters.frameworkId = this.frameworkList[0].id;
      }
    }
    this.itemsLoader.setVariables(this.resultsOptions);
  },

  methods: {
    goBackToParent() {
      this.hierarchyChain.pop();
    },

    goToChild(row) {
      this.filters.search = null;
      this.hierarchyChain.push(row);
    },

    handleRadioSelect(item) {
      this.selectedItemCache = item;
      this.$emit('input', item.id);
    },

    radioIsChecked(row) {
      return this.value == row.id;
    },

    handleSelectionUpdate(ids) {
      this.$emit('input', ids);
    },

    hasChildren(row) {
      return (
        row.children &&
        (!Array.isArray(row.children) || row.children.length > 0)
      );
    },

    isDisabled(id) {
      return this.disabledIds && this.disabledIds.some(x => x == id);
    },

    loadMore() {
      this.itemsLoader.loadMore();
    },

    itemName(row) {
      if (this.getItemName) {
        return this.getItemName(row);
      }
      const key = this.columns && this.columns[0] && this.columns[0].id;
      return row[key];
    },
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "hierarchy_framework",
    "search_hierarchy",
    "search",
    "loadmore",
    "navigate_up",
    "navigate_down"
  ],
  "mod_approval": [
    "no_item_selected",
    "item_selected_x",
    "query_results_none"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-browsePickerCommon {
  display: flex;
  flex-direction: column;

  &__table {
    margin: var(--gap-4) 0;
  }

  &__navigateDown {
    display: flex;
  }

  &__selectionInfo {
    margin-top: var(--gap-4);
    font-weight: bold;
  }

  &__listScroll {
    height: 100%;
    overflow-y: auto;
  }

  &__loadMoreBar {
    display: flex;
    justify-content: center;
    margin: var(--gap-4) 0;
  }
}
</style>
