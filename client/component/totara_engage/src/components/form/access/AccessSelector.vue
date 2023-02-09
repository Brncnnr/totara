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
  @module totara_engage
-->

<template>
  <RadioGroup
    v-model="access"
    :name="name"
    :required="required"
    class="tui-accessSelector"
    :aria-labelledby="ariaLabelledby"
  >
    <Radio
      v-for="(option, index) in options"
      :key="index"
      :name="name"
      :value="option.value"
      :disabled="isDisabled(option.value)"
    >
      {{ option.label }}
    </Radio>
  </RadioGroup>
</template>

<script>
import RadioGroup from 'tui/components/form/RadioGroup';
import Radio from 'tui/components/form/Radio';
import { AccessManager } from 'totara_engage/index';
import getAccessOptions from 'totara_engage/graphql/access_options';

export default {
  components: {
    RadioGroup,
    Radio,
  },

  props: {
    publicDisabled: {
      type: Boolean,
      default: false,
    },

    restrictedDisabled: {
      type: Boolean,
      default: false,
    },

    privateDisabled: {
      type: Boolean,
      default: false,
    },

    selectedAccess: {
      type: String,
      default: null,
      validator(prop) {
        return AccessManager.isValid(prop);
      },
    },

    required: {
      type: Boolean,
      default: true,
    },

    name: {
      type: String,
      default() {
        return this.$id('access-seting');
      },
    },

    ariaLabelledby: String,
  },

  apollo: {
    options: {
      query: getAccessOptions,
    },
  },

  data() {
    return {
      options: [],
      access: this.selectedAccess,
    };
  },

  watch: {
    /**
     * @param {String} value
     */
    access(value) {
      this.$emit('change', value);
    },
  },

  methods: {
    /**
     *
     * @param {String} value
     * @return {Boolean}
     */
    isDisabled(value) {
      if (AccessManager.isPublic(value)) {
        return this.publicDisabled;
      } else if (AccessManager.isRestricted(value)) {
        return this.restrictedDisabled;
      }

      return this.privateDisabled;
    },
  },
};
</script>
