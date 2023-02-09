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

  @author Jack Humphrey <jack.humphrey@totaralearning.com>
  @module mod_approval
-->
<template>
  <Modal size="small">
    <ModalContent :title="$str('rename_stage', 'mod_approval')">
      <Uniform
        ref="form"
        vertical
        :initial-values="initialValues"
        validation-mode="submit"
        @submit="renameWorkflowStage"
      >
        <FormRow
          ref="nameRow"
          v-slot="{ id }"
          :required="true"
          :label="$str('stage_name', 'mod_approval')"
          class="tui-mod_approval-renameStageModal__formRow"
        >
          <FormText
            :id="id"
            v-model="workflowStageName"
            name="name"
            :validations="v => [v.required(), v.maxLength(255)]"
          />
        </FormRow>
      </Uniform>
      <template v-slot:buttons>
        <ButtonGroup>
          <Button
            :disabled="cantSubmit"
            :styleclass="{ primary: true }"
            :text="$str('rename', 'core')"
            @click="$refs.form.submit()"
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
import { FormRow, FormText, Uniform } from 'tui/components/uniform';
import Button from 'tui/components/buttons/Button';
import CancelButton from 'tui/components/buttons/Cancel';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';

export default {
  components: {
    Modal,
    ModalContent,
    FormRow,
    FormText,
    Uniform,
    Button,
    CancelButton,
    ButtonGroup,
  },
  props: {
    workflowStage: { type: Object, required: true },
  },

  xState: {
    machineId: MOD_APPROVAL__WORKFLOW_EDIT,
  },

  data() {
    return {
      initialValues: {
        name: this.workflowStage.name,
      },
      workflowStageName: this.workflowStage.name,
    };
  },

  computed: {
    cantSubmit() {
      return (
        this.workflowStageName === this.workflowStage.name ||
        this.workflowStageName === ''
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
    renameWorkflowStage(values) {
      this.$send({
        type: this.$e.RENAME_WORKFLOW_STAGE,
        name: values.name,
        workflowStageId: this.workflowStage.id,
      });
    },
  },
};
</script>

<lang-strings>
{
  "mod_approval": [
    "rename_stage",
    "stage_name"
  ],
  "core": [
    "rename"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-renameStageModal {
  &__formRow {
    margin-top: var(--gap-4);
  }
}
</style>
