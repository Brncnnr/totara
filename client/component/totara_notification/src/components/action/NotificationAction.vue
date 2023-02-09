<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module totara_notification
-->
<template>
  <div class="tui-notificationAction">
    <Dropdown>
      <template v-slot:trigger="{ toggle, isOpen }">
        <ButtonIcon
          :aria-expanded="isOpen ? 'true' : 'false'"
          :aria-label="
            $str('actions_for', 'totara_notification', preferenceTitle)
          "
          :styleclass="{ small: true, transparentNoPadding: true }"
          @click="toggle"
        >
          <MoreIcon :size="300" />
        </ButtonIcon>
      </template>

      <DropdownItem
        :title="
          $str('edit_notification_name', 'totara_notification', preferenceTitle)
        "
        :aria-label="
          $str('edit_notification_name', 'totara_notification', preferenceTitle)
        "
        @click="$emit('edit-notification')"
      >
        <!-- Edit the notification -->
        {{ $str('edit', 'core') }}
      </DropdownItem>
      <DropdownItem
        v-if="isDeletable"
        :title="
          $str(
            'delete_notification_name',
            'totara_notification',
            preferenceTitle
          )
        "
        :aria-label="
          $str(
            'delete_notification_name',
            'totara_notification',
            preferenceTitle
          )
        "
        @click="$emit('delete-notification')"
      >
        <!-- Delete the notification -->
        {{ $str('delete', 'core') }}
      </DropdownItem>
    </Dropdown>
  </div>
</template>

<script>
import Dropdown from 'tui/components/dropdown/Dropdown';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import MoreIcon from 'tui/components/buttons/MoreIcon';
import DropdownItem from 'tui/components/dropdown/DropdownItem';

export default {
  components: {
    Dropdown,
    ButtonIcon,
    MoreIcon,
    DropdownItem,
  },
  props: {
    preferenceTitle: {
      type: String,
      required: true,
    },
    isDeletable: {
      type: Boolean,
      required: true,
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "edit",
      "delete"
    ],
    "totara_notification": [
      "edit_notification_name",
      "actions_for",
      "delete_notification_name"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-notificationAction {
  display: flex;
  align-items: flex-start;
}
</style>
