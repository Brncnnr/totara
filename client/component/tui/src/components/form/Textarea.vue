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
  <textarea
    :id="id"
    class="tui-formTextarea"
    :class="[
      charLength ? 'tui-formTextarea--charLength-' + charLength : null,
      charLength ? 'tui-input--customSize' : null,
    ]"
    :aria-describedby="ariaDescribedby"
    :aria-label="ariaLabel"
    :aria-labelledby="ariaLabelledby"
    :autocomplete="autocomplete"
    :autofocus="autofocus"
    :cols="cols"
    :dir="dir"
    :disabled="disabled"
    :maxlength="maxlength"
    :minlength="minlength"
    :name="name"
    :placeholder="placeholder"
    :readonly="readonly"
    :required="required"
    :rows="rows"
    :spellcheck="spellcheck"
    :wrap="wrap"
    :value="value"
    @input="$emit('input', $event.target.value)"
  />
</template>

<script>
import { charLengthProp } from './form_common';

export default {
  props: {
    ariaDescribedby: [String, Boolean],
    ariaInvalid: [String, Boolean],
    ariaLabel: [String, Boolean],
    ariaLabelledby: [String, Boolean],
    autocomplete: String,
    autofocus: Boolean,
    cols: Number,
    dir: {
      type: String,
      validator: x => ['auto', 'ltr', 'rtl', null].includes(x),
    },
    disabled: Boolean,
    id: String,
    charLength: charLengthProp,
    maxlength: Number,
    minlength: Number,
    name: String,
    placeholder: String,
    readonly: Boolean,
    required: Boolean,
    rows: [Number, String],
    spellcheck: {
      type: [String, Boolean],
      default: null,
    },
    wrap: String,
    value: String,
  },
};
</script>

<style lang="scss">
.tui-formTextarea {
  display: block;
  flex-grow: 1;
  width: 100%;
  min-width: 0;
  max-width: 100%;
  max-height: 100%;
  padding: var(--gap-1) var(--gap-2);
  overflow: auto;
  color: var(--form-input-text-color);
  font-size: var(--form-input-font-size);
  font-family: inherit;
  line-height: inherit;
  border: var(--form-input-border-size) solid var(--form-input-border-color);
  resize: none;

  @include tui-char-length-classes();

  &::placeholder {
    color: var(--form-input-text-placeholder-color);
  }

  .tui-contextInvalid & {
    border-color: var(--form-input-border-color-invalid);
    box-shadow: var(--form-input-shadow-invalid);
  }

  &:focus {
    background: var(--form-input-bg-color-focus);
    border: var(--form-input-border-size) solid
      var(--form-input-border-color-focus);
    outline: none;
    box-shadow: var(--form-input-shadow-focus);

    .tui-contextInvalid & {
      background: var(--form-input-bg-color-invalid-focus);
      border-color: var(--form-input-border-color-invalid);
      box-shadow: var(--form-input-shadow-invalid-focus);
    }
  }

  &[disabled] {
    color: var(--form-input-text-color-disabled);
    background: var(--form-input-bg-color-disabled);
    border-color: var(--form-input-border-color-disabled);

    &::placeholder {
      color: var(--form-input-text-color-disabled);
    }
  }
}
</style>
