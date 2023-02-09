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

  @author Qingyang Liu <qingyang.liu@totaralearning.com>
  @module totara_oauth2
-->
<template>
  <Modal :aria-labelledby="$id('title')" class="tui-oauth2ProviderForm">
    <ModalContent
      :title="title"
      :title-id="$id('title')"
      :close-button="showCloseButton"
    >
      <Uniform
        ref="form"
        :initial-values="initialValues"
        input-width="full"
        @change="formValues = $event"
        @submit="$emit('submit', $event)"
      >
        <FormRow aria-hidden="true" class="tui-oauth2ProviderForm__required">
          <span class="tui-oauth2ProviderForm__requiredStar">
            *
          </span>
          {{ $str('required_fields', 'totara_oauth2') }}
        </FormRow>

        <FormRow
          v-slot="{ id }"
          :label="$str('name', 'core')"
          required
          :vertical="true"
        >
          <FormText
            :id="id"
            name="name"
            :validations="v => [v.required()]"
            :maxlength="75"
          />
        </FormRow>

        <FormRow
          v-slot="{ id }"
          :label="$str('description', 'totara_oauth2')"
          :vertical="true"
        >
          <FormTextarea
            name="description"
            :maxlength="1024"
            :aria-describedby="$id('desc-desc')"
            :rows="setRows('description', 8, 25)"
          />
        </FormRow>

        <FormRow :label="$str('scopes', 'totara_oauth2')" :vertical="true">
          <Checkbox
            name="xapi_write"
            disabled
            checked
            class="tui-oauth2ProviderForm__checkBox"
          >
            {{ $str('xapi_write', 'totara_oauth2') }}
          </Checkbox>
        </FormRow>
        <input v-show="false" type="submit" :disabled="isSaving" />
      </Uniform>
      <template v-slot:buttons>
        <ButtonGroup class="tui-oauth2ProviderForm__buttonGroup">
          <Button
            :styleclass="{ primary: true }"
            :text="$str('add_provider', 'totara_oauth2')"
            :aria-label="$str('add_provider', 'totara_oauth2')"
            type="submit"
            :disabled="isSaving"
            @click="$refs.form.submit()"
          />
          <Cancel :disabled="isSaving" @click="$emit('request-close')" />
        </ButtonGroup>
      </template>
    </ModalContent>
  </Modal>
</template>

<script>
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import Button from 'tui/components/buttons/Button';
import FormRow from 'tui/components/form/FormRow';
import Cancel from 'tui/components/buttons/Cancel';
import Checkbox from 'tui/components/form/Checkbox';
import { FormText, Uniform, FormTextarea } from 'tui/components/uniform';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';

export default {
  components: {
    ButtonGroup,
    Button,
    Checkbox,
    Cancel,
    FormRow,
    FormText,
    Modal,
    ModalContent,
    Uniform,
    FormTextarea,
  },
  props: {
    title: {
      type: String,
      required: true,
    },
    showCloseButton: {
      type: Boolean,
      default: true,
    },
    isSaving: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    return {
      initialValues: {
        name: '',
        xapi_write: 'XAPI_WRITE',
        description: '',
      },
      formValues: null,
    };
  },

  methods: {
    /**
     *
     * @param {String} field
     * @param {Int} defaultRow
     * @param {Int} maxRow
     *
     **/
    setRows(field, defaultRow, maxRow) {
      let text = '';
      if (this.formValues && field in this.formValues) {
        text = this.formValues[field];
      } else if (this.initialValues && field in this.initialValues) {
        text = this.initialValues[field];
      }
      let row = (text.match(/\n/g) || []).length + 1;
      if (row < defaultRow) {
        return defaultRow;
      }
      if (row > maxRow) {
        return maxRow;
      }
      return row;
    },
  },
};
</script>
<lang-strings>
  {
    "totara_oauth2": [
      "add_provider",
      "description",
      "required_fields",
      "scopes",
      "xapi_write"
    ],
    "core": [
      "name"
    ]
  }
</lang-strings>
<style lang="scss">
.tui-oauth2ProviderForm {
  &__buttonGroup {
    display: flex;
    justify-content: flex-end;
  }
  &__requiredStar {
    color: var(--color-prompt-alert);
  }
}
</style>
