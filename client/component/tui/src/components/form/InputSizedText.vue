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
  <div class="tui-formInputSizedText">
    <slot />
  </div>
</template>

<style lang="scss">
.tui-formInputSizedText {
  // prettier-ignore
  padding: calc((var(--form-input-height) - (1em * var(--font-body-line-height))) / 2) 0;
  line-height: var(--font-body-line-height);
}
</style>
