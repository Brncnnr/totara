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
  <div
    class="tui-loader"
    :class="{ 'tui-loader--active': loading, 'tui-loader--fullpage': fullpage }"
  >
    <div v-if="isEmptySlot() && loading" class="tui-loader__empty" />
    <slot />
    <div v-if="loading" class="tui-loader__overlay" role="alert">
      <div class="tui-loader__overlay-positioner">
        <div class="tui-loader__display">
          <Loading aria-hidden="true" />
          {{ $str('loading', 'core') }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Loading from 'tui/components/icons/Loading';

export default {
  components: {
    Loading,
  },

  props: {
    fullpage: Boolean,
    loading: Boolean,
  },

  methods: {
    isEmptySlot() {
      return !this.$slots.default;
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "loading"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-loader {
  $loading-fullpage: #{&}--fullpage;

  position: relative;

  // Don't show nested loaders
  &--active > * .tui-loader__overlay {
    display: none;
  }

  // If no slot content (e.g. initial load)
  &__empty {
    min-height: var(--gap-8);
  }

  &__overlay {
    position: absolute;
    top: 0;
    /*!rtl:ignore*/
    left: 0;
    width: 100%;
    height: 100%;

    #{$loading-fullpage} & {
      position: fixed;
      z-index: var(--zindex-loading-page);
    }

    &-positioner {
      position: absolute;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      height: 100%;
      background: var(--color-background);
      opacity: 0.85;
      content: '';
    }
  }

  &__display {
    position: sticky;
    top: var(--gap-6);
    bottom: var(--gap-6);
    color: var(--color-neutral-6);
    font-size: var(--font-size-15);

    .fa-spinner {
      position: relative;
      top: -1px;
    }
  }
}
</style>
