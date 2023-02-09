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

  @author Simon Tegg <simon.tegg@totaralearning.com>
  @module mod_approval
-->
<template>
  <div>
    <div>
      <Button
        v-if="inherited"
        :styleclass="{ transparent: true }"
        :text="$str('inherited', 'mod_approval')"
        @click="
          $send({
            type: $e.VIEW_APPROVERS,
            approvers: inheritedApproverUsers,
            inheritedFrom: inherited.assignment.name,
            approvalModalTitle,
            approvalModalLevelName: approvalLevelName,
          })
        "
      />
      <Button
        v-else
        :styleclass="{ transparent: true }"
        :text="usersText"
        @click="
          $send({
            type: $e.VIEW_APPROVERS,
            approvers: approverUsers,
            approvalModalTitle,
            approvalModalLevelName: approvalLevelName,
          })
        "
      />
    </div>
  </div>
</template>
<script>
import { MOD_APPROVAL__WORKFLOW_EDIT } from 'mod_approval/constants';
import Button from 'tui/components/buttons/Button';

export default {
  name: 'OverrideCell',

  components: {
    Button,
  },

  props: {
    assignmentApprovalLevel: {
      type: Object,
      required: true,
    },

    organisationName: {
      type: String,
      required: true,
    },

    approvalLevelName: {
      type: String,
      required: true,
    },
  },

  xState: {
    machineId: MOD_APPROVAL__WORKFLOW_EDIT,
  },

  computed: {
    approvers() {
      return this.assignmentApprovalLevel.approvers;
    },

    approverUsers() {
      return this.approvers.map(approver => approver.approver_entity);
    },

    usersText() {
      return this.approvers.length === 1
        ? this.$str('one_user', 'mod_approval')
        : this.$str('x_users', 'mod_approval', this.approvers.length);
    },

    inherited() {
      return this.assignmentApprovalLevel
        .inherited_from_assignment_approval_level;
    },

    approvalModalTitle() {
      return this.$str(
        'approvers_modal_title',
        'mod_approval',
        this.organisationName
      );
    },

    inheritedApproverUsers() {
      return this.inherited.approvers.map(approver => approver.approver_entity);
    },
  },
};
</script>

<lang-strings>
{
  "mod_approval": [
    "approvers_modal_title",
    "inherited",
    "inherited_from",
    "level_approvers",
    "x_users",
    "one_user"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-overrideCell {
  &__approvers {
    display: flex;
  }
}
</style>
