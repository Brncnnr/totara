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

  @author Jaron Steenson <jaron.steenson@totaralearning.com>
  @module totara_competency
-->

<template>
  <div class="tui-competencySelfAssignmentTable">
    <h3 class="tui-competencySelfAssignmentTable__header">
      {{ $str('competencies', 'totara_competency', totalCompetencyCount) }}
    </h3>

    <SelectTable
      :value="value"
      :data="competencies"
      :expandable-rows="true"
      :select-all-enabled="true"
      checkbox-v-align="center"
      @input="$emit('input', $event)"
    >
      <template v-slot:header-row>
        <ExpandCell :header="true" />
        <HeaderCell size="4">
          {{ $str('header_competency', 'totara_competency') }}
        </HeaderCell>
        <HeaderCell size="4">
          {{ $str('header_assignment_status', 'totara_competency') }}
        </HeaderCell>
        <HeaderCell size="4">
          {{ $str('header_assignment_reasons', 'totara_competency') }}
        </HeaderCell>
      </template>

      <template v-slot:row="{ row, expand, expandState }">
        <ExpandCell
          :aria-label="row.display_name"
          :empty="!row.description"
          :expand-state="expandState"
          @click="expand()"
        />
        <Cell
          size="4"
          :column-header="$str('header_competency', 'totara_competency')"
          valign="center"
        >
          {{ row.display_name }}
        </Cell>
        <Cell
          size="4"
          :column-header="$str('header_assignment_status', 'totara_competency')"
          valign="center"
        >
          {{ getAssignmentStatus(row) }}
        </Cell>
        <Cell
          size="4"
          :column-header="
            row.user_assignments.length > 0
              ? $str('header_assignment_reasons', 'totara_competency')
              : ''
          "
          valign="center"
        >
          <ul class="tui-competencySelfAssignmentTable__reasons">
            <li
              v-for="assignments in row.user_assignments"
              :key="assignments.id"
            >
              {{ assignments.reason_assigned }}
            </li>
          </ul>
        </Cell>
      </template>

      <template v-slot:expand-content="{ row }">
        <div class="tui-competencySelfAssignmentTable__expand">
          <h4 class="tui-competencySelfAssignmentTable__expand-header">
            {{ row.display_name }}
          </h4>
          <h5 class="tui-competencySelfAssignmentTable__expand-subHeader">
            {{ $str('description', 'totara_competency') }}
          </h5>
          <div v-html="row.description" />
        </div>
      </template>
    </SelectTable>
  </div>
</template>

<script>
import Cell from 'tui/components/datatable/Cell';
import ExpandCell from 'tui/components/datatable/ExpandCell';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import SelectTable from 'tui/components/datatable/SelectTable';

export default {
  components: {
    Cell,
    ExpandCell,
    HeaderCell,
    SelectTable,
  },
  props: {
    value: {
      type: Array,
      required: true,
    },
    competencies: {
      type: Array,
      required: true,
    },
    totalCompetencyCount: {
      type: Number,
      required: true,
    },
  },
  data() {
    return {
      page: 0,
    };
  },
  methods: {
    getAssignmentStatus(competency) {
      if (this.isAssigned(competency)) {
        return this.$str('currently_assigned', 'totara_competency');
      }

      return this.$str('not_assigned', 'totara_competency');
    },
    isAssigned(competency) {
      return (
        Boolean(competency.user_assignments) &&
        competency.user_assignments.length > 0
      );
    },
  },
};
</script>

<lang-strings>
  {
    "totara_competency": [
      "all",
      "competencies",
      "currently_assigned",
      "description",
      "competencies",
      "header_competency",
      "header_assignment_status",
      "header_assignment_reasons",
      "not_assigned"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-competencySelfAssignmentTable {
  & > * + * {
    margin-top: var(--gap-2);
  }

  &__header {
    margin: 0;
    @include tui-font-heading-small();
  }

  &__reasons {
    margin: 0;
    padding-left: 0;
    list-style: none;
  }

  &__expand {
    & > * + * {
      margin: var(--gap-2) 0 0;
    }

    &-header {
      @include tui-font-heading-small();
    }

    &-subHeader {
      @include tui-font-heading-x-small();
    }
  }
}
</style>
