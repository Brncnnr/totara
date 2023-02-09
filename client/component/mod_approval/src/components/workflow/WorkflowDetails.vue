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
  <div class="tui-mod_approval-workflowDetails">
    <FormViewSection v-if="$matches('navigation.form')" />
    <ApprovalLevelsManagement
      v-if="$matches('navigation.approvals.approvers')"
    />
    <ApprovalsOverrides
      v-else-if="$matches('navigation.approvals.overrides')"
    />
    <InteractionsSection v-else-if="$matches('navigation.interactions')" />
    <NotificationEdit v-else-if="$matches('navigation.notifications')" />
  </div>
</template>

<script>
import ApprovalLevelsManagement from 'mod_approval/components/workflow/ApprovalLevelsManagement';
import ApprovalsOverrides from 'mod_approval/components/workflow/ApprovalsOverrides';
import InteractionsSection from 'mod_approval/components/workflow/interactions/InteractionsSection';
import NotificationEdit from 'mod_approval/components/workflow/NotificationEdit';
import FormViewSection from 'mod_approval/components/workflow/form_views/FormViewSection';
import { MOD_APPROVAL__WORKFLOW_EDIT } from 'mod_approval/constants';

export default {
  name: 'WorkflowDetails',
  components: {
    ApprovalLevelsManagement,
    ApprovalsOverrides,
    InteractionsSection,
    NotificationEdit,
    FormViewSection,
  },

  xState: {
    machineId: MOD_APPROVAL__WORKFLOW_EDIT,
  },
};
</script>
