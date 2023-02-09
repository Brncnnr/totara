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

  @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
  @module totara_competency
  @deprecated since Totara 14.0
  This component has been deprecated, please use competency_list/ListBody.vue instead
-->

<template>
  <Table :data="competencies">
    <template v-slot:header-row>
      <HeaderCell size="10">
        {{ $str('header_competency', 'totara_competency') }}
      </HeaderCell>
      <HeaderCell size="2" align="center">
        {{ $str('proficient', 'totara_competency') }}
      </HeaderCell>
      <HeaderCell size="3">
        {{ $str('achievement_level', 'totara_competency') }}
      </HeaderCell>
    </template>
    <template v-slot:row="{ row }">
      <Cell
        size="10"
        :column-header="$str('header_competency', 'totara_competency')"
      >
        <a :href="competencyDetailsLink(row)">{{ row.competency.fullname }}</a>
      </Cell>

      <Cell
        size="2"
        align="center"
        :column-header="$str('proficient', 'totara_competency')"
      >
        <CheckIcon
          v-if="row.items[0].my_value && row.items[0].my_value.proficient"
          size="200"
          :alt="$str('yes', 'core')"
        />
        <span v-else>
          <span :aria-hidden="true">-</span>
          <span class="sr-only">{{ $str('no', 'core') }}</span>
        </span>
      </Cell>

      <Cell
        size="3"
        :column-header="$str('achievement_level', 'totara_competency')"
      >
        <MyRatingCell
          v-if="row.items[0].my_value"
          :value="row.items[0].my_value"
          :scales="scales"
        />
      </Cell>
    </template>
  </Table>
</template>

<script>
import Table from 'tui/components/datatable/Table';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import Cell from 'tui/components/datatable/Cell';
import CheckIcon from 'tui/components/icons/CheckSuccess';
import MyRatingCell from 'totara_competency/components/profile/MyRatingCell';

export default {
  components: {
    Table,
    HeaderCell,
    Cell,
    CheckIcon,
    MyRatingCell,
  },

  props: {
    competencies: {
      required: true,
      type: Array,
    },
    baseUrl: {
      required: true,
      type: String,
    },
    userId: {
      required: true,
      type: Number,
    },
    scales: {
      required: true,
      type: Array,
    },
  },

  methods: {
    competencyDetailsLink(row) {
      const params = { competency_id: row.competency.id };
      if (!this.isMine) {
        params.user_id = this.userId;
      }
      return this.$url(`${this.baseUrl}/details/index.php`, params);
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "no",
    "yes"
  ],
  "totara_competency": [
    "achievement_level",
    "header_competency",
    "proficient"
  ]
}
</lang-strings>
