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
  @module tui
-->

<template>
  <Field
    v-slot="fieldSlotProps"
    :name="name"
    :validate="validate"
    :validations="validations"
  >
    <div
      class="tui-formField"
      :class="[
        hasError(fieldSlotProps) && 'tui-contextInvalid',
        charLength && 'tui-formField--charLength-' + charLength,
        charLength && 'tui-input--customSize',
      ]"
    >
      <slot v-bind="makeSlotProps(fieldSlotProps)" />
      <FieldError :id="$id('error')" :error="computeError(fieldSlotProps)" />
    </div>
  </Field>
</template>

<script>
import Field from 'tui/components/reform/Field';
import FieldError from 'tui/components/form/FieldError';
import { charLengthProp } from '../form/form_common';

export default {
  components: {
    Field,
    FieldError,
  },

  props: {
    name: {
      type: [String, Number, Array],
      required: true,
    },
    error: String,

    validate: Function,
    validations: [Function, Array],

    charLength: charLengthProp,
  },

  methods: {
    makeSlotProps(slotProps) {
      const errorId = this.hasError(slotProps) ? this.$id('error') : null;
      const ariaDescribedby = slotProps.ariaDescribedby || null;

      return Object.assign({}, slotProps, {
        errorId,
        attrs: {
          id: slotProps.id,
          name: slotProps.inputName,
          'aria-describedby': [ariaDescribedby, errorId].join(' '),
          'aria-invalid': errorId ? 'true' : null,
        },
      });
    },

    hasError(slotProps) {
      return !!(this.error || slotProps.error);
    },

    computeError(slotProps) {
      const error = this.error || slotProps.error;
      if (error) {
        return error.toString();
      }
    },
  },
};
</script>

<style lang="scss">
.tui-formField {
  flex-grow: 1;
  width: 100%;
  min-width: 0;

  @include tui-char-length-classes();
}
</style>
