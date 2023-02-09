<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Arshad Anwer <arshad.anwer@totaralearning.com>
  @package totara_contentmarketplace
-->

<template>
  <div class="tui-contentMarketplaceImportTable">
    <Table :border-bottom-hidden="true" :data="items">
      <template v-slot:header-row>
        <HeaderCell v-if="$slots['row-header']" size="12" valign="center">
          <slot name="row-header" />
        </HeaderCell>
      </template>

      <template v-slot:row="{ row }">
        <Cell size="12">
          <slot name="row" :row="row" />
          <a
            role="button"
            class="tui-contentMarketplaceImportTable__link"
            href="#"
            :aria-label="row.name"
            @click.prevent="$emit('select', row)"
          />
        </Cell>
      </template>
    </Table>
  </div>
</template>

<script>
// Components
import Cell from 'tui/components/datatable/Cell';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import Table from 'tui/components/datatable/Table';

export default {
  components: {
    Cell,
    HeaderCell,
    Table,
  },

  props: {
    // Course data
    items: {
      type: Array,
      required: true,
    },
  },
};
</script>

<style lang="scss">
.tui-contentMarketplaceImportTable {
  &__link {
    position: absolute;
    top: 0;
    width: 100%;
    height: 100%;
  }
}
</style>
