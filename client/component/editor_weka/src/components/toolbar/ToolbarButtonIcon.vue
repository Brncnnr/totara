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
  @module editor_weka
-->

<template>
  <ButtonIcon
    class="tui-wekaToolbarButtonIcon"
    :class="{
      'tui-wekaToolbarButtonIcon--selected': selected,
      'tui-wekaToolbarButtonIcon--iconOnly': iconOnly,
    }"
    :aria-label="text"
    :aria-pressed="ariaPressed"
    :title="text"
    :disabled="disabled"
    :caret="caret"
    @click="$emit('click', $event)"
  >
    <slot name="icon" />
  </ButtonIcon>
</template>

<script>
import ButtonIcon from 'tui/components/buttons/ButtonIcon';

export default {
  components: {
    ButtonIcon,
  },

  props: {
    text: {
      type: String,
      required: true,
    },

    // null = not selectable
    selected: {
      type: Boolean,
      default: null,
    },

    iconOnly: Boolean,

    disabled: Boolean,

    caret: {
      type: Boolean,
      default: false,
    },
  },

  computed: {
    ariaPressed() {
      if (this.selected == null) {
        return false; // unset - not pressable
      }
      return this.selected ? 'true' : 'false';
    },
  },
};
</script>

<style lang="scss">
.tui-wekaToolbarButtonIcon {
  // stylelint-disable-next-line tui/at-extend-only-placeholders
  @extend .tui-wekaToolbarButton;

  &--selected {
    // stylelint-disable-next-line tui/at-extend-only-placeholders
    @extend .tui-wekaToolbarButton--selected;
  }
}
</style>
