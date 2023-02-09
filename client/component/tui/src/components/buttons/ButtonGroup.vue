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
  <div class="tui-formBtnGroup">
    <slot />
  </div>
</template>

<style lang="scss">
.tui-formBtnGroup {
  display: flex;

  .tui-formBtn {
    min-width: 80px;
  }

  > :not(:first-child) {
    margin: auto 0 auto var(--gap-4);
  }
}
</style>
