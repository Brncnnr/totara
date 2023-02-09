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
  @module samples
-->

<template>
  <div class="tui-testInputGroup">
    <Form>
      <FormRow v-slot="{ id, labelId }" label="InputGroup">
        <InputGroup :aria-labelledby="labelId">
          <template>
            <InputGroupInput
              :id="id"
              :monospace="true"
              readonly
              :type="showPassword ? 'text' : 'password'"
              :value="password"
            />
            <InputGroupButton
              class="tui-testInputGroup__button"
              :aria-controls="id"
              :text="showHideText"
              @click="toggleVisibility"
            />
          </template>
        </InputGroup>
      </FormRow>
    </Form>
  </div>
</template>

<script>
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';

import InputGroup from 'tui/components/form/InputGroup';
import InputGroupInput from 'tui/components/form/InputGroupInput';
import InputGroupButton from 'tui/components/form/InputGroupButton';

export default {
  components: {
    Form,
    FormRow,
    InputGroup,
    InputGroupInput,
    InputGroupButton,
  },

  data() {
    return {
      showPassword: false,
      password: 'wafUFSKRawfXpq7QkRlu8CdW',
    };
  },

  computed: {
    showHideText() {
      return this.showPassword ? 'Hide' : 'Show';
    },
  },

  methods: {
    toggleVisibility() {
      this.showPassword = !this.showPassword;
    },
  },
};
</script>

<style lang="scss">
.tui-testInputGroup {
  &__button {
    min-width: 6rem;
  }
}
</style>
