<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Jack Humphrey <jack.humphrey@totaralearning.com>
  @module totara_api
-->

<template>
  <div class="tui-totara_api-duration" role="group">
    <Label
      :for-id="$id('duration-value')"
      :label="$str('duration_value_label', 'totara_api', ariaLabel)"
      :hidden="true"
    />
    <InputNumber
      :id="$id('duration-value')"
      v-model="durationValue"
      @input="$emit('input', durationInSeconds)"
      @blur="$emit('blur')"
    />
    <Label
      :for-id="$id('duration-units')"
      :label="$str('duration_units_label', 'totara_api', ariaLabel)"
      :hidden="true"
    />
    <Select
      :id="$id('duration-units')"
      v-model="durationUnits"
      :options="unitOptions"
      @input="$emit('input', durationInSeconds)"
      @blur="$emit('blur')"
    />
  </div>
</template>

<script>
import InputNumber from 'tui/components/form/InputNumber';
import Select from 'tui/components/form/Select';
import Label from 'tui/components/form/Label';

import { Units, unitsInSeconds, parseSeconds } from 'totara_api/duration';

export default {
  components: {
    InputNumber,
    Select,
    Label,
  },
  props: {
    value: Number,
    ariaLabel: {
      type: String,
      default: '',
    },
  },

  data() {
    return {
      durationValue: null,
      durationUnits: '',
      unitOptions: [
        {
          id: Units.WEEKS,
          label: this.$str('duration_weeks', 'totara_api'),
        },
        {
          id: Units.DAYS,
          label: this.$str('duration_days', 'totara_api'),
        },
        {
          id: Units.HOURS,
          label: this.$str('duration_hours', 'totara_api'),
        },
        {
          id: Units.MINUTES,
          label: this.$str('duration_minutes', 'totara_api'),
        },
        {
          id: Units.SECONDS,
          label: this.$str('duration_seconds', 'totara_api'),
        },
      ],
    };
  },

  computed: {
    /**
     * Returns the duration in seconds
     * @return {Number}
     */
    durationInSeconds() {
      if (this.durationValue == null || !this.durationUnits) {
        return null;
      }
      const unit = unitsInSeconds.find(unit => unit.id === this.durationUnits);
      return this.durationValue * unit.seconds;
    },
  },

  watch: {
    value: {
      handler: 'setDurationFromValue',
    },
  },

  mounted() {
    this.setDurationFromValue();
  },

  methods: {
    setDurationFromValue() {
      if (this.value == null) {
        if (this.durationInSeconds != null) {
          this.durationValue = null;
        }
        return;
      }
      if (this.value == this.durationInSeconds) {
        return;
      }
      const duration = parseSeconds(this.value);
      this.durationValue = duration.value;
      this.durationUnits = duration.units;
    },
  },
};
</script>
<lang-strings>
  {
    "totara_api": [
      "duration_seconds",
      "duration_minutes",
      "duration_hours",
      "duration_days",
      "duration_weeks",
      "duration_value_label",
      "duration_units_label"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-totara_api-duration {
  @include tui-stack-vertical(var(--gap-2));
}
</style>
