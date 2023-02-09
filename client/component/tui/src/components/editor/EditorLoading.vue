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
    class="tui-editorLoading"
    :class="{ 'tui-editorLoading--compact': compact }"
  >
    <div class="tui-editorLoading__inner">
      <Loading
        class="tui-editorLoading__spinner"
        :alt="$str('loading', 'core')"
      />
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
    compact: Boolean,
  },
};
</script>

<lang-strings>
{
  "core": ["loading"]
}
</lang-strings>

<style lang="scss">
.tui-editorLoading {
  display: flex;
  flex-direction: column;
  justify-content: center;
  width: 100%;
  background-color: var(--form-input-bg-color);
  border: var(--border-width-thin) solid var(--color-neutral-5);

  // inner must be separate for intrinsic height to work
  &__inner {
    display: flex;
    align-items: center;
    justify-content: center;

    // similar to default Weka height
    height: calc(9.5rem);
  }

  &--compact &__inner {
    // similar to default Weka height with compact on
    height: calc(6.2rem);
  }

  &__spinner {
    color: var(--color-neutral-5);
  }
}
</style>
