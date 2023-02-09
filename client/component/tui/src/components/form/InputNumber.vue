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
  <Input v-bind="$props" type="number" v-on="$listeners" />
</template>

<script>
// Components
import Input from 'tui/components/form/Input';

export default {
  components: {
    Input,
  },
  inheritAttrs: false,

  /* eslint-disable vue/require-prop-types */
  props: [
    'ariaDescribedby',
    'ariaInvalid',
    'ariaLabel',
    'ariaLabelledby',
    'autocomplete',
    'autofocus',
    'dir',
    'disabled',
    'id',
    'charLength',
    'max',
    'min',
    'name',
    'pattern',
    'placeholder',
    'readonly',
    'required',
    'step',
    'styleclass',
    'value',
  ],
};
</script>
