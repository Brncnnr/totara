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
  <div class="tui-mod_approval-approvalLevelsManagement">
    <div
      v-if="levels.length === 0"
      class="tui-mod_approval-approvalLevelsManagement__empty"
    >
      {{ $str('approvers_empty', 'mod_approval') }}
    </div>
    <div v-else class="tui-mod_approval-approvalLevelsManagement__container">
      <SubPageHeading :title="$str('approvals', 'mod_approval')">
        <template v-slot:buttons>
          <Button
            :text="$str('button_configure_overrides', 'mod_approval')"
            @click="$send($e.CONFIGURE_OVERRIDES)"
          />
        </template>
      </SubPageHeading>
      <ApprovalLevelsEdit />
    </div>
    <ButtonIcon
      v-if="canAddLevel"
      class="tui-mod_approval-approvalLevelsManagement__addButton"
      :disabled="$matches('persistence.saving')"
      :aria-label="$str('button_add_approval_level', 'mod_approval')"
      :text="$str('button_add_approval_level', 'mod_approval')"
      @click="$send($e.ADD_APPROVAL_LEVEL)"
    >
      <AddIcon size="200" />
    </ButtonIcon>
    <ModalPresenter
      :open="$matches('persistence.addLevel')"
      @request-close="$send($e.CANCEL)"
    >
      <AddApprovalLevelModal />
    </ModalPresenter>
    <ConfirmationModal
      id="confirm-delete"
      :title="$str('delete_approval_level', 'mod_approval')"
      :confirm-button-text="$str('delete', 'mod_approval')"
      :open="$matches('persistence.confirmDeleteLevel')"
      @cancel="$send($e.CANCEL)"
      @confirm="$send($e.DELETE_APPROVAL_LEVEL)"
    >
      <p>
        {{ $str('confirm_delete_approval_level', 'mod_approval', toEditName) }}
      </p>
    </ConfirmationModal>
  </div>
</template>

<script>
import SubPageHeading from 'mod_approval/components/page/SubPageHeading';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Button from 'tui/components/buttons/Button';
import AddIcon from 'tui/components/icons/Add';
import ApprovalLevelsEdit from 'mod_approval/components/workflow/ApprovalLevelsEdit';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import AddApprovalLevelModal from 'mod_approval/components/workflow/AddApprovalLevelModal';
import { MOD_APPROVAL__WORKFLOW_EDIT } from 'mod_approval/constants';

export default {
  components: {
    SubPageHeading,
    ButtonIcon,
    AddIcon,
    Button,
    ConfirmationModal,
    ModalPresenter,
    AddApprovalLevelModal,
    ApprovalLevelsEdit,
  },

  computed: {
    levels() {
      return this.$selectors.getActiveStageApprovalLevels(this.$context);
    },

    toEditName() {
      return this.$selectors.getToEditApprovalLevelName(this.$context);
    },

    canAddLevel() {
      return this.$selectors.getWorkflowIsDraft(this.$context);
    },
  },

  xState: {
    machineId: MOD_APPROVAL__WORKFLOW_EDIT,
  },
};
</script>

<style lang="scss">
.tui-mod_approval-approvalLevelsManagement {
  &__empty {
    margin-top: var(--gap-8);
    @include tui-font-heading-small-regular;
    text-align: center;
  }
}
</style>

<lang-strings>
{
  "core": [
    "add"
  ],
  "mod_approval": [
    "approval_level_name",
    "approvals",
    "approvers_empty",
    "button_add_approval_level",
    "button_configure_overrides",
    "confirm_delete_approval_level",
    "delete",
    "delete_approval_level",
    "reorder_levels"
  ]
}
</lang-strings>
