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

  @author Alvin Smith <alvin.smith@totaralearning.com>
  @module totara_engage
-->

<template>
  <RadioGroup
    v-model="time"
    :name="name"
    :required="required"
    class="tui-timeViewSelector"
    :aria-labelledby="ariaLabelledby"
  >
    <Radio
      v-for="(option, index) in options"
      :key="index"
      :name="name"
      :value="option.value"
    >
      {{ option.label }}
    </Radio>
  </RadioGroup>
</template>

<script>
import RadioGroup from 'tui/components/form/RadioGroup';
import Radio from 'tui/components/form/Radio';

// GraphQL queries
import getTimeViewOptions from 'totara_engage/graphql/time_view_options';

export default {
  components: {
    RadioGroup,
    Radio,
  },

  props: {
    selectedTime: {
      type: String,
      default: null,
    },

    name: {
      type: String,
      default() {
        return this.$id('time-view-setting');
      },
    },

    required: Boolean,
    ariaLabelledby: String,
  },

  apollo: {
    options: {
      query: getTimeViewOptions,
    },
  },

  data() {
    return {
      options: [],
      time: this.selectedTime,
    };
  },

  watch: {
    /**
     * @param {String} value
     */
    time(value) {
      this.$emit('change', value);
    },
  },
};
</script>
