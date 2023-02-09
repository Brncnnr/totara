<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Simon Chester <simon.chester@totaralearning.com>
  @module editor_weka
-->

<template>
  <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
    <slot
      stroke-class="tui-editor_weka-layoutIcon24Base__stroke"
      semi-fill-class="tui-editor_weka-layoutIcon24Base__semiFill"
    />
  </svg>
</template>

<style lang="scss">
.tui-editor_weka-layoutIcon24Base {
  &__stroke {
    stroke: var(--color-neutral-6);
  }

  &__semiFill {
    fill: var(--color-neutral-5);
  }
}
</style>
