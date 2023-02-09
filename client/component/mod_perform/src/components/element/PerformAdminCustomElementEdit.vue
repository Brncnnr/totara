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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module mod_perform
-->
<template>
  <Uniform
    ref="form"
    :initial-values="initialValues"
    :vertical="true"
    :validation-mode="validationMode"
    input-width="full"
    @change="onChange"
    @submit="handleSubmit"
  >
    <FormRowStack spacing="large">
      <FormRow
        v-if="settings.has_title"
        :label="settings.title_text"
        :required="settings.is_title_required"
        :helpmsg="settings.title_help_text"
      >
        <FormText
          name="rawTitle"
          :validations="
            v =>
              settings.is_title_required
                ? [v.required(), v.maxLength(1024)]
                : [v.maxLength(1024)]
          "
        />
      </FormRow>

      <slot />

      <FormRow
        v-if="settings.has_reporting_id"
        :label="$str('reporting_identifier', 'mod_perform')"
        :helpmsg="$str('reporting_id_help_text', 'mod_perform')"
      >
        <FormText
          name="identifier"
          :validations="v => [v.maxLength(255)]"
          char-length="20"
        />
      </FormRow>

      <FormRow v-if="settings.is_respondable">
        <FormCheckbox
          name="responseRequired"
          :disabled="!settings.is_response_required_enabled"
        >
          {{ $str('section_element_response_required', 'mod_perform') }}
        </FormCheckbox>
      </FormRow>

      <FormRow>
        <ButtonGroup>
          <Button
            class="tui-performAdminCustomElementEdit__submit"
            :styleclass="{ primary: true, small: true }"
            :text="$str('button_save', 'mod_perform')"
            type="submit"
          />
          <Button
            :text="$str('button_cancel', 'mod_perform')"
            :styleclass="{ small: true }"
            @click="$emit('cancel')"
          />
        </ButtonGroup>
      </FormRow>
    </FormRowStack>
  </Uniform>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import FormRowStack from 'tui/components/form/FormRowStack';
import {
  FormCheckbox,
  FormRow,
  FormText,
  Uniform,
} from 'tui/components/uniform';

export default {
  components: {
    Button,
    ButtonGroup,
    FormCheckbox,
    FormRow,
    FormRowStack,
    FormText,
    Uniform,
  },

  props: {
    initialValues: Object,
    settings: Object,
    validationMode: {
      type: String,
      default: 'submit',
    },
  },

  mounted() {
    this.$el.querySelector('input').focus();
  },

  methods: {
    handleSubmit(values) {
      let data = Object.assign({}, values);
      delete data.identifier;
      delete data.responseRequired;
      delete data.rawTitle;
      this.$emit('update', {
        data: data,
        identifier: values.identifier,
        is_required: values.responseRequired,
        title: values.rawTitle,
      });
    },

    onChange(values) {
      this.$emit('change', values);
    },

    cancel() {
      this.$emit('cancel');
    },

    /**
     * Set value of field at path.
     *
     * @public
     * @param {(string|number|array)} path
     * @param value
     */
    update(path, value) {
      this.$refs.form.update(path, value);
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "button_cancel",
      "button_save",
      "question_title",
      "reporting_id_help_text",
      "reporting_identifier",
      "section_element_response_required"
    ]
  }
</lang-strings>
