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
  @package contentmarketplace_linkedin
-->

<template>
  <FilterSidePanel
    v-model="value"
    :skip-content-id="contentId"
    :title="$str('filters_title', 'contentmarketplace_linkedin')"
  >
    <!-- Search filter -->
    <SearchFilter
      v-model="value.search"
      :label="$str('a11y_search_filter', 'contentmarketplace_linkedin')"
      :placeholder="
        $str('search_filter_placeholder', 'contentmarketplace_linkedin')
      "
    />

    <div>
      <!-- Subject tree filter -->
      <Tree
        v-model="openNodes.subjects"
        :header-level="4"
        :separator="true"
        :tree-data="filters.subjects"
      >
        <template v-slot:content="{ content, label }">
          <MultiSelectFilter
            v-model="value.subjects"
            :hidden-title="true"
            :options="content"
            :title="label"
            :visible-item-limit="5"
          />
        </template>
      </Tree>

      <!-- Time tree filter -->
      <Tree
        v-model="openNodes.time_to_complete"
        :header-level="4"
        :separator="true"
        :tree-data="filters.time_to_complete"
      >
        <template v-slot:content="{ content, label }">
          <MultiSelectFilter
            v-model="value.time_to_complete"
            :hidden-title="true"
            :options="content"
            :title="label"
            :visible-item-limit="5"
          />
        </template>
      </Tree>

      <!-- In catalog tree filter -->
      <Tree
        v-model="openNodes.in_catalog"
        :header-level="4"
        :separator="true"
        :tree-data="filters.in_catalog"
      >
        <template v-slot:content="{ content, label }">
          <MultiSelectFilter
            v-model="value.in_catalog"
            :hidden-title="true"
            :options="content"
            :title="label"
            :visible-item-limit="5"
          />
        </template>
      </Tree>
    </div>
  </FilterSidePanel>
</template>

<script>
import FilterSidePanel from 'tui/components/filters/FilterSidePanel';
import MultiSelectFilter from 'tui/components/filters/MultiSelectFilter';
import SearchFilter from 'tui/components/filters/SearchFilter';
import Tree from 'tui/components/tree/Tree';

export default {
  components: {
    FilterSidePanel,
    MultiSelectFilter,
    SearchFilter,
    Tree,
  },

  props: {
    contentId: String,
    filters: {
      type: Object,
      required: true,
    },
    openNodes: Object,
    value: Object,
  },
};
</script>

<lang-strings>
  {
    "contentmarketplace_linkedin": [
      "a11y_search_filter",
      "filters_title",
      "search_filter_placeholder"
    ]
  }
</lang-strings>
