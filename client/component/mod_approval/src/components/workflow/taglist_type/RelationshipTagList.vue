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

  @author Kunle Odusan <kunle.odusan@totaralearning.com>
  @author Simon Tegg <simon.tegg@totaralearning.com>
  @module mod_approval
-->

<template>
  <!-- TODO: TL-32043 currently hardcoded to a single non-iteractable "Manager" -->
  <TagList
    :disabled="disabled"
    :items="availableOptions"
    :tags="selectedApprovers"
    :label-name="
      $str(
        'relationships_for_approver_level_taglist',
        'mod_approval',
        approverLevel.name
      )
    "
  >
    <template v-slot:tag="{ tag: { approver_entity } }">
      <Tag :text="approver_entity.name" />
    </template>
    <template v-slot:item="{ item }">
      <div>
        <p>{{ item.name }}</p>
      </div>
    </template>
  </TagList>
</template>

<script>
import TagList from 'tui/components/tag/TagList';
import Tag from 'tui/components/tag/Tag';
import { MOD_APPROVAL__WORKFLOW_EDIT } from 'mod_approval/constants';

export default {
  components: {
    TagList,
    Tag,
  },

  props: {
    disabled: Boolean,
    approvalLevelId: {
      type: String,
      required: true,
    },
  },

  computed: {
    availableOptions() {
      return this.$selectors
        .getRoleTypeOptions(this.$context)
        .filter(
          option =>
            !this.selectedApprovers.some(
              approver => approver.approver_entity.id == option.identifier
            )
        );
    },

    approverLevel() {
      return this.$selectors
        .getActiveStageApprovalLevels(this.$context)
        .find(approvalLevel => approvalLevel.id === this.approvalLevelId);
    },

    selectedApprovers() {
      // TODO: TL-31105 pretend manager is selected
      return this.$selectors
        .getRoleTypeOptions(this.$context)
        .filter(option => option.idnumber === 'manager')
        .map(option => ({
          id: `fake-${option.identifier}`,
          name: option.name,
          approver_entity: {
            __typename: 'totara_core_relationship',
            id: option.identifier,
            name: option.name,
          },
        }));
      // TODO: because the real solution doesn't work until we 'setApprovalLevelApprovers' in 'updateSelectedApproverType'
      // return this.approverLevel.approvers.filter(
      //   approver => approver.type === ApproverType.RELATIONSHIP
      // );
    },
  },
  xState: {
    machineId: MOD_APPROVAL__WORKFLOW_EDIT,
  },
};
</script>

<lang-strings>
{
  "mod_approval": [
    "relationships_for_approver_level_taglist"
  ]
}
</lang-strings>
