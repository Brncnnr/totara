<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module mod_contentmarketplace
-->
<template>
  <!-- A page to list all the content marketplace modules within the course -->
  <LayoutOneColumn :title="heading">
    <template v-slot:content>
      <Table :data="marketplaceRecords">
        <template v-slot:header-row>
          <HeaderCell>
            {{ $str('name', 'core') }}
          </HeaderCell>
          <HeaderCell>
            {{ $str('marketplace_component', 'mod_contentmarketplace') }}
          </HeaderCell>
        </template>

        <template v-slot:row="{ row }">
          <Cell :column-header="$str('name', 'core')">
            <a
              :href="
                $url('/mod/contentmarketplace/view.php', { id: row.cm_id })
              "
            >
              {{ row.name }}
            </a>
          </Cell>
          <Cell
            :column-header="
              $str('marketplace_component', 'mod_contentmarketplace')
            "
          >
            {{ row.component_name }}
          </Cell>
        </template>
      </Table>
    </template>
  </LayoutOneColumn>
</template>

<script>
import LayoutOneColumn from 'tui/components/layouts/LayoutOneColumn';
import Table from 'tui/components/datatable/Table';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import Cell from 'tui/components/datatable/Cell';

export default {
  components: {
    LayoutOneColumn,
    Table,
    HeaderCell,
    Cell,
  },
  props: {
    marketplaceRecords: {
      type: Array,
      required: true,
      validator(records) {
        return records.every(record => {
          return (
            'name' in record && 'component_name' in record && 'cm_id' in record
          );
        });
      },
    },
    heading: {
      type: String,
      required: true,
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "name"
  ],
  "mod_contentmarketplace": [
    "marketplace_component"
  ]
}
</lang-strings>
