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
  @module tui
-->

<template>
  <FormRow>
    <SubmitCancelGroup
      :submitting="submitting"
      @submit="submit"
      @cancel="cancel"
    />
  </FormRow>
</template>

<script>
// Components
import SubmitCancelGroup from 'tui/components/buttons/SubmitCancelGroup';
import FormRow from 'tui/components/form/FormRow';

export default {
  components: {
    SubmitCancelGroup,
    FormRow,
  },

  props: {
    submitting: Boolean,
  },

  methods: {
    cancel() {
      this.$emit('cancel');
    },

    submit(e) {
      this.$emit('submit', e);
    },
  },
};
</script>
