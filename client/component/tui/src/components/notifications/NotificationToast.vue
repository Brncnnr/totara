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

  @author Arshad Anwer <arshad.anwer@totaralearning.com>
  @module totara_core
-->

<template>
  <div
    class="tui-notificationToast"
    :class="{
      ['tui-notificationToast--' + type]: type,
    }"
  >
    <div class="tui-notificationToast__icon">
      <component
        :is="iconForType"
        :alt="labelForIconType + ': '"
        :title="labelForIconType"
        :size="200"
      />
    </div>
    <div class="tui-notificationToast__message" v-html="message" />
    <div class="tui-notificationToast__dismiss" aria-hidden="true">
      <CloseButton
        class="tui-notificationToast__dismiss_button"
        :size="300"
        @click="dismiss"
      />
    </div>
  </div>
</template>

<script>
// Components
import CloseButton from 'tui/components/buttons/CloseIcon';
import ErrorIcon from 'tui/components/icons/Error';
import InfoIcon from 'tui/components/icons/Info';
import SuccessIcon from 'tui/components/icons/SuccessSolid';
import WarningIcon from 'tui/components/icons/Warning';

const icons = {
  error: ErrorIcon,
  info: InfoIcon,
  success: SuccessIcon,
  warning: WarningIcon,
};

export default {
  components: {
    CloseButton,
  },

  props: {
    message: String,
    type: {
      type: String,
      default: 'info',
      validator: val => ['success', 'error'].includes(val),
    },
  },

  computed: {
    /**
     * Return icon component for the type of notification
     *
     * @returns {Component}
     */
    iconForType() {
      return icons[this.type];
    },

    /**
     * Text to display for type icon.
     *
     * @returns {string}
     */
    labelForIconType() {
      switch (this.type) {
        case 'info':
        case 'success':
        case 'warning':
        case 'error':
          return this.$str(this.type, 'core');
        default:
          return null;
      }
    },
  },

  methods: {
    /**
     * Dismiss the notification
     *
     */
    dismiss() {
      this.$emit('dismiss');
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "error",
    "info",
    "success",
    "warning"
  ]
}
</lang-strings>

<style lang="scss">
@mixin tui-notification-toast-color($name, $color) {
  .tui-notificationToast {
    &--#{$name} {
      background: $color;
    }

    &--#{$name} &__icon {
      background: $color;
    }
  }
}

.tui-notificationToast {
  @include tui-font-body-small();

  display: flex;
  color: var(--toast-color-text);
  background-color: var(--color-prompt-success);
  border-radius: var(--border-radius-small);
  box-shadow: var(--shadow-3);

  &__icon {
    display: flex;
    align-items: center;
    padding: var(--gap-4) 0 var(--gap-4) var(--gap-4);
    color: var(--color-neutral-1);
    background: var(--color-prompt-success);
    // -1px to avoid isue with razor thin white line between icon container and notification border
    // prettier-ignore
    border-top-left-radius: calc(var(--border-radius-small) - var(--border-width-thin) - 1px);
    // prettier-ignore
    border-bottom-left-radius: calc(var(--border-radius-small) - var(--border-width-thin) - 1px);
  }

  &__message {
    display: flex;
    flex: 1;
    align-items: center;
    padding: var(--gap-4) var(--gap-2);
  }

  &__dismiss {
    display: flex;

    &_button {
      color: var(--color-neutral-3);

      &:hover {
        color: var(--color-neutral-1);
      }
    }
  }
}

@include tui-notification-toast-color('error', var(--color-prompt-alert));

@media screen and (min-width: $tui-screen-sm) {
  .tui-notificationToast {
    @include tui-font-body();
    color: var(--toast-color-text);

    border-radius: var(--border-radius-normal);

    &__icon {
      // -1px to avoid isue with razor thin white line between icon container and notification border
      // prettier-ignore
      border-top-left-radius: calc(var(--border-radius-normal) - var(--border-width-thin) - 1px);
      // prettier-ignore
      border-bottom-left-radius: calc(var(--border-radius-normal) - var(--border-width-thin) - 1px);
    }
  }
}
</style>
