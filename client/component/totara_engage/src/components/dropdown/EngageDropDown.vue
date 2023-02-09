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

  @author Qingyang Liu <qingyang liu@totaralearning.com>
  @module totara_engage
-->

<template>
  <Dropdown :position="position" :open="open">
    <template v-slot:trigger="{ toggle, isOpen }">
      <slot name="trigger" :toggle="toggle">
        <ButtonIcon
          :aria-expanded="isOpen ? 'true' : 'false'"
          :aria-label="$str('more', 'totara_engage')"
          :styleclass="{ small: true, transparentNoPadding: true }"
          @click="toggle"
        >
          <MoreIcon size="300" />
        </ButtonIcon>
      </slot>
    </template>

    <slot name="dropdown-items">
      <DropdownItem
        v-for="({ action, label }, i) in actions"
        :key="i"
        @click="action"
      >
        {{ label }}
      </DropdownItem>
    </slot>
  </Dropdown>
</template>

<script>
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import MoreIcon from 'tui/components/icons/More';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';

export default {
  components: {
    Dropdown,
    DropdownItem,
    MoreIcon,
    ButtonIcon,
  },

  props: {
    position: String,
    open: Boolean,
    actions: {
      type: Array,
      validator(prop) {
        let items = Array.prototype.filter.call(prop, item => {
          return !('label' in item) || !('action' in item);
        });

        return items.length === 0;
      },
    },
  },
};
</script>

<lang-strings>
  {
    "totara_engage": [
      "more"
    ]
  }
</lang-strings>
