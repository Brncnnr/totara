<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Qingyang Liu <qingyang.liu@totaralearning.com>
  @module totara_oauth2
-->
<template>
  <div class="tui-oauth2ProviderAction">
    <Dropdown>
      <template v-slot:trigger="{ toggle, isOpen }">
        <MoreIcon
          :aria-expanded="isOpen ? 'true' : 'false'"
          :aria-label="$str('actions_for', 'totara_oauth2', providerName)"
          :size="300"
          @click="toggle"
        />
      </template>

      <DropdownItem
        :title="$str('delete_provider_name', 'totara_oauth2', providerName)"
        :aria-label="
          $str('delete_provider_name', 'totara_oauth2', providerName)
        "
        @click="$emit('delete-provider')"
      >
        {{ $str('delete', 'core') }}
      </DropdownItem>
    </Dropdown>
  </div>
</template>

<script>
import Dropdown from 'tui/components/dropdown/Dropdown';
import MoreIcon from 'tui/components/buttons/MoreIcon';
import DropdownItem from 'tui/components/dropdown/DropdownItem';

export default {
  components: {
    Dropdown,
    MoreIcon,
    DropdownItem,
  },
  props: {
    providerName: {
      type: String,
      required: true,
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "delete"
    ],
    "totara_oauth2": [
      "actions_for",
      "delete_provider_name"
    ]
  }
</lang-strings>
