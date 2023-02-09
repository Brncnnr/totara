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
  <ModalContent :title="$str('add_assignment', 'mod_approval')">
    <div class="tui-mod_approval-chooseAssignmentStep__content">
      <WorkflowDefaultAssignmentPicker
        class="tui-mod_approval-chooseAssignmentStep__picker"
        :value="{
          id: $context.selectedIdentifier,
          type: $context.selectedAssignmentType,
        }"
        @input="handlePickerInput"
      />
    </div>
    <template v-slot:footer-content>
      <div class="tui-mod_approval-chooseAssignmentStep__buttons">
        <Button
          :text="$str('back', 'core')"
          @click="$send({ type: $e.BACK })"
        />
        <ButtonGroup>
          <Button
            :styleclass="{ primary: true }"
            :disabled="!$context.selectedIdentifier"
            :text="$str('button_create', 'mod_approval')"
            @click="$send({ type: $e.CREATE })"
          />
          <CancelButton @click="$send({ type: $e.CANCEL })" />
        </ButtonGroup>
      </div>
    </template>
  </ModalContent>
</template>

<script>
import { MOD_APPROVAL__WORKFLOW_CREATE } from 'mod_approval/constants';
import ModalContent from 'tui/components/modal/ModalContent';
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import CancelButton from 'tui/components/buttons/Cancel';
import WorkflowDefaultAssignmentPicker from 'mod_approval/components/workflow/WorkflowDefaultAssignmentPicker';

export default {
  components: {
    ModalContent,
    ButtonGroup,
    Button,
    CancelButton,
    WorkflowDefaultAssignmentPicker,
  },

  xState: {
    machineId: MOD_APPROVAL__WORKFLOW_CREATE,
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
  "core": [
    "back"
  ],
  "mod_approval": [
    "button_create",
    "add_assignment"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-chooseAssignmentStep {
  &__content {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    height: 50rem;
    padding-top: var(--gap-6);
  }

  &__picker {
    flex-grow: 1;
    min-height: 30rem;
  }

  &__buttons {
    display: flex;
    flex: 1;
    justify-content: space-between;
    max-height: 10rem;
  }
}
</style>
