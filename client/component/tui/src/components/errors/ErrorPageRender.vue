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
  <div class="tui-errorDisplay">
    <h1 class="tui-errorDisplay__title">
      {{ $tryStr('error', 'core') || 'Error' }}
    </h1>
    <p>
      {{
        message ||
          $tryStr('error:pagerender', 'totara_core') ||
          'An error occurred while rendering the page.'
      }}
    </p>
    <div class="tui-errorDisplay__buttons">
      <button v-if="retryable" type="button" @click.prevent="retry">
        {{ $str('retry', 'totara_core') }}
      </button>
    </div>
    <a
      v-if="error"
      class="tui-errorDisplay__detailsToggle"
      href="javascript:;"
      @click.prevent="toggleDetails"
    >
      {{ (showDetails ? '▾ ' : '▸ ') + $str('details', 'totara_core') }}
    </a>
    <pre v-if="error && showDetails" class="tui-errorDisplay__detailsPre">{{
      error.stack ? error.stack : error
    }}</pre>
  </div>
</template>

<script>
export default {
  props: {
    message: String,
    // error can accept any type as long as it or its .stack property can be converted to a string
    // eslint-disable-next-line vue/require-prop-types
    error: {},
    retryable: Boolean,
  },

  data() {
    return {
      showDetails: false,
    };
  },

  methods: {
    toggleDetails() {
      this.showDetails = !this.showDetails;
    },

    retry() {
      this.$emit('retry');
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "error"
  ],
  "totara_core": [
    "details",
    "error:pagerender",
    "retry"
  ]
}
</lang-strings>

<style lang="scss">
.tui-errorDisplay {
  padding: var(--gap-8);
  background: var(--color-neutral-3);
  border-radius: 4px;

  &__title {
    margin-top: 0;
  }

  &__buttons {
    margin-bottom: var(--gap-4);
  }

  &__buttons:last-child {
    margin-bottom: 0;
  }

  &__detailsToggle {
    text-decoration: none;

    &:hover,
    &:focus {
      text-decoration: none;
    }
  }

  &__detailsPre {
    margin-top: var(--gap-2);
    margin-bottom: 0;
    padding: var(--gap-4);
    background-color: var(--color-neutral-1);
    border-radius: 4px;
  }
}
</style>
