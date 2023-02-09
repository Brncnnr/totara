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
  @module performelement_date_picker
-->

<template>
  <div class="tui-datePickerAdminEdit">
    <PerformAdminCustomElementEdit
      :initial-values="initialValues"
      :settings="settings"
      @cancel="$emit('display')"
      @change="updateValues"
      @update="$emit('update', processData($event))"
    >
      <FormRow
        :label="
          $str('label_year_range_begins_at', 'performelement_date_picker')
        "
        required
      >
        <FormField
          v-slot="{ attrs, value, update, blur }"
          char-length="full"
          name="yearRangeStart"
          :validations="
            v => [
              v.required(),
              v.integer(),
              v.min(yearRangeMin),
              v.maxForRangeStart(
                yearRangeStart,
                yearRangeEnd,
                yearRangeMin,
                yearRangeMax,
                $str('error_start_after_end', 'performelement_date_picker')
              ),
            ]
          "
        >
          <InputText
            v-bind="attrs"
            :placeholder="
              $str('year_placeholder', 'performelement_date_picker')
            "
            :char-length="4"
            :maxlength="4"
            :value="value"
            @input="update"
            @blur="blur"
          />
        </FormField>
      </FormRow>
      <FormRow
        :label="$str('label_year_range_ends_at', 'performelement_date_picker')"
        required
      >
        <FormField
          v-slot="{ attrs, value, update, blur }"
          char-length="full"
          name="yearRangeEnd"
          :validations="
            v => [
              v.required(),
              v.integer(),
              v.minForRangeEnd(
                yearRangeStart,
                yearRangeEnd,
                yearRangeMin,
                yearRangeMax,
                $str('error_end_before_start', 'performelement_date_picker')
              ),
              v.max(yearRangeMax),
            ]
          "
        >
          <InputText
            v-bind="attrs"
            :placeholder="
              $str('year_placeholder', 'performelement_date_picker')
            "
            :char-length="4"
            :maxlength="4"
            :value="value"
            @input="update"
            @blur="blur"
          />
        </FormField>
      </FormRow>
    </PerformAdminCustomElementEdit>
  </div>
</template>

<script>
import { DEFAULT_YEAR_RANGE_OFFSET } from 'tui/date';
import InputText from 'tui/components/form/InputText';
import { FormField, FormRow } from 'tui/components/uniform';
import PerformAdminCustomElementEdit from 'mod_perform/components/element/PerformAdminCustomElementEdit';

export default {
  components: {
    FormField,
    FormRow,
    InputText,
    PerformAdminCustomElementEdit,
  },

  inheritAttrs: false,

  props: {
    identifier: String,
    isRequired: Boolean,
    rawTitle: String,
    settings: Object,
    data: Object,
  },

  data() {
    const yearRangeStart = Number.isInteger(this.data.yearRangeStart)
      ? this.data.yearRangeStart
      : null;
    const yearRangeEnd = Number.isInteger(this.data.yearRangeEnd)
      ? this.data.yearRangeEnd
      : null;

    return {
      initialValues: {
        rawTitle: this.rawTitle,
        identifier: this.identifier,
        responseRequired: this.isRequired,
        yearRangeStart: yearRangeStart || this.getLowerOffsetYear(),
        yearRangeEnd: yearRangeEnd || this.getUpperOffsetYear(),
      },
      yearRangeMin: 1900,
      yearRangeMax: this.getUpperOffsetYear(),
      yearRangeStart,
      yearRangeEnd,
    };
  },
  methods: {
    /**
     * @param {number|string|null} yearRangeStart
     * @param {number|string|null} yearRangeEnd
     * Track changes to year range outside uniform so we can adjust the min/max validation accordingly.
     */
    updateValues({ yearRangeStart, yearRangeEnd }) {
      let parsedYearRangeStart = parseInt(yearRangeStart, 10);
      this.yearRangeStart = isNaN(parsedYearRangeStart)
        ? null
        : parsedYearRangeStart;

      let parsedYearRangeEnd = parseInt(yearRangeEnd, 10);
      this.yearRangeEnd = isNaN(parsedYearRangeEnd) ? null : parsedYearRangeEnd;
    },

    /**
     * Provide the lower offset based on current year
     *
     * @return {Number}
     */
    getLowerOffsetYear() {
      return new Date().getFullYear() - DEFAULT_YEAR_RANGE_OFFSET;
    },

    /**
     * Provide the upper offset based on current year
     *
     * @return {Number}
     */
    getUpperOffsetYear() {
      return new Date().getFullYear() + DEFAULT_YEAR_RANGE_OFFSET;
    },

    /**
     * Cast the years before/after config to Numbers ready to be used by the DateSelector component.
     *
     * @return {object}
     * @param {object} values
     */
    processData(values) {
      // We need to explicitly handle, Number 0 because if the form is untouched and literal 0 has been fed in
      // if will be passed back out untouched. If a "0" is typed in, it will come out as string zero.
      values.data.yearRangeStart = values.data.yearRangeStart
        ? Number(values.data.yearRangeStart)
        : null;

      values.data.yearRangeEnd = values.data.yearRangeEnd
        ? Number(values.data.yearRangeEnd)
        : null;

      return values;
    },
  },
};
</script>

<lang-strings>
{
  "performelement_date_picker": [
    "error_end_before_start",
    "error_start_after_end",
    "label_year_range_begins_at",
    "label_year_range_ends_at",
    "year_placeholder"
  ]
}
</lang-strings>
