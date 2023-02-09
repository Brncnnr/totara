<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

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
  <Modal size="small">
    <ModalContent :title="$str('rename_approval_level', 'mod_approval')">
      <FormRow
        ref="nameRow"
        v-slot="{ id }"
        :required="true"
        :label="$str('approval_level_name', 'mod_approval')"
        class="tui-mod_approval-renameApprovalLevelModal__formRow"
      >
        <InputText
          :id="id"
          v-model="approvalLevelName"
          name="name"
          @submit="renameWorkflowStage"
        />
      </FormRow>
      <template v-slot:buttons>
        <ButtonGroup>
          <Button
            :disabled="cantSubmit"
            :styleclass="{ primary: true }"
            :text="$str('rename', 'core')"
            @click="renameWorkflowStage"
          />
          <CancelButton @click="$send($e.CANCEL)" />
        </ButtonGroup>
      </template>
    </ModalContent>
  </Modal>
</template>

<script>
import { MOD_APPROVAL__WORKFLOW_EDIT } from 'mod_approval/constants';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import FormRow from 'tui/components/form/FormRow';
import InputText from 'tui/components/form/InputText';
import Button from 'tui/components/buttons/Button';
import CancelButton from 'tui/components/buttons/Cancel';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';

export default {
  components: {
    Modal,
    ModalContent,
    FormRow,
    InputText,
    Button,
    CancelButton,
    ButtonGroup,
  },
  props: {
    approvalLevel: { type: Object, required: true },
  },

  xState: {
    machineId: MOD_APPROVAL__WORKFLOW_EDIT,
  },

  data() {
    return {
      approvalLevelName: this.approvalLevel.name,
    };
  },

  computed: {
    cantSubmit() {
      return (
        this.approvalLevelName === this.approvalLevel.name ||
        this.approvalLevelName === ''
      );
    },
  },

  mounted() {
    this.$nextTick(() => {
      const nameRow = this.$refs.nameRow;
      if (nameRow && nameRow.$el) {
        const name = nameRow.$el.querySelector('input[name=name]');
        if (name) {
          name.focus();
        }
      }
    });
  },

  methods: {
    renameWorkflowStage() {
      this.$send({
        type: this.$e.RENAME_APPROVAL_LEVEL,
        name: this.approvalLevelName,
        approvalLevelId: this.approvalLevel.id,
      });
    },
  },
};
</script>

<lang-strings>
{
  "mod_approval": [
    "rename_approval_level",
    "approval_level_name"
  ],
  "core": [
    "rename"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-renameApprovalLevelModal {
  &__formRow {
    margin-top: var(--gap-4);
  }
}
</style>
