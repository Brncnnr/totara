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
  @package totara_contentmarketplace
-->

<template>
  <div class="tui-contentMarketplaceImportReviewTable">
    <SelectTable
      :border-bottom-hidden="true"
      :data="items"
      :hover-off="true"
      :no-label-offset="true"
      :row-label-key="rowLabelKey"
      :select-all-enabled="true"
      :selected-highlight-off="true"
      :value="selectedItems"
      @input="$emit('update', $event)"
    >
      <template v-slot:header-row>
        <HeaderCell size="12" valign="center" />
      </template>

      <template v-slot:row="{ checked, row }">
        <Cell size="12">
          <slot name="row" :checked="checked" :row="row" />
        </Cell>
      </template>
    </SelectTable>
  </div>
</template>

<script>
// Components
import Cell from 'tui/components/datatable/Cell';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import SelectTable from 'tui/components/datatable/SelectTable';

export default {
  components: {
    Cell,
    HeaderCell,
    SelectTable,
  },

  props: {
    // Course data
    items: {
      type: Array,
      required: true,
    },
    // Used for populating accessibility string on rows
    rowLabelKey: {
      type: String,
      required: true,
    },
    // List of selected item ID's
    selectedItems: {
      type: Array,
      required: true,
    },
  },
};
</script>
