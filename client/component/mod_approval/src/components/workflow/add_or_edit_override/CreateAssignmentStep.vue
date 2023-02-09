<!-- This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

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
  <ModalContent :title="$str('button_add_override', 'mod_approval')">
    <div class="tui-mod_approval-createAssignmentStep__content">
      <WorkflowDefaultAssignmentPicker
        class="tui-mod_approval-createAssignmentStep__picker"
        :value="{
          id: $context.selectedIdentifier,
          type: $context.selectedAssignmentType,
        }"
        :disabled-ids="$selectors.getActiveDisabledIds($context)"
        :force-loading="$matches('createAssignment.fetchingDisabledIds')"
        @input="handlePickerInput"
      />
      <Separator />
    </div>
    <template v-slot:footer-content>
      <div class="tui-mod_approval-createAssignmentStep__buttons">
        <ButtonGroup>
          <Button
            :styleclass="{ primary: true }"
            :loading="$matches('createAssignment.creatingAssignment')"
            :disabled="!$context.selectedIdentifier"
            :text="$str('button_add', 'mod_approval')"
            @click="$send({ type: $e.NEXT })"
          />
          <CancelButton @click="$send({ type: $e.CANCEL })" />
        </ButtonGroup>
      </div>
    </template>
  </ModalContent>
</template>

<script>
import { MOD_APPROVAL__EDIT_OVERRIDES } from 'mod_approval/constants';
import ModalContent from 'tui/components/modal/ModalContent';
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import CancelButton from 'tui/components/buttons/Cancel';
import Separator from 'tui/components/decor/Separator';
import WorkflowDefaultAssignmentPicker from 'mod_approval/components/workflow/WorkflowDefaultAssignmentPicker';

export default {
  components: {
    ModalContent,
    ButtonGroup,
    Button,
    CancelButton,
    Separator,
    WorkflowDefaultAssignmentPicker,
  },

  xState: {
    machineId: MOD_APPROVAL__EDIT_OVERRIDES,
  },

  computed: {
    disabledIds() {
      return (
        this.$context.disabledIds[this.context.selectedAssignmentType] || []
      );
    },
  },

  methods: {
    handlePickerInput(assignment) {
      this.$send({
        type: this.$e.SELECT_ASSIGNMENT_TARGET,
        identifier: assignment.id,
        assignmentType: assignment.type,
      });
    },
  },
};
</script>

<lang-strings>
{
  "mod_approval": [
    "button_add",
    "button_add_override"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-createAssignmentStep {
  &__content {
    margin-top: var(--gap-6);
  }

  &__picker {
    flex-grow: 1;
    height: 50rem;
  }

  &__buttons {
    display: flex;
    flex: 1;
    justify-content: flex-end;
    max-height: 10rem;

    &-hasBack {
      justify-content: space-between;
    }
  }
}
</style>
