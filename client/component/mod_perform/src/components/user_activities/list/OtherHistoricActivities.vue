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

  @author Oleg Demeshev <oleg.demeshevn@totaralearning.com>
  @module mod_perform
-->
<template>
  <Table
    :data="otherHistoricActivities"
    class="tui-performOtherHistoricActivityList"
  >
    <template v-slot:header-row>
      <HeaderCell size="4">
        {{
          $str('user_historic_activities_activity_title_header', 'mod_perform')
        }}
      </HeaderCell>
      <HeaderCell size="2">
        {{ $str('user_historic_activities_type_header', 'mod_perform') }}
      </HeaderCell>
      <HeaderCell size="2">
        {{ $str('user_historic_activities_user_header', 'mod_perform') }}
      </HeaderCell>
      <HeaderCell size="2">
        {{
          $str('user_historic_activities_relationship_to_header', 'mod_perform')
        }}
      </HeaderCell>
      <HeaderCell size="2">
        {{ $str('user_historic_activities_status_header', 'mod_perform') }}
      </HeaderCell>
    </template>
    <template v-slot:row="{ row: historicActivity }">
      <Cell
        size="4"
        :column-header="
          $str('user_historic_activities_activity_title_header', 'mod_perform')
        "
        valign="center"
      >
        <a :href="historicActivity.activity_link">
          {{ historicActivity.activity_name }}
        </a>
      </Cell>
      <Cell
        size="2"
        :column-header="
          $str('user_historic_activities_type_header', 'mod_perform')
        "
      >
        {{ historicActivity.type }}
      </Cell>
      <Cell
        size="2"
        :column-header="
          $str('user_historic_activities_user_header', 'mod_perform')
        "
      >
        {{ historicActivity.subject_user }}
      </Cell>
      <Cell
        size="2"
        :column-header="
          $str('user_historic_activities_relationship_to_header', 'mod_perform')
        "
      >
        {{ historicActivity.relationship_to }}
      </Cell>

      <Cell
        size="2"
        :column-header="
          $str('user_historic_activities_status_header', 'mod_perform')
        "
        valign="center"
      >
        {{ historicActivity.status }}
      </Cell>
    </template>
  </Table>
</template>

<script>
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
    otherHistoricActivities: {
      required: true,
      type: Array,
    },
  },
};
</script>
<lang-strings>
{
  "mod_perform": [
    "user_historic_activities_activity_title_header",
    "user_historic_activities_type_header",
    "user_historic_activities_user_header",
    "user_historic_activities_relationship_to_header",
    "user_historic_activities_status_header"
  ]
}
</lang-strings>

<style lang="scss">
.tui-performOtherHistoricActivityList {
  @include tui-font-body();
}
</style>
