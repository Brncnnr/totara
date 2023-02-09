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
  @module totara_competency
-->

<template>
  <div
    class="tui-progressCircle"
    :class="{
      'tui-progressCircle--complete': complete,
    }"
  >
    <!-- Proficiency progress circle -->
    <div class="tui-progressCircle__circle">
      <span class="tui-progressCircle__circle-text">
        {{
          $str('achieved_n_out_of_n', 'totara_competency', {
            current: completed,
            target: target,
          })
        }}
      </span>
    </div>

    <div class="tui-progressCircle__label">
      {{ $str('completed', 'totara_competency') }}
    </div>
  </div>
</template>

<script>
export default {
  props: {
    complete: {
      type: Boolean,
    },
    completed: {
      type: Number,
    },
    target: {
      type: Number,
    },
  },
};
</script>

<lang-strings>
  {
    "totara_competency": [
      "achieved_n_out_of_n",
      "completed"
    ]
  }

</lang-strings>

<style lang="scss">
.tui-progressCircle {
  &__circle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 70px;
    height: 70px;
    margin: var(--gap-6) auto 0;
    background: var(--color-neutral-4);
    border-radius: 100%;
  }

  &__label {
    margin-top: var(--gap-1);
    text-align: center;
  }

  &--complete &__circle {
    background: var(--color-primary);
  }
}
</style>
