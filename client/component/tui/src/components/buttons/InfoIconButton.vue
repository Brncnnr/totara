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
  <Popover :size="size" :triggers="['click']">
    <template v-slot:trigger="{ isOpen }">
      <ButtonIcon
        :aria-expanded="isOpen.toString()"
        :aria-label="label"
        :disabled="disabled"
        class="tui-infoIconButton"
        :styleclass="{
          primary: true,
          small: true,
          transparent: true,
        }"
      >
        <InfoIcon size="100" />
      </ButtonIcon>
    </template>
    <slot />
  </Popover>
</template>

<script>
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import InfoIcon from 'tui/components/icons/Info';
import Popover from 'tui/components/popover/Popover';

export default {
  components: {
    ButtonIcon,
    InfoIcon,
    Popover,
  },

  props: {
    ariaLabel: String,
    disabled: Boolean,
    /**
     * Alternative to aria-label, you specify what the help is, e.g. "Article",
     * and it will create a string like "Show help for Article".
     */
    isHelpFor: String,
    // Optional prop for setting a set width of popover
    size: String,
  },

  computed: {
    label() {
      if (this.ariaLabel && this.ariaLabel.trim().length > 0) {
        return this.ariaLabel;
      }

      return this.$str('show_help_for_x', 'totara_core', this.isHelpFor);
    },
  },

  mounted() {
    if (this.ariaLabel && this.ariaLabel.trim().length > 0) {
      return;
    }
    if (this.isHelpFor && this.isHelpFor.trim().length > 0) {
      return;
    }
    console.error(
      '[InfoIconButton] You must pass either aria-label or is-help-for.'
    );
  },
};
</script>

<lang-strings>
  {
    "totara_core": [
      "show_help_for_x"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-infoIconButton {
  &.tui-iconBtn {
    width: auto;
    height: auto;
    vertical-align: -0.125em;
  }
}
</style>
