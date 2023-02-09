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
  <div v-weka-node-ui class="tui-editor_weka-nodeMenu" contenteditable="false">
    <slot />
  </div>
</template>

<script>
import { wekaNodeUI } from '../directives';

export default {
  directives: {
    'weka-node-ui': wekaNodeUI,
  },

  props: {
    contextMode: {
      type: String,
      required: true,
      validator: x => x === 'contained' || x === 'uncontained',
    },
  },

  provide() {
    return {
      wekaNodeMenuContext: this.wekaNodeMenuContext,
    };
  },

  computed: {
    wekaNodeMenuContext() {
      return {
        contextMode: this.contextMode,
      };
    },
  },
};
</script>

<style lang="scss">
.tui-editor_weka-nodeMenu {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  align-items: center;
  padding: var(--weka-node-menu-padding);
  background-color: var(--color-neutral-1);
  border-radius: 4px;
  box-shadow: var(--shadow-2);
  user-select: none;

  @include tui-font-body-small();
}
</style>
