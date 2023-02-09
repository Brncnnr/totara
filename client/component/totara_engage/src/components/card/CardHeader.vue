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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module totara_engage
-->

<template>
  <div class="tui-engageCardHeader">
    <div class="tui-engageCardHeader__first">
      <slot name="first" />
    </div>
    <div class="tui-engageCardHeader__second">
      <slot name="second" />
    </div>
    <div class="tui-engageCardHeader__third">
      <slot name="third" />
    </div>
  </div>
</template>

<style lang="scss">
.tui-engageCardHeader {
  &__first {
    display: flex;
    justify-content: flex-end;
  }

  &__second {
    * {
      margin: 0;
    }
  }
}
</style>
