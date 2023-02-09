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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module samples
-->

<template>
  <FormScope :path="path" :validate="validate" :process="process">
    <FormRowStack>
      <FormRow label="First name" required>
        <FormText name="first" />
      </FormRow>
      <FormRow label="Last name" required>
        <FormText name="last" />
      </FormRow>
    </FormRowStack>
  </FormScope>
</template>

<script>
import FormScope from 'tui/components/reform/FormScope';
import { FormRow, FormRowStack, FormText } from 'tui/components/uniform';

export default {
  components: {
    FormScope,
    FormRow,
    FormText,
    FormRowStack,
  },

  props: {
    path: [String, Array],
  },

  methods: {
    validate(values = {}) {
      const errors = {};
      if (!values.first) errors.first = 'First name is required';
      if (!values.last) errors.last = 'Last name is required';
      return errors;
    },

    // reshape form value on submission
    // if this was left out the result would simply be an object with { first, last }
    process(values) {
      return `${values.first} ${values.last}`;
    },
  },
};
</script>
