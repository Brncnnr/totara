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
  <div class="tui-notifiableEventAction">
    <!-- Drop down button -->
    <Dropdown>
      <template v-slot:trigger="{ toggle, isOpen }">
        <MoreIcon
          :aria-expanded="isOpen ? 'true' : 'false'"
          :aria-label="
            $str('actions_for_event', 'totara_notification', resolverName)
          "
          :no-padding="true"
          :size="300"
          @click="toggle"
        />
      </template>

      <DropdownItem
        v-if="showCreateNotificationOption && canManage"
        :aria-label="
          $str(
            'create_notification_for_event',
            'totara_notification',
            resolverName
          )
        "
        @click="$emit('create-custom-notification')"
      >
        {{ $str('create_notification', 'totara_notification') }}
      </DropdownItem>
      <DropdownItem
        v-if="showDeliveryPreferenceOption && canManage"
        :aria-label="
          $str(
            'delivery_preferences_for_event',
            'totara_notification',
            resolverName
          )
        "
        @click="$emit('update-delivery-preferences')"
      >
        {{ $str('edit_delivery_preferences', 'totara_notification') }}
      </DropdownItem>
      <DropdownItem
        v-if="canAudit"
        :href="
          $url('/totara/notification/notification_event_log.php', auditParams)
        "
      >
        {{ $str('viewnotificationlogs', 'totara_notification') }}
      </DropdownItem>
    </Dropdown>
  </div>
</template>

<script>
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import MoreIcon from 'tui/components/buttons/MoreIcon';

export default {
  components: {
    DropdownItem,
    Dropdown,
    MoreIcon,
  },

  props: {
    resolverName: {
      type: String,
      required: true,
    },

    showDeliveryPreferenceOption: Boolean,
    showCreateNotificationOption: {
      type: Boolean,
      default: true,
    },

    canAudit: Boolean,
    canManage: Boolean,
    extendedContext: Object,
    contextId: Number,
    resolverClassName: String,
  },

  computed: {
    /**
     * creates the audit parameters
     *
     * @returns {Object} The parameters required for the audit page
     */
    auditParams() {
      let result = { context_id: this.contextId };

      if (
        this.extendedContext &&
        this.extendedContext.component &&
        this.extendedContext.area &&
        this.extendedContext.itemId
      ) {
        result.component = this.extendedContext.component;
        result.area = this.extendedContext.area;
        result.item_id = this.extendedContext.itemId;
      }

      if (this.resolverClassName) {
        result.resolver_class_name = this.resolverClassName;
      }

      return result;
    },
  },
};
</script>

<lang-strings>
  {
    "totara_notification": [
      "actions_for_event",
      "create_notification",
      "create_notification_for_event",
      "edit_delivery_preferences",
      "delivery_preferences_for_event",
      "viewnotificationlogs"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-notifiableEventAction {
  display: flex;
  align-items: center;
}
</style>
