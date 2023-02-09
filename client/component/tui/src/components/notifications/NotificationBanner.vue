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
  <div
    v-if="!dismissed"
    class="tui-notificationBanner"
    :class="'tui-notificationBanner--' + type"
  >
    <div class="tui-notificationBanner__icon">
      <component
        :is="iconForType"
        :alt="labelForIconType + ': '"
        :title="labelForIconType"
        :size="200"
        state="none"
      />
    </div>
    <div v-if="$scopedSlots['body']" class="tui-notificationBanner__body">
      <slot name="body" />
    </div>

    <div
      v-else
      class="tui-notificationBanner__message"
      :class="{ 'tui-notificationBanner__message--inline': inlineMessage }"
      v-html="message"
    />

    <div
      v-if="isDismissable"
      class="tui-notificationBanner__dismiss"
      aria-hidden="true"
    >
      <CloseButton
        class="tui-notificationBanner__dismiss_button"
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
    dismissable: Boolean,
    inlineMessage: Boolean,
    message: String,
    selfDismiss: Boolean,
    type: {
      type: String,
      default: 'info',
      validator: val => ['info', 'success', 'warning', 'error'].includes(val),
    },
  },

  data() {
    return {
      dismissed: false,
    };
  },

  computed: {
    /**
     * Check if the notification can be manually closed
     *
     * @returns {boolean}
     */
    isDismissable() {
      return this.dismissable || this.selfDismiss;
    },

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
      if (this.dismissable) {
        this.$emit('dismiss');
      }
      if (this.selfDismiss) {
        this.dismissed = true;
      }
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
@mixin tui-notification-banner-color($name, $color) {
  .tui-notificationBanner {
    &--#{$name} {
      border-color: $color;
    }

    &--#{$name} &__icon {
      background: $color;
    }
  }
}

.tui-notificationBanner {
  @include tui-font-body-small();

  display: flex;
  background-color: var(--color-background);
  border: var(--border-width-thin) solid var(--color-prompt-info);
  border-radius: var(--border-radius-small);

  &__icon {
    display: flex;
    padding: var(--gap-4);
    color: var(--color-neutral-1);
    background: var(--color-prompt-info);
    // -1px to avoid issue with razor thin white line between icon container and notification border
    // prettier-ignore
    border-top-left-radius: calc(var(--border-radius-small) - var(--border-width-thin) - 1px);
    // prettier-ignore
    border-bottom-left-radius: calc(var(--border-radius-small) - var(--border-width-thin) - 1px);
  }

  &__body {
    flex-grow: 1;
  }

  &__message {
    display: flex;
    flex: 1;
    align-items: center;
    padding: var(--gap-3);

    &--inline {
      display: inline;
    }
  }

  &__dismiss {
    display: flex;

    &_button {
      color: var(--color-neutral-6);
    }
  }
}

@include tui-notification-banner-color('success', var(--color-prompt-success));
@include tui-notification-banner-color('warning', var(--color-prompt-warning));
@include tui-notification-banner-color('error', var(--color-prompt-alert));

@media screen and (min-width: $tui-screen-sm) {
  .tui-notificationBanner {
    @include tui-font-body();

    border-radius: var(--border-radius-normal);

    &__icon {
      // -1px to avoid issue with razor thin white line between icon container and notification border
      // prettier-ignore
      border-top-left-radius: calc(var(--border-radius-normal) - var(--border-width-thin) - 1px);
      // prettier-ignore
      border-bottom-left-radius: calc(var(--border-radius-normal) - var(--border-width-thin) - 1px);
    }
  }
}
</style>
