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

  @author Arshad Anwer <arshad.anwer@totaralearning.com>
  @module totara_api
-->
<template>
  <div class="tui-totara_api-clientActions">
    <Dropdown>
      <template v-slot:trigger="{ toggle, isOpen }">
        <MoreIcon
          :aria-expanded="isOpen ? 'true' : 'false'"
          :aria-label="$str('actions_for', 'totara_api', clientName)"
          :size="300"
          @click="toggle"
        />
      </template>

      <DropdownItem
        :title="$str('edit_client_details_name', 'totara_api', clientName)"
        :aria-label="$str('edit_client_details_name', 'totara_api', clientName)"
        :href="
          $url('/totara/api/client/edit.php', {
            id: clientId,
          })
        "
      >
        {{ $str('edit_client_details', 'totara_api') }}
      </DropdownItem>
      <DropdownItem
        :title="$str('edit_client_settings_name', 'totara_api', clientName)"
        :aria-label="
          $str('edit_client_settings_name', 'totara_api', clientName)
        "
        :href="getEditClientSettingsPageUrl"
      >
        {{ $str('edit_client_settings', 'totara_api') }}
      </DropdownItem>
      <DropdownItem
        v-if="clientStatusEnabled"
        :title="$str('disable_client_name', 'totara_api', clientName)"
        :aria-label="$str('disable_client_name', 'totara_api', clientName)"
        @click="$emit('disable-api-client')"
      >
        {{ $str('disable_client', 'totara_api') }}
      </DropdownItem>
      <DropdownItem
        v-else
        :title="$str('enable_client_name', 'totara_api', clientName)"
        :aria-label="$str('enable_client_name', 'totara_api', clientName)"
        :disabled="tenantSuspended"
        @click="$emit('enable-api-client')"
      >
        {{ $str('enable_client', 'totara_api') }}
      </DropdownItem>
      <DropdownItem
        :title="$str('delete_client_name', 'totara_api', clientName)"
        :aria-label="$str('delete_client_name', 'totara_api', clientName)"
        @click="$emit('delete-api-client')"
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
    clientName: {
      type: String,
      required: true,
    },
    tenantId: Number,
    tenantSuspended: Boolean,
    clientStatusEnabled: {
      type: Boolean,
      required: true,
    },
    clientId: {
      type: String,
      required: true,
    },
  },

  computed: {
    getEditClientSettingsPageUrl() {
      let params = { client_id: this.clientId };
      if (this.tenantId) {
        params.tenant_id = this.tenantId;
      }
      return this.$url('/totara/api/client/settings.php', params);
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "delete"
    ],
    "totara_api": [
      "actions_for",
      "delete_client_name",
      "disable_client",
      "disable_client_name",
      "edit_client_details",
      "edit_client_details_name",
      "edit_client_settings",
      "edit_client_settings_name",
      "enable_client",
      "enable_client_name"
    ]
  }
</lang-strings>
