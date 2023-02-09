<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totara.com] for more information.

  @author Simon Chester <simon.chester@totara.com>
  @module totara_useraction
-->

<template>
  <InputSet char-length="full">
    <InputNumber
      v-model="durationValue"
      :aria-label="$str('duration_value', 'totara_useraction')"
      char-length="5"
    />
    <Select
      v-model="durationUnit"
      :aria-label="$str('duration_unit', 'totara_useraction')"
      :options="durationUnitOptions"
      char-length="10"
    />
  </InputSet>
</template>

<script>
import InputNumber from 'tui/components/form/InputNumber';
import InputSet from 'tui/components/form/InputSet';
import Select from 'tui/components/form/Select';

export default {
  components: {
    InputNumber,
    InputSet,
    Select,
  },

  props: {
    value: Object,
  },

  data() {
    return {
      durationValue: this.value && this.value.value,
      durationUnit: this.value && this.value.unit,
      durationUnitOptions: [
        { id: 'DAY', label: this.$str('unit_days', 'totara_useraction') },
        { id: 'MONTH', label: this.$str('unit_months', 'totara_useraction') },
        { id: 'YEAR', label: this.$str('unit_years', 'totara_useraction') },
      ],
    };
  },

  computed: {
    selectedValue() {
      const value = this.durationValue;
      const unit = this.durationUnit;
      return value && unit ? { value, unit } : null;
    },
  },

  watch: {
    value() {
      if (!this.isValueEqual(this.value, this.selectedValue)) {
        if (this.value) {
          this.durationUnit = this.value.unit;
          this.durationValue = this.value.value;
        } else {
          this.durationValue = '';
        }
      }
    },

    selectedValue() {
      if (!this.isValueEqual(this.value, this.selectedValue)) {
        this.$emit('input', this.selectedValue);
      }
    },
  },

  methods: {
    isValueEqual(a, b) {
      return (
        a == b &&
        !(a == null || b == null) &&
        a.value == b.value &&
        a.unit == b.unit
      );
    },
  },
};
</script>

<lang-strings>
{
  "totara_useraction": [
    "duration_unit",
    "duration_value",
    "unit_days",
    "unit_months",
    "unit_years"
  ]
}
</lang-strings>
