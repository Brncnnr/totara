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
  <ActionDropdown
    :position="position"
    :actions="actions"
    :context-mode="contextMode"
  >
    <template v-slot:trigger="{ toggle, isOpen }">
      <NodeMenuButton
        :text="text"
        :aria-expanded="isOpen.toString()"
        :aria-label="ariaLabel"
        :title="title"
        :caret="caret"
        v-bind="buttonAttrs"
        @click="toggle"
      >
        <template v-if="$scopedSlots.icon" v-slot:icon>
          <slot name="icon" />
        </template>
      </NodeMenuButton>
    </template>
  </ActionDropdown>
</template>

<script>
import ActionDropdown from 'editor_weka/components/editing/ActionDropdown';
import NodeMenuButton from 'editor_weka/components/node_menu/NodeMenuButton';

export default {
  components: {
    ActionDropdown,
    NodeMenuButton,
  },

  props: {
    text: String,
    ariaLabel: String,
    title: String,
    position: String,
    actions: Array,
    caret: {
      type: Boolean,
      default: true,
    },
    buttonAttrs: Object,
  },

  inject: ['wekaNodeMenuContext'],

  computed: {
    contextMode() {
      return this.wekaNodeMenuContext && this.wekaNodeMenuContext.contextMode;
    },
  },
};
</script>
