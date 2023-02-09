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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module mod_approval
-->

<template>
  <div class="tui-mod_approval-printApprovalsCell">
    <table class="tui-mod_approval-printApprovalsCell__table">
      <thead>
        <tr>
          <th class="tui-mod_approval-printApprovalsCell__step">
            {{ $str('approval_step', 'mod_approval') }}
          </th>
          <th class="tui-mod_approval-printApprovalsCell__name">
            {{ $str('approver_name', 'mod_approval') }}
          </th>
          <th class="tui-mod_approval-printApprovalsCell__date">
            {{ $str('approval_date', 'mod_approval') }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(approver, i) in approvers" :key="i">
          <td class="tui-mod_approval-printApprovalsCell__step" scope="row">
            {{ approver.level }}
          </td>
          <td class="tui-mod_approval-printApprovalsCell__name">
            {{ approver.fullname }}
          </td>
          <td class="tui-mod_approval-printApprovalsCell__date">
            {{ approver.date }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
export default {
  props: {
    approvers: {
      required: true,
      type: Array,
    },
  },
};
</script>

<lang-strings>
{
  "mod_approval": [
    "approval_date",
    "approval_step",
    "approver_name"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-printApprovalsCell {
  &__table {
    width: calc(100% + var(--border-width-thin) * 2);
    margin: calc(var(--border-width-thin) * -1);

    th {
      font-weight: normal;
      font-size: 0.8em;
    }

    td,
    th {
      padding: var(--gap-1);
      border: var(--border-width-thin) solid black;
    }
  }

  &__step {
    width: 38%;
  }

  &__name {
    width: 38%;
  }

  &__date {
    width: 24%;
  }
}
</style>
