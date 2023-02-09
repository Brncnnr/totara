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

  @author Kunle Odusan <kunle.odusan@totaralearning.com>
  @package totara_perform
-->

<template>
  <Modal :aria-labelledby="$id('title')">
    <ModalContent
      :title="$str('add_activity', 'mod_perform')"
      :title-id="$id('title')"
      :close-button="false"
      @dismiss="$emit('cancel')"
    >
      <Uniform ref="form" :initial-values="initialValues" @submit="trySave">
        <FormRow :label="$str('create_activity_title', 'mod_perform')" required>
          <FormText
            name="name"
            :maxlength="ACTIVITY_NAME_MAX_LENGTH"
            char-length="full"
            :validations="v => [v.required()]"
          />
        </FormRow>

        <FormRow
          :label="
            $str('general_info_label_activity_description', 'mod_perform')
          "
        >
          <FormTextarea
            name="description"
            char-length="full"
            :aria-describedby="$id('desc-desc')"
          />
          <FormRowDetails :id="$id('desc-desc')">
            {{ $str('you_can_add_this_later', 'mod_perform') }}
          </FormRowDetails>
        </FormRow>

        <FormRow
          :label="$str('create_activity_type', 'mod_perform')"
          :required="true"
        >
          <FormSelect
            name="type"
            :aria-describedby="$id('select-desc')"
            :options="activityTypes"
            char-length="10"
            :validations="v => [v.required()]"
          />
          <FormRowDetails :id="$id('select-desc')">
            {{ $str('activity_type_help_text', 'mod_perform') }}
          </FormRowDetails>
        </FormRow>
      </Uniform>

      <template v-slot:buttons>
        <Button
          :loading="isSaving"
          :styleclass="{ primary: true }"
          :text="$str('button_create', 'mod_perform')"
          @click="$refs.form.submit()"
        />

        <CancelButton :disabled="isSaving" @click="$emit('request-close')" />
      </template>
    </ModalContent>
  </Modal>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import CancelButton from 'tui/components/buttons/Cancel';
import {
  Uniform,
  FormRow,
  FormText,
  FormSelect,
  FormTextarea,
} from 'tui/components/uniform';
import FormRowDetails from 'tui/components/form/FormRowDetails';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import { ACTIVITY_NAME_MAX_LENGTH } from 'mod_perform/constants';
import createActivityMutation from 'mod_perform/graphql/create_activity';

export default {
  components: {
    Button,
    CancelButton,
    Uniform,
    FormRow,
    FormRowDetails,
    FormText,
    FormSelect,
    FormTextarea,
    Modal,
    ModalContent,
  },

  props: {
    types: Array,
  },

  data() {
    return {
      initialValues: {
        type: null,
      },
      isSaving: false,
      mutationError: null,
    };
  },

  computed: {
    activityTypes() {
      return [
        {
          id: null,
          label: this.$str('create_activity_select_placeholder', 'mod_perform'),
        },
      ].concat(this.types);
    },
  },

  created() {
    this.ACTIVITY_NAME_MAX_LENGTH = ACTIVITY_NAME_MAX_LENGTH;
  },

  methods: {
    /**
     * Try to persist the activity to the back end.
     * Emitting events on success/failure.
     *
     * @returns {Promise<void>}
     */
    async trySave(values) {
      this.isSaving = true;

      try {
        const savedActivity = await this.save(values);
        this.$emit('mutation-success', savedActivity);
      } catch (e) {
        this.$emit('mutation-error', e);

        // If something goes wrong during create, allow the user to try again.
        this.isSaving = false;
      }
    },

    /**
     * @returns {Promise<{id, name, description}>}
     */
    async save(values) {
      const { data: resultData } = await this.$apollo.mutate({
        mutation: createActivityMutation,
        variables: values,
      });

      return resultData.mod_perform_create_activity.activity;
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "activity_type_help_text",
      "add_activity",
      "button_create",
      "create_activity_select_placeholder",
      "create_activity_title",
      "create_activity_type",
      "general_info_label_activity_description",
      "you_can_add_this_later"
    ]
  }
</lang-strings>
