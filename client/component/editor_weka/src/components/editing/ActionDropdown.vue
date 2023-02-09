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
  <Dropdown
    :position="position"
    :open="open"
    :context-mode="contextMode"
    @dismiss="$emit('dismiss')"
  >
    <template v-slot:trigger="triggerProps">
      <slot name="trigger" v-bind="triggerProps" />
    </template>
    <slot name="dropdown-items">
      <DropdownButton
        v-for="(action, i) in actions"
        :key="i"
        @click.prevent="action.action()"
      >
        <div class="tui-editor_weka-actionDropdown__dropdownItemContent">
          <div
            v-if="action.iconComponent"
            class="tui-editor_weka-actionDropdown__dropdownItemIcon"
          >
            <component :is="action.iconComponent" />
          </div>
          {{ action.label }}
        </div>
      </DropdownButton>
    </slot>
  </Dropdown>
</template>

<script>
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownButton from 'tui/components/dropdown/DropdownButton';

export default {
  components: {
    Dropdown,
    DropdownButton,
  },

  props: {
    position: String,
    open: Boolean,
    actions: Array,
    contextMode: String,
  },
};
</script>

<style lang="scss">
.tui-editor_weka-actionDropdown {
  &__dropdownItemContent {
    display: flex;
    align-items: center;
  }

  &__dropdownItemIcon {
    display: flex;
    flex-shrink: 0;
    margin-right: var(--gap-2);
  }
}
</style>
