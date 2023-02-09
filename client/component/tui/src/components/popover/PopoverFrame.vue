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
    class="tui-popoverFrame"
    :class="[
      side ? 'tui-popoverFrame--' + side : null,
      size ? 'tui-popoverFrame--size-' + size : null,
    ]"
  >
    <Arrow :relative-side="arrowSide" :distance="arrowDistance" />
    <CloseButton
      v-if="closeable || srCloseable"
      class="tui-popoverFrame__close"
      :class="{ 'sr-only': !closeable }"
      @click="$emit('close')"
    />
    <div v-if="title" class="tui-popoverFrame__title">
      {{ title }}
    </div>
    <div
      role="tooltip"
      :class="{
        'tui-popoverFrame__content': true,
        'tui-popoverFrame__content--nonClosable': !closeable,
      }"
    >
      <slot />
    </div>
    <div v-if="$scopedSlots.buttons" class="tui-popoverFrame__buttons">
      <slot name="buttons" />
    </div>
  </div>
</template>

<script>
import Arrow from 'tui/components/decor/Arrow';
import CloseButton from 'tui/components/buttons/CloseIcon';
import { langSide } from 'tui/i18n';

export default {
  components: {
    Arrow,
    CloseButton,
  },

  props: {
    title: String,
    side: String,
    size: {
      type: String,
      validator(value) {
        const allowedOptions = ['sm', 'md', 'lg'];
        return allowedOptions.indexOf(value) !== -1;
      },
    },
    srCloseable: Boolean,
    arrowDistance: Number,
    closeable: {
      type: Boolean,
      default: true,
    },
  },

  computed: {
    arrowSide() {
      return langSide(this.side);
    },
  },
};
</script>

<style lang="scss">
.tui-popoverFrame {
  @include tui-font-body();
  position: relative;
  max-width: 300px;
  // margin must be equal on all 4 sides, and must not change with position
  margin: 10px;
  padding: var(--gap-4);
  background: var(--color-background);
  background-clip: padding-box;
  box-shadow: var(--shadow-3);

  &::before {
    position: absolute;
    top: 0;
    left: 0;
    z-index: -1;
    width: 100%;
    height: 100%;
    box-shadow: 0 0 0 1px var(--color-neutral-5);
    content: '';
  }

  &--size-sm {
    width: 250px;
    max-width: none;
  }

  &--size-md {
    width: 300px;
    max-width: none;
  }

  &--size-lg {
    width: 95vw;
    max-width: none;

    @media (min-width: 600px) {
      width: 560px;
    }
  }

  &__close {
    position: absolute;
    top: 0;
    right: 0;
    display: flex;
    padding: calc(1.4rem - 0.1em) calc(1.4rem - 0.3em);
  }

  &__title {
    @include tui-font-heading-x-small();
    padding-top: var(--gap-2);
    padding-right: var(--gap-4);
  }

  &__content {
    padding: var(--gap-2) var(--gap-4) var(--gap-2) 0;

    & img {
      max-width: 100%;
    }
  }

  &__title + &__content,
  &__content--nonClosable {
    padding-right: 0;
  }

  &__buttons {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    padding-top: var(--gap-1);
    padding-bottom: var(--gap-3);

    > * {
      margin-top: var(--gap-2);
    }

    > * + * {
      margin-left: var(--gap-4);
    }
  }
}
</style>
