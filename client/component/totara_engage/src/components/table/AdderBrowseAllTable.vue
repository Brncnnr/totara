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

  @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
  @module totara_engage
-->

<template>
  <SelectTable
    :large-check-box="true"
    :no-label-offset="true"
    :value="selectedItems"
    :data="cards"
    :disabled-ids="disabledItems"
    checkbox-v-align="center"
    :select-all-enabled="true"
    :border-bottom-hidden="true"
    class="tui-engageAdderBrowseTable"
    :get-id="getId"
    @input="$emit('update', $event)"
  >
    <template v-slot:header-row>
      <HeaderCell size="2" valign="center" />
      <HeaderCell size="8" valign="center">
        {{ $str('title', 'totara_engage') }}
      </HeaderCell>
      <HeaderCell size="4" valign="center">
        {{ $str('filteraccess', 'totara_engage') }}
      </HeaderCell>
      <HeaderCell size="6" valign="center">
        {{ $str('contributor', 'totara_engage') }}
      </HeaderCell>
    </template>

    <template v-slot:row="{ row }">
      <!-- Column header passes an empty space for now to make behat pass -->
      <Cell
        class="tui-engageAdderBrowseTable__imgHeader"
        column-header=" "
        size="2"
        valign="center"
      >
        <EngageCardImage
          class="tui-engageAdderBrowseTable__img"
          :card-attribute="row"
        />
      </Cell>

      <Cell
        size="8"
        :column-header="$str('title', 'totara_engage')"
        valign="center"
      >
        <span class="tui-engageAdderBrowseTable__title">
          {{ row.name }}
        </span>
      </Cell>

      <Cell
        size="4"
        :column-header="$str('filteraccess', 'totara_engage')"
        valign="center"
      >
        <AccessIcon :access="row.access" size="300" />
      </Cell>

      <Cell
        size="6"
        :column-header="$str('contributor', 'totara_engage')"
        valign="center"
      >
        {{ row.user.fullname }}
      </Cell>
    </template>
  </SelectTable>
</template>

<script>
import SelectTable from 'tui/components/datatable/SelectTable';
import Cell from 'tui/components/datatable/Cell';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import AccessIcon from 'totara_engage/components/icons/access/computed/AccessIcon';
import EngageCardImage from 'totara_engage/components/card/compute/EngageCardImage';

// Mixins
import AdderMixin from 'totara_engage/mixins/adder_mixin';

export default {
  components: {
    AccessIcon,
    Cell,
    EngageCardImage,
    HeaderCell,
    SelectTable,
  },

  mixins: [AdderMixin],

  props: {
    disabledItems: {
      type: Array,
      default: () => [],
    },
    selectedItems: {
      type: Array,
      default: () => [],
    },
    cards: {
      type: Array,
      default: () => [],
    },
  },

  methods: {
    getId(row) {
      return this.createCardId(row);
    },
  },
};
</script>

<lang-strings>
{
  "totara_engage": [
    "contributor",
    "title",
    "filteraccess"
  ]
}
</lang-strings>

<style lang="scss">
.tui-engageAdderBrowseTable {
  &__img {
    width: 65px;
    height: 45px;
    overflow: hidden;

    @media (min-width: $tui-screen-sm) {
      width: 100%;
    }
  }

  &__title {
    display: block;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }
}
</style>
