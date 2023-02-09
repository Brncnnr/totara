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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module totara_competency
-->

<template>
  <Table
    :data="assignments"
    class="tui-competencyDetailArchivedAssignmentsTable"
  >
    <template v-slot:header-row>
      <HeaderCell size="6">
        {{ $str('assignment', 'totara_competency') }}
      </HeaderCell>
      <HeaderCell size="3">
        {{ $str('assignment_date_archived', 'totara_competency') }}
      </HeaderCell>
      <HeaderCell size="3">
        {{ $str('proficiency_status', 'totara_competency') }}
      </HeaderCell>
    </template>
    <template v-slot:row="{ row, expand }">
      <!-- Assignment name column -->
      <Cell size="6" :column-header="$str('assignment', 'totara_competency')">
        {{ row.name }}
      </Cell>

      <!-- Archived Date column -->
      <Cell
        size="3"
        :column-header="$str('assignment_date_archived', 'totara_competency')"
      >
        {{ row.archivedAt }}
      </Cell>

      <!-- Proficiency status column -->
      <Cell
        size="3"
        :column-header="$str('proficiency_status', 'totara_competency')"
      >
        <div class="tui-competencyDetailArchivedAssignmentsTable__achievement">
          <ProficientStatus :proficient-status="row.proficient" />
          <InfoIconButton
            v-if="row.legacy"
            :is-help-for="$str('proficiency_status', 'totara_competency')"
          >
            <h4
              class="tui-competencyDetailArchivedAssignmentsTable__discontinued"
            >
              {{
                $str(
                  'legacy_assignment_rating_discontinued',
                  'totara_competency'
                )
              }}
            </h4>
            {{
              $str('legacy_assignment_rating_description', 'totara_competency')
            }}
          </InfoIconButton>
        </div>
      </Cell>
    </template>
  </Table>
</template>

<script>
import Cell from 'tui/components/datatable/Cell';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import InfoIconButton from 'tui/components/buttons/InfoIconButton';
import ProficientStatus from 'totara_competency/components/details/ProficientStatus';
import Table from 'tui/components/datatable/Table';

export default {
  components: {
    Cell,
    HeaderCell,
    InfoIconButton,
    ProficientStatus,
    Table,
  },
  props: {
    assignments: {
      type: Array,
    },
  },
};
</script>

<lang-strings>
  {
    "totara_competency": [
      "assignment",
      "assignment_date_archived",
      "legacy_assignment_rating_description",
      "legacy_assignment_rating_discontinued",
      "proficiency_status"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-competencyDetailArchivedAssignmentsTable {
  &__achievement {
    display: flex;
  }
}
</style>
