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
  <LayoutIconBase v-slot="{ strokeClass, semiFillClass }">
    <rect
      x="0.5"
      y="4.5"
      width="23"
      height="15"
      rx="1.5"
      :class="strokeClass"
    />
    <path
      d="M0.5 6C0.5 5.17157 1.17157 4.5 2 4.5H7.5V19.5H2C1.17157 19.5 0.5 18.8284 0.5 18V6Z"
      :class="[strokeClass, semiFillClass]"
    />
    <line x1="7.5" y1="4" x2="7.5" y2="20" :class="strokeClass" />
  </LayoutIconBase>
</template>

<script>
import LayoutIconBase from 'editor_weka/components/editing/graphics/layout_icons_24/LayoutIconBase';

export default {
  components: {
    LayoutIconBase,
  },
};
</script>
