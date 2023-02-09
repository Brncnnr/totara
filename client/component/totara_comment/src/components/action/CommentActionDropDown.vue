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
  @module totara_comment
-->
<template>
  <Dropdown position="bottom-right" class="tui-commentActionDropDown">
    <template v-slot:trigger="{ toggle, isOpen }">
      <ButtonIcon
        v-if="showActions"
        :aria-expanded="isOpen ? 'true' : 'false'"
        :aria-label="$str('triggermenu', 'totara_comment')"
        :styleclass="{ transparentNoPadding: true }"
        @click.prevent="toggle"
      >
        <More size="300" />
      </ButtonIcon>
    </template>

    <DropdownItem v-if="showUpdateAction" @click="$emit('click-edit')">
      {{ $str('edit', 'core') }}
    </DropdownItem>

    <DropdownItem v-if="showDeleteAction" @click="$emit('click-delete')">
      {{ $str('delete', 'core') }}
    </DropdownItem>

    <DropdownItem
      v-if="showReportAction"
      @click="$emit('click-report-content')"
    >
      {{ $str('reportcontent', 'totara_comment') }}
    </DropdownItem>
  </Dropdown>
</template>

<script>
import Dropdown from 'tui/components/dropdown/Dropdown';
import More from 'tui/components/icons/More';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';

export default {
  components: {
    ButtonIcon,
    Dropdown,
    More,
    DropdownItem,
  },

  props: {
    showDeleteAction: {
      type: Boolean,
      default: true,
    },

    showUpdateAction: {
      type: Boolean,
      default: true,
    },

    showReportAction: {
      type: Boolean,
      default: true,
    },
  },

  computed: {
    showActions() {
      return (
        this.showDeleteAction || this.showUpdateAction || this.showReportAction
      );
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "delete",
      "edit"
    ],

    "totara_comment": [
      "triggermenu",
      "reportcontent"
    ]
  }
</lang-strings>
