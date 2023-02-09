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
  @module container_workspace
-->
<template>
  <Modal
    :dismissable="{
      overlayClose: false,
      esc: true,
      backdropClick: false,
    }"
    :aria-labelledby="$id('title')"
    class="tui-workspaceRequestModal"
  >
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
        <FormRow>
          {{ bodyText }}
        </FormRow>

        <FormRow v-slot="{ id }" :vertical="true">
          <FormTextarea
            name="messageContent"
            :aria-describedby="$id('desc-desc')"
            :maxlength="250"
            :rows="setRows('messageContent', 5, 25)"
          />
        </FormRow>
      </Uniform>
      <template v-slot:buttons>
        <ButtonGroup class="tui-workspaceRequestModal__buttonGroup">
          <Button
            :styleclass="{ primary: true }"
            :text="buttonText"
            :aria-label="buttonText"
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
import { Uniform, FormTextarea } from 'tui/components/uniform';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';

export default {
  components: {
    ButtonGroup,
    Button,
    Cancel,
    FormRow,
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
    bodyText: {
      type: String,
      required: false,
    },
    buttonText: {
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
        messageContent: '',
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
