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
  <img
    :src="src"
    :alt="alt"
    class="tui-avatar"
    :class="`tui-avatar--${size}`"
  />
</template>

<script>
export default {
  props: {
    src: {
      required: true,
      type: String,
    },

    alt: {
      required: true,
      type: String,
    },

    size: {
      type: String,
      default: 'medium',
      validator(value) {
        return ['large', 'medium', 'small', 'xsmall', 'xxsmall'].includes(
          value
        );
      },
    },
  },
};
</script>

<style lang="scss">
:root {
  --avatar-border-radius: 50%;
  --avatar-large-width: 185px;
  --avatar-large-height: 185px;
  --avatar-large-padding: 5px;
  --avatar-medium-width: 105px;
  --avatar-medium-height: 105px;
  --avatar-medium-padding: 3px;
  --avatar-small-width: 50px;
  --avatar-small-height: 50px;
  --avatar-small-padding: 2px;
  --avatar-xsmall-width: 38px;
  --avatar-xsmall-height: 38px;
  --avatar-xsmall-padding: 1px;
  --avatar-xxsmall-width: 24px;
  --avatar-xxsmall-height: 24px;
  --avatar-xxsmall-padding: 1px;
}

.tui-avatar {
  border: var(--border-width-normal) solid var(--avatar-border-color);
  border-radius: var(--avatar-border-radius);

  &--large {
    width: var(--avatar-large-width);
    height: var(--avatar-large-height);
    padding: var(--avatar-large-padding);
  }

  &--medium {
    width: var(--avatar-medium-width);
    height: var(--avatar-medium-height);
    padding: var(--avatar-medium-padding);
  }

  &--small {
    width: var(--avatar-small-width);
    height: var(--avatar-small-height);
    padding: var(--avatar-small-padding);
  }

  &--xsmall {
    width: var(--avatar-xsmall-width);
    height: var(--avatar-xsmall-height);
    padding: var(--avatar-xsmall-padding);
  }

  &--xxsmall {
    width: var(--avatar-xxsmall-width);
    height: var(--avatar-xxsmall-height);
    padding: var(--avatar-xxsmall-padding);
  }
}
</style>
