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
  <Modal
    :size="$matches('chooseTarget') ? 'normal' : 'large'"
    :dismissable="false"
  >
    <ModalContent
      v-if="$matches('chooseTarget')"
      :title="$str('assign_roles', 'mod_approval')"
    >
      <div class="tui-mod_approval-assignRolesModal__contentChooseTarget">
        <Form vertical>
          <FormRow
            v-slot="{ ariaDescribedby }"
            :label="$str('assign_roles_where', 'mod_approval')"
          >
            <RadioGroup
              v-model="assignTarget"
              :aria-describedby="ariaDescribedby"
            >
              <Radio :value="workflowTarget">
                {{ $str('workflow', 'mod_approval') }}
              </Radio>
              <Radio :value="approvalOverrideTarget">
                {{ $str('approval_override', 'mod_approval') }}
              </Radio>
            </RadioGroup>
          </FormRow>
        </Form>
      </div>

      <template v-slot:buttons>
        <Button
          :text="
            assignTarget === workflowTarget
              ? $str('assign', 'mod_approval')
              : $str('next', 'core')
          "
          :styleclass="{ primary: true }"
          @click="nextStep"
        />
        <Button
          :text="$str('cancel', 'core')"
          @click="$send({ type: $e.CANCEL })"
        />
      </template>
    </ModalContent>
    <ModalContent
      v-if="$matches('chooseApprovalOverride')"
      :title="$str('assign_roles', 'mod_approval')"
    >
      <div class="tui-mod_approval-assignRolesModal__contentPicker">
        <WorkflowDefaultAssignmentPicker
          v-model="assignment"
          class="tui-mod_approval-assignRolesModal__picker"
          :context-id="$selectors.getContextId($context)"
        />
      </div>

      <template v-slot:footer-content>
        <div class="tui-mod_approval-assignRolesModal__footer">
          <Button :text="$str('back', 'core')" @click="$send($e.BACK)" />
          <ButtonGroup>
            <Button
              :text="$str('assign', 'mod_approval')"
              :styleclass="{ primary: true }"
              :disabled="!canSubmit"
              @click="handleOrgSubmit"
            />
            <Button
              :text="$str('cancel', 'core')"
              @click="$send({ type: $e.CANCEL })"
            />
          </ButtonGroup>
        </div>
      </template>
    </ModalContent>
  </Modal>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import FormRow from 'tui/components/form/FormRow';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import {
  MOD_APPROVAL__WORKFLOW_ASSIGN_ROLES,
  RoleAssignTarget,
} from 'mod_approval/constants';
import WorkflowDefaultAssignmentPicker from 'mod_approval/components/workflow/WorkflowDefaultAssignmentPicker';

export default {
  components: {
    Button,
    ButtonGroup,
    FormRow,
    Radio,
    RadioGroup,
    Modal,
    ModalContent,
    WorkflowDefaultAssignmentPicker,
  },

  props: {
    machineId: String,
  },

  data() {
    return {
      assignTarget: RoleAssignTarget.WORKFLOW,
      assignment: null,
      workflowTarget: RoleAssignTarget.WORKFLOW,
      approvalOverrideTarget: RoleAssignTarget.APPROVAL_OVERRIDE,
    };
  },

  xState: {
    machineId: MOD_APPROVAL__WORKFLOW_ASSIGN_ROLES,
  },

  computed: {
    canSubmit() {
      return (
        this.assignment != null &&
        this.assignment.type != null &&
        this.assignment.id != null
      );
    },
  },

  methods: {
    nextStep() {
      this.$send({
        type: this.$e.NEXT,
        target: this.assignTarget,
      });
    },

    handleOrgSubmit() {
      this.$send({
        type: this.$e.NEXT,
        target: this.assignTarget,
        assignment: this.assignment,
      });
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "next",
    "back",
    "cancel"
  ],
  "mod_approval": [
    "approval_override",
    "assign",
    "assign_roles",
    "assign_roles_where",
    "workflow"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-assignRolesModal {
  &__contentChooseTarget {
    padding-top: var(--gap-2);
  }

  &__contentPicker {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    height: 50rem;
    padding-top: var(--gap-4);
  }

  &__picker {
    flex-grow: 1;
    min-height: 0;
  }

  &__footer {
    display: flex;
    flex-grow: 1;
    justify-content: space-between;
  }
}
</style>
