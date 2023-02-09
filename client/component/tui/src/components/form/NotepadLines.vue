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

  @author Jaron Steenson <jaron.steenson@totaralearning.com>
  @module tui
-->

<template>
  <div
    class="tui-notepadLines"
    :class="charLength ? 'tui-notepadLines--charLength-' + charLength : null"
  >
    <div v-for="n in lines" :key="n" class="tui-notepadLines__line" />
  </div>
</template>

<script>
import { charLengthProp } from './form_common';

export default {
  props: {
    lines: {
      type: Number,
      default: 1,
      validator(value) {
        return value >= 1;
      },
    },
    charLength: charLengthProp,
  },
};
</script>

<style lang="scss">
.tui-notepadLines {
  @include tui-char-length-classes;

  &__line {
    height: var(--form-notepad-line-height);
    border-bottom: var(--form-notepad-line-border);
  }
}
</style>
