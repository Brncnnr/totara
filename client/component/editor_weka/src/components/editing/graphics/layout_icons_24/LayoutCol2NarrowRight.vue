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
      d="M16.5 4.5H22C22.8284 4.5 23.5 5.17157 23.5 6V18C23.5 18.8284 22.8284 19.5 22 19.5H16.5V4.5Z"
      :class="[strokeClass, semiFillClass]"
    />
    <line x1="16.5" y1="4" x2="16.5" y2="20" :class="strokeClass" />
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
