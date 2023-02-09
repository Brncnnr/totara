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

  @author Alvin Smith <alvin.smith@totaralearning.com>
  @module tui
-->

<template>
  <div class="tui-labelledButtonTrigger">
    <ButtonIcon
      :aria-label="buttonAriaLabel"
      :disabled="disabled"
      :styleclass="styleclass"
      @click="$emit('click', $event)"
    >
      <slot name="icon" />
    </ButtonIcon>

    <Popover
      v-if="$scopedSlots['hover-label-content']"
      :context-mode="contextMode"
      :triggers="['focus', 'hover']"
      :closeable="closeablePopover"
      @open-changed="$emit('popover-open-changed', $event)"
    >
      <template v-slot:trigger="{ isOpen }">
        <Button
          :aria-expanded="isOpen ? 'true' : 'false'"
          :aria-label="triggerAriaLabelText"
          :text="String(labelText)"
          :styleclass="{ transparent: true, small: true }"
          @click="$emit('open', $event)"
        />
      </template>
      <slot name="hover-label-content" />
    </Popover>

    <template v-else>
      <span
        class="tui-labelledButtonTrigger__label"
        @click="$emit('open', $event)"
      >
        {{ labelText }}
      </span>
    </template>
  </div>
</template>

<script>
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Popover from 'tui/components/popover/Popover';
import Button from 'tui/components/buttons/Button';

export default {
  components: {
    ButtonIcon,
    Popover,
    Button,
  },

  props: {
    labelText: {
      type: [String, Number],
      required: true,
    },
    buttonAriaLabel: {
      type: String,
      required: true,
    },
    styleclass: {
      type: Object,
      default() {
        return {
          circle: true,
        };
      },
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    labelAriaLabel: {
      type: String,
      default: '',
    },
    closeablePopover: {
      type: Boolean,
      default: true,
    },
    contextMode: {
      type: String,
      default: 'uncontained',
    },
  },

  computed: {
    triggerAriaLabelText() {
      if (this.labelAriaLabel !== '') {
        return this.labelAriaLabel;
      } else {
        return this.labelText;
      }
    },
  },
};
</script>

<style lang="scss">
.tui-labelledButtonTrigger {
  display: inline-flex;
  flex-direction: column;
  align-items: center;

  &__label {
    color: var(--color-state);
    &:hover {
      text-decoration: underline;
      cursor: pointer;
    }
  }
}
</style>
