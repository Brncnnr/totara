<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

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
  <Modal>
    <ModalContent :title="$str('button_add_approval_level', 'mod_approval')">
      <FormRow
        ref="nameRow"
        v-slot="{ id }"
        :required="true"
        :label="$str('approval_level_name', 'mod_approval')"
      >
        <InputText
          :id="id"
          v-model="newApprovalLevelName"
          name="name"
          @submit="createNewApprovalLevel"
        />
      </FormRow>
      <template v-slot:buttons>
        <ButtonGroup>
          <Button
            :disabled="newApprovalLevelName === ''"
            :styleclass="{ primary: true }"
            :text="$str('add', 'core')"
            @click="createNewApprovalLevel"
          />
          <CancelButton
            @click="
              $send($e.CANCEL);
              newApprovalLevelName = '';
            "
          />
        </ButtonGroup>
      </template>
    </ModalContent>
  </Modal>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import CancelButton from 'tui/components/buttons/Cancel';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import FormRow from 'tui/components/form/FormRow';
import InputText from 'tui/components/form/InputText';
import { MOD_APPROVAL__WORKFLOW_EDIT } from 'mod_approval/constants';

export default {
  name: 'AddApprovalLevelModal',
  components: {
    Button,
    ButtonGroup,
    CancelButton,
    Modal,
    ModalContent,
    FormRow,
    InputText,
  },

  /*
   * newApprovalLevelName maintained outside of $context for v-model convenience.
   * A refactor may shift it into $context
   */

  data() {
    return {
      newApprovalLevelName: '',
    };
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
    createNewApprovalLevel() {
      this.$send({
        type: this.$e.ADD_APPROVAL_LEVEL,
        name: this.newApprovalLevelName,
        workflowStageId: this.$selectors.getActiveStageId(this.$context),
      });

      this.newApprovalLevelName = '';
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
    "button_add_approval_level",
    "approval_level_name"
  ],
  "core": [
      "add"
  ]
}
</lang-strings>
