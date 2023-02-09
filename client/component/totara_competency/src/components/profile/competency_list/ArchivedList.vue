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
  This component has been deprecated, please use competency_list/ListBody.vue (with archived=true)
-->

<template>
  <Table :data="groupedCompetencyData" :group-mode="true" :archived="true">
    <template v-slot:header-row>
      <HeaderCell size="3">
        {{ $str('header_competency', 'totara_competency') }}
      </HeaderCell>
      <HeaderCell size="2">
        {{ $str('header_assignment_reasons', 'totara_competency') }}
      </HeaderCell>
      <HeaderCell size="2">
        {{ $str('header_archived_date', 'totara_competency') }}
      </HeaderCell>
      <HeaderCell size="2" align="center">
        {{ $str('proficient', 'totara_competency') }}
      </HeaderCell>
      <HeaderCell size="2">
        {{ $str('achievement_level', 'totara_competency') }}
      </HeaderCell>
    </template>
    <template v-slot:row="{ row, firstInGroup }">
      <Cell
        size="3"
        :column-header="$str('header_competency', 'totara_competency')"
        :repeated-header="!firstInGroup"
      >
        <a :href="competencyDetailsLink(row)">{{ row.competency.fullname }}</a>
      </Cell>

      <Cell
        size="2"
        :column-header="$str('header_assignment_reasons', 'totara_competency')"
      >
        {{ row.assignment && row.assignment.progress_name }}
      </Cell>

      <Cell
        size="2"
        :column-header="$str('header_archived_date', 'totara_competency')"
      >
        {{
          row.assignment &&
            (row.assignment.archived_at || row.assignment.unassigned_at)
        }}
      </Cell>

      <Cell
        size="2"
        align="center"
        :column-header="$str('proficient', 'totara_competency')"
      >
        <CheckIcon
          v-if="row.proficient"
          size="200"
          :alt="$str('yes', 'core')"
        />
        <span v-else>
          <span :aria-hidden="true">-</span>
          <span class="sr-only">{{ $str('no', 'core') }}</span>
        </span>
      </Cell>

      <Cell
        size="2"
        :column-header="$str('achievement_level', 'totara_competency')"
      >
        <MyRatingCell
          v-if="row.my_value"
          :value="row.my_value"
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
import MyRatingCell from 'totara_competency/components/profile/MyRatingCell';

export default {
  components: {
    Table,
    HeaderCell,
    Cell,
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

  computed: {
    groupedCompetencyData() {
      return this.competencies.map(x => ({
        id: x.competency.id,
        rows: x.items.map(item =>
          Object.assign({ competency: x.competency }, item)
        ),
      }));
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
    "header_competency",
    "header_assignment_reasons",
    "header_archived_date",
    "proficient",
    "achievement_level"
  ]
}
</lang-strings>
