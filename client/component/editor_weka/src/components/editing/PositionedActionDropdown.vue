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
  @module editor_weka
-->

<template>
  <div :style="wrapperStyle">
    <ActionDropdown :actions="actions" :open="true" @dismiss="$emit('dismiss')">
      <template v-slot:trigger="triggerProps">
        <slot name="trigger" v-bind="triggerProps" />
      </template>
    </ActionDropdown>
  </div>
</template>

<script>
import ActionDropdown from 'editor_weka/components/editing/ActionDropdown';

export default {
  components: {
    ActionDropdown,
  },

  props: {
    location: {
      type: Object,
      validator: p => 'x' in p && 'y' in p,
    },
    actions: Array,
  },

  computed: {
    wrapperStyle() {
      return {
        position: 'absolute',
        left: this.location.x + 'px',
        top: this.location.y + 'px',
      };
    },
  },
};
</script>
