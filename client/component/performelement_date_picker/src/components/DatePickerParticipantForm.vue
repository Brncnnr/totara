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

  @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
  @module performelement_date_picker
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
      <FormScope :path="path" :process="process" :validate="validate">
        <FieldGroup :aria-labelledby="labelId">
          <FormDateSelector
            name="response"
            :year-range-start="element.data.yearRangeStart"
            :year-range-end="element.data.yearRangeEnd"
          />
        </FieldGroup>
      </FormScope>
    </template>
  </ElementParticipantFormContent>
</template>

<script>
import ElementParticipantFormContent from 'mod_perform/components/element/ElementParticipantFormContent';
import { FormDateSelector, FormScope } from 'tui/components/uniform';
import FieldGroup from 'tui/components/form/FieldGroup';
import { getValuesFromIso, isExists } from 'tui/date';

export default {
  components: {
    ElementParticipantFormContent,
    FormScope,
    FormDateSelector,
    FieldGroup,
  },

  props: {
    element: Object,
    isDraft: Boolean,
    path: {
      type: [String, Array],
      default: '',
    },
  },

  methods: {
    /**
     * An array of validation rules for the element.
     * The rules returned depend on if we are saving as draft or if a response is required or not.
     *
     * @param {Object|null|undefined} values
     * @return {object}
     */
    validate(values) {
      // Date element has been interacted with
      if (
        values &&
        Object.prototype.hasOwnProperty.call(values, 'response') &&
        values.response !== null
      ) {
        let dateCheck = this.validDate(values.response);

        if (typeof values.response === 'undefined' || !dateCheck) {
          return {
            response: this.$str(
              'error_invalid_date',
              'performelement_date_picker'
            ),
          };
        }
      }

      if (
        !this.isDraft &&
        this.element.is_required &&
        (!values || !values.response)
      ) {
        return { response: this.$str('required', 'core') };
      }
    },

    /**
     * Validates the date iso value.
     *
     * @param {Object} value
     * @return {Boolean}
     */
    validDate(value) {
      if (!value) {
        return false;
      }
      const date = getValuesFromIso(value.iso);
      return isExists(date.year, date.month, date.day);
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

<lang-strings>
  {
    "core": [
      "required"
    ],
    "performelement_date_picker": [
        "error_invalid_date"
    ]
  }
</lang-strings>
