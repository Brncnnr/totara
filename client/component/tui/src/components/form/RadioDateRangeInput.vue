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
  @module totara_core
-->

<template>
  <div class="tui-radioDateRangeInput" role="group">
    <div class="tui-radioDateRangeInput__number">
      <InputNumber
        :id="$id('rangeValue')"
        v-model="rangeValue"
        :aria-label="$str('number', 'totara_core')"
        :disabled="disabled"
        :name="name + '[value]'"
        @input="update"
      />
    </div>
    <div class="tui-radioDateRangeInput__type">
      <Select
        :id="$id('rangeType')"
        v-model="rangeType"
        :aria-label="$str('date_range_type_input', 'totara_core')"
        :disabled="disabled"
        :name="name + '[range]'"
        :options="rangeTypeOptions"
        @input="update"
      />
    </div>
  </div>
</template>

<script>
// Components
import InputNumber from 'tui/components/form/InputNumber';
import Select from 'tui/components/form/Select';

export default {
  components: {
    InputNumber,
    Select,
  },

  props: {
    disabled: Boolean,
    name: String,
    value: Object,
  },

  data() {
    return {
      rangeType: 'DAY',
      rangeTypeOptions: [
        {
          id: 'DAY',
          label: this.$str('date_range_days', 'totara_core'),
        },
        {
          id: 'WEEK',
          label: this.$str('date_range_weeks', 'totara_core'),
        },
      ],
      rangeValue: 1,
    };
  },

  computed: {
    selectedLabel() {
      let selectedOption = this.rangeTypeOptions.filter(
        x => x.id === this.rangeType
      );
      return selectedOption[0].label;
    },

    selectedValue() {
      return {
        value: this.rangeValue,
        range: this.rangeType,
      };
    },
  },

  watch: {
    value: {
      handler() {
        if (this.value !== undefined) {
          this.rangeType = this.value.range;
          this.rangeValue = this.value.value;
        } else {
          this.rangeType = 'DAY';
          this.rangeValue = 1;
        }
        this.update();
      },
    },
  },

  mounted() {
    // If no value emit the default
    if (this.value === undefined) {
      this.rangeType = 'DAY';
      this.rangeValue = 1;
      this.update();
    } else {
      this.rangeType = this.value.range;
      this.rangeValue = this.value.value;
    }
  },

  methods: {
    /**
     * Update the selected date
     *
     */
    update() {
      this.$emit('accessible-change', {
        value: this.rangeValue,
        range: this.selectedLabel,
      });
      this.$emit('input', this.selectedValue);
    },
  },
};
</script>

<lang-strings>
  {
    "totara_core": [
      "date_range_days",
      "number",
      "date_range_type_input",
      "date_range_weeks"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-radioDateRangeInput {
  display: flex;

  & > * + * {
    margin-left: var(--gap-2);
  }

  &__number {
    display: flex;
    width: 6rem;
  }

  &__type {
    display: flex;
    width: 12rem;
  }
}
</style>
