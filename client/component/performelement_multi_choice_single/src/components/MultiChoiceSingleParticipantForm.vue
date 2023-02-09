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

  @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
  @module performelement_multi_choice_single
-->
<template>
  <!-- Handle the different view switching (read only / print / form),
  populate form content if editable and display others responses -->
  <ElementParticipantFormContent
    v-bind="$attrs"
    :element="element"
    :is-draft="isDraft"
    :from-print="false"
  >
    <template v-slot:content>
      <FormScope :path="path" :process="process">
        <FormRadioGroup
          class="tui-multiChoiceSingleParticipantForm"
          :validations="validations"
          name="response"
        >
          <Radio
            v-for="item in element.data.options"
            :key="item.name"
            :value="item.name"
          >
            {{ item.value }}
          </Radio>
        </FormRadioGroup>
      </FormScope>
    </template>
  </ElementParticipantFormContent>
</template>

<script>
import ElementParticipantFormContent from 'mod_perform/components/element/ElementParticipantFormContent';
import FormRadioGroup from 'tui/components/uniform/FormRadioGroup';
import FormScope from 'tui/components/reform/FormScope';
import Radio from 'tui/components/form/Radio';
import { v as validation } from 'tui/validation';

export default {
  components: {
    ElementParticipantFormContent,
    FormRadioGroup,
    FormScope,
    Radio,
  },

  props: {
    element: Object,
    isDraft: Boolean,
    path: {
      type: [String, Array],
      default: '',
    },
  },

  computed: {
    /**
     * An array of validation rules for the element.
     * The rules returned depend on if we are saving as draft or if a response is required or not.
     *
     * @return {(function|object)[]}
     */
    validations() {
      if (!this.isDraft && this.element && this.element.is_required) {
        return [validation.required()];
      }

      return [];
    },
  },

  methods: {
    /**
     * Process the form values.
     *
     * @param value
     * @return {null|string}
     */
    process(value) {
      if (!value || !value.response) {
        return null;
      }

      return value.response;
    },
  },
};
</script>
