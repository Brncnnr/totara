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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module engage_survey
-->

<template>
  <FormRow
    v-slot="{ labelId }"
    class="tui-engageSurveyRadioBox"
    :label="label"
    hidden
  >
    <RadioGroup v-model="option" :aria-labelledby="labelId">
      <Radio
        v-for="item in options"
        :key="item.id"
        :name="'engagesurvey-radiobox'"
        :value="item.id"
        :label="item.value"
        class="tui-engageSurveyRadioBox__radio"
      >
        {{ item.value }}
      </Radio>
    </RadioGroup>
  </FormRow>
</template>

<script>
import RadioGroup from 'tui/components/form/RadioGroup';
import Radio from 'tui/components/form/Radio';
import { FormRow } from 'tui/components/uniform';

const has = Object.prototype.hasOwnProperty;

export default {
  components: {
    RadioGroup,
    Radio,
    FormRow,
  },

  model: {
    prop: 'value',
    event: 'update-value',
  },

  props: {
    value: {
      // We are using this property for v-model.
      required: false,
      type: [Number, String],
    },

    options: {
      required: true,
      type: [Array, Object],
      validator(prop) {
        for (let i in prop) {
          if (!has.call(prop, i)) {
            continue;
          }

          let option = prop[i];
          if (!has.call(option, 'id') || !has.call(option, 'value')) {
            return false;
          }
        }

        return true;
      },
    },

    label: String,
  },

  data() {
    return {
      option: null,
    };
  },

  watch: {
    option(value) {
      this.$emit('update-value', value);
    },
  },
};
</script>

<style lang="scss">
.tui-engageSurveyRadioBox {
  .tui-radioGroup {
    padding: 0;
    overflow: auto;
  }

  .tui-radioGroup > * + * {
    margin-top: 0;
  }

  &__radio {
    margin-bottom: var(--gap-4);

    .tui-radio__label {
      font-size: var(--font-size-15);
      @include tui-wordbreak--hyphens;
    }
  }
}
</style>
