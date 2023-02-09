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
  <Dropdown v-if="availableLists && availableLists.length > 0">
    <template v-slot:trigger="{ toggle, isOpen }">
      <Button
        :aria-expanded="isOpen ? 'true' : 'false'"
        :styleclass="{ transparent: true }"
        class="tui-draggableMoveMenu__button"
        :text="$str('move_to_list', 'totara_core')"
        @click="toggle"
      />
    </template>
    <DropdownItem
      v-for="list in availableLists"
      :key="list.sourceId"
      @click="() => handleClick(list)"
    >
      {{ list.sourceName }}
    </DropdownItem>
  </Dropdown>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';

export default {
  components: {
    Dropdown,
    DropdownItem,
    Button,
  },

  props: {
    availableLists: {
      type: Array,
    },
  },

  methods: {
    handleClick(list) {
      this.$emit('move', list);
    },
  },
};
</script>

<lang-strings>
{
  "totara_core": ["move_to_list"]
}
</lang-strings>

<style lang="scss">
.tui-draggableMoveMenu {
  &__button:not(:focus):not([aria-expanded='true']) {
    @include sr-only();
  }
}
</style>
