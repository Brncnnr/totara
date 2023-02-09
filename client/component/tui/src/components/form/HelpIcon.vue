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
  <div class="tui-formHelpIcon">
    <Popover position="top" :size="size" :title="title" :triggers="['click']">
      <template v-slot:trigger>
        <ButtonIcon
          class="tui-formHelpIcon__icon"
          :aria-label="ariaLabel"
          :title="iconLabel || helpmsg"
          :styleclass="{ transparent: true }"
        >
          <Info v-if="!hidden" size="100" />
        </ButtonIcon>
      </template>
      <slot v-if="$slots.default" />
      <template v-else>{{ helpmsg }}</template>
    </Popover>

    <div :id="descId" class="tui-formHelpIcon__desc">
      <slot v-if="$slots.default" />
      <template v-else>{{ helpmsg }}</template>
    </div>
  </div>
</template>

<script>
// Components
import Info from 'tui/components/icons/Info';
import Popover from 'tui/components/popover/Popover';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';

export default {
  components: {
    Info,
    Popover,
    ButtonIcon,
  },

  props: {
    descId: {
      required: true,
      type: String,
    },
    helpmsg: {
      type: String,
    },
    iconLabel: {
      type: String,
    },
    hidden: {
      type: Boolean,
    },
    size: {
      type: String,
    },
    title: {
      type: String,
    },
    label: String,
  },

  computed: {
    ariaLabel() {
      if (this.label && this.label.trim().length > 0) {
        return this.$str('show_help_for_x', 'totara_core', this.label);
      }

      return this.$str('help', 'core');
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "help"
  ],
  "totara_core": [
    "show_help_for_x"
  ]
}
</lang-strings>

<style lang="scss">
.tui-formHelpIcon {
  display: inline-block;

  &__icon {
    padding: 0;
    color: var(--color-state);
  }

  &__desc {
    // aria-describedby still works even if the target element is display: none
    // https://developer.paciellogroup.com/blog/2015/05/short-note-on-aria-labelledby-and-aria-describedby/
    display: none;
  }
}
</style>
