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
  <Modal :dismissable="false">
    <ModalContent :title="$str('add_stage', 'mod_approval')">
      <div class="tui-mod_approval-addStageModal__content">
        <Uniform
          ref="form"
          vertical
          :initial-values="values"
          validation-mode="submit"
          @change="handleChange"
          @submit="handleFormSubmit"
        >
          <FormRow
            ref="nameRow"
            :label="$str('stage_name', 'mod_approval')"
            required
          >
            <FormText
              name="name"
              char-length="full"
              :validations="v => [v.required(), v.maxLength(255)]"
            />
          </FormRow>

          <FormRow
            :label="$str('add_stage_type_label', 'mod_approval')"
            required
          >
            <FormRadioGroup name="type">
              <Radio
                v-for="{ value, label } in stageTypes"
                :key="value"
                :value="value"
              >
                {{ label }}
              </Radio>
            </FormRadioGroup>
          </FormRow>

          <input v-show="false" type="submit" :disabled="!canSubmit" />
        </Uniform>
      </div>

      <template v-slot:buttons>
        <Button
          :text="$str('add', 'core')"
          :styleclass="{ primary: true }"
          :disabled="!canSubmit"
          @click="$refs.form.submit()"
        />
        <Button :text="$str('cancel', 'core')" @click="$send($e.CANCEL)" />
      </template>
    </ModalContent>
  </Modal>
</template>

<script>
import { MOD_APPROVAL__WORKFLOW_EDIT } from 'mod_approval/constants';
import Button from 'tui/components/buttons/Button';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import {
  FormRow,
  FormRadioGroup,
  FormText,
  Uniform,
} from 'tui/components/uniform';
import Radio from 'tui/components/form/Radio';
import { StageType } from 'mod_approval/constants';

export default {
  components: {
    Button,
    Modal,
    ModalContent,
    FormRow,
    FormRadioGroup,
    FormText,
    Uniform,
    Radio,
  },

  data() {
    return {
      values: {},
      assignment: null,
      stageTypes: [
        {
          value: StageType.FORM_SUBMISSION,
          label: this.$str('stage_type_form_submission', 'mod_approval'),
        },
        {
          value: StageType.APPROVALS,
          label: this.$str('stage_type_approvals', 'mod_approval'),
        },
        {
          value: StageType.WAITING,
          label: this.$str('stage_type_waiting', 'mod_approval'),
        },
        {
          value: StageType.FINISHED,
          label: this.$str('stage_type_finished', 'mod_approval'),
        },
      ],
    };
  },
  xState: {
    machineId: MOD_APPROVAL__WORKFLOW_EDIT,
  },

  computed: {
    canSubmit() {
      return Boolean(this.values.name && this.values.type);
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
    handleChange(values) {
      this.values = values;
    },

    handleFormSubmit() {
      if (!this.canSubmit) {
        return;
      }
      this.$send({ type: this.$e.ADD_WORKFLOW_STAGE, values: this.values });
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "add",
    "cancel"
  ],
  "mod_approval": [
    "add_stage",
    "add_stage_type_label",
    "stage_name",
    "stage_type_approvals",
    "stage_type_finished",
    "stage_type_form_submission",
    "stage_type_waiting"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-addStageModal {
  &__content {
    flex-grow: 1;
    min-height: 22rem;
    padding-top: var(--gap-4);
  }
}
</style>
