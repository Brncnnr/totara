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
  <div v-if="error" class="tui-formFieldError">
    <div class="tui-formFieldError__inner">
      {{ error }}

      <ButtonIcon
        v-if="dismissable"
        :aria-label="$str('closebuttontitle', 'core')"
        class="tui-formFieldError__dismiss"
        :styleclass="{
          small: true,
          transparent: true,
        }"
        @click="$emit('dismiss')"
      >
        <CloseIcon custom-class="tui-formFieldError__dismiss-icon" />
      </ButtonIcon>
    </div>
  </div>
</template>

<script>
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import CloseIcon from 'tui/components/icons/Close';

export default {
  components: {
    ButtonIcon,
    CloseIcon,
  },

  props: {
    error: String,
    dismissable: Boolean,
  },
};
</script>

<lang-strings>
{
  "core": [
    "closebuttontitle"
  ]
}
</lang-strings>

<style lang="scss">
.tui-formFieldError {
  // offset the extra red shadow on invalid elements + we want the error
  // message to be spaced 1px away
  margin-top: calc(var(--form-input-invalid-shadow-size) + 1px);

  &__inner {
    @include tui-font-body-small();
    display: inline-flex;
    align-items: center;
    padding: 0.6rem 0.8rem;
    color: var(--form-error-text-color);
    background: var(--form-error-bg-color);
  }

  &__dismiss {
    margin-left: var(--gap-1);
    padding: 0;
    color: inherit;
    opacity: 0.7;

    &:hover:active,
    &:active,
    &:hover,
    &:focus {
      color: inherit;
      opacity: 1;
    }

    &-icon {
      position: relative;
      top: -1px;
      @include tui-font-body-small();
    }
  }
}
</style>
