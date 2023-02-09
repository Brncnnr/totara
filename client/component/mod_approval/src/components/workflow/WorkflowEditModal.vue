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

  @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
  @module mod_approval
-->

<template>
  <Modal class="tui-mod_approval-workflowEditModal">
    <ModalContent :title="$str('workflow_edit_details', 'mod_approval')">
      <Uniform
        ref="form"
        :initial-values="initialValues"
        :errors="errors"
        input-width="full"
        @validation-changed="validationChanged"
        @change="change"
      >
        <FormRow required :label="$str('workflow_name', 'mod_approval')">
          <FormText
            name="name"
            :validations="v => [v.required()]"
            :maxlength="1024"
            :autofocus="true"
          />
        </FormRow>
        <FormRow :label="$str('workflow_description', 'mod_approval')">
          <FormTextarea name="description" :rows="4" />
        </FormRow>
        <FormRow :required="true" :label="$str('workflow_id', 'mod_approval')">
          <FormText
            name="id_number"
            :maxlength="100"
            :disabled="updating"
            :validations="v => [v.required()]"
            @input="onInputId"
          />
        </FormRow>
      </Uniform>
      <template v-slot:buttons>
        <Button
          :disabled="!canUpdate"
          :styleclass="{ primary: true }"
          :text="$str('update')"
          :loading="busy"
          @click="confirm"
        />
        <CancelButton :disabled="busy" @click="$emit('cancel')" />
      </template>
    </ModalContent>
  </Modal>
</template>

<script>
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import {
  Uniform,
  FormRow,
  FormText,
  FormTextarea,
} from 'tui/components/uniform';
import CancelButton from 'tui/components/buttons/Cancel';
import Button from 'tui/components/buttons/Button';
import isIdNumberUniqueQuery from 'mod_approval/graphql/workflow_id_number_is_unique';
import { debounce } from 'tui/util';
import pending from 'tui/pending';
import { langString } from 'tui/i18n';

// All known paths.
const paths = ['name', 'description', 'id_number'];

export default {
  components: {
    Modal,
    ModalContent,
    Uniform,
    FormRow,
    FormText,
    FormTextarea,
    Button,
    CancelButton,
  },

  props: {
    workflowId: {
      type: [String, Number],
      required: true,
    },
    initialValues: {
      type: Object,
      required: true,
      validator: val => Object.keys(val).every(key => paths.includes(key)),
    },
    updating: {
      type: Boolean,
    },
  },

  data: () => ({
    validating: null,
    idError: '',
    isValidResult: true,
    values: null,
  }),

  computed: {
    busy() {
      return Boolean(this.updating || this.validating);
    },

    canUpdate() {
      if (this.values === null || this.idError || !this.isValidResult) {
        return false;
      }
      return paths.some(
        name => this.trimmedValues[name] !== this.initialValues[name]
      );
    },

    trimmedValues() {
      if (this.values === null) {
        return null;
      }
      const trimmedValues = {};
      paths.forEach(path => (trimmedValues[path] = this.values[path].trim()));
      return trimmedValues;
    },

    errors() {
      if (this.idError) {
        return { id_number: langString(this.idError, 'mod_approval') };
      } else {
        return {};
      }
    },
  },

  mounted() {
    // touch all fields to be able to perform validation on the fly
    paths.forEach(path => this.$refs.form.touch(path));
    this.$nextTick(() => this.$refs.form.focus());
  },

  methods: {
    confirm() {
      this.$emit('confirm', this.trimmedValues);
    },

    onInputId(newValue) {
      newValue = newValue.trim();
      this.lastIdNumber = newValue;
      if (this.shouldValidateId()) {
        this.validateId(newValue);
      } else {
        this.updateError('');
      }
    },

    shouldValidateId() {
      return (
        this.lastIdNumber !== '' &&
        this.lastIdNumber !== this.initialValues.id_number
      );
    },

    validateId(newValue) {
      if (!this.validating) {
        this.validating = pending();
      }
      if (!this.debouncedValidateId) {
        this.debouncedValidateId = debounce(async val => {
          let result;
          try {
            result = await this.checkUniqueness(val);
          } catch (e) {
            // see if a user changes the field while awaiting GQL.
            if (this.shouldValidateId()) {
              this.updateError('error:workflow_id_error');
            }
            throw e;
          }
          // see if a user changes the field while awaiting GQL.
          if (this.shouldValidateId()) {
            this.updateError(result ? '' : 'error:workflow_id_not_unique');
          }
        }, 500);
      }
      this.debouncedValidateId(newValue);
    },

    updateError(error) {
      this.idError = error;
      if (this.validating) {
        this.validating();
      }
      this.validating = null;
    },

    async checkUniqueness(val) {
      const response = await this.$apollo.query({
        query: isIdNumberUniqueQuery,
        variables: {
          input: { workflow_id: this.workflowId, id_number: val },
        },
        fetchPolicy: 'network-only',
      });
      return response.data.mod_approval_workflow_id_number_is_unique;
    },

    change(values) {
      this.values = values;
    },

    validationChanged(validationResults) {
      this.isValidResult = validationResults.isValid;
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "update"
  ],
  "mod_approval": [
    "workflow_edit_details",
    "workflow_description",
    "workflow_id",
    "workflow_name"
  ]
}
</lang-strings>
