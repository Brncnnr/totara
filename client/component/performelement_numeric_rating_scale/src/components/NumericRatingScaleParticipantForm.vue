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

  @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
  @package performelement_numeric_rating_scale
-->

<template>
  <!-- Handle the different view switching (read only / print / form),
  populate form content if editable and display others responses -->
  <ElementParticipantFormContent
    v-bind="$attrs"
    :element="element"
    :is-draft="isDraft"
  >
    <template v-slot:content="{ labelId }">
      <FormScope :path="path" :process="process">
        <div class="tui-elementEditNumericRatingScaleParticipantForm">
          <FormRange
            name="response"
            :default-value="element.data.defaultValue"
            :show-labels="false"
            :min="min"
            :max="max"
            :validations="validations"
            aria-hiden="true"
          />
          <FormNumber
            name="response"
            :min="min"
            :max="max"
            char-length="5"
            :aria-labelledby="labelId"
          />
        </div>
      </FormScope>
      <ElementDescription
        v-if="element.data.descriptionEnabled"
        :aria-region-label="
          $str('scale_description', 'performelement_numeric_rating_scale')
        "
        :content-html="element.data.descriptionHtml"
      />
    </template>
  </ElementParticipantFormContent>
</template>

<script>
import ElementDescription from 'mod_perform/components/element/participant_form/ElementDescription';
import ElementParticipantFormContent from 'mod_perform/components/element/ElementParticipantFormContent';
import FormScope from 'tui/components/reform/FormScope';
import { FormRange, FormNumber } from 'tui/components/uniform';
import { v as validation } from 'tui/validation';

export default {
  components: {
    ElementDescription,
    ElementParticipantFormContent,
    FormScope,
    FormRange,
    FormNumber,
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
     * The minimum value that can be selected.
     *
     * @return {number}
     */
    min() {
      return parseInt(this.element.data.lowValue, 10);
    },
    /**
     * The maximum value that can be selected.
     *
     * @return {number}
     */
    max() {
      return parseInt(this.element.data.highValue, 10);
    },
  },

  methods: {
    /**
     * An array of validation rules for the element.
     * The rules returned depend on if we are saving as draft or if a response is required or not.
     *
     * @return {(function|object)[]}
     */
    validations() {
      const rules = [validation.min(this.min), validation.max(this.max)];

      if (this.isDraft) {
        return rules;
      }

      if (this.element && this.element.is_required) {
        return [validation.required(), ...rules];
      }

      return rules;
    },
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

<style lang="scss">
.tui-elementEditNumericRatingScaleParticipantForm {
  display: flex;
  align-items: flex-end;
  justify-content: flex-start;

  & > * + * {
    margin-left: var(--gap-4);
  }
}
</style>

<lang-strings>
{
  "performelement_numeric_rating_scale": [
    "scale_description"
  ]
}
</lang-strings>
