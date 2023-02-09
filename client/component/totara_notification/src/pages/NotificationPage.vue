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

  @author Steve Barnett <steve.barnett@totaralearning.com>
  @module totara_notification
-->

<template>
  <Layout class="tui-notificationPage" :title="title">
    <template v-slot:content>
      <Notifications
        :can-change-delivery-channel-defaults="canChangeDeliveryChannelDefaults"
        :context-id="contextId"
        :extended-context="extendedContext"
        :preferred-editor-format="preferredEditorFormat"
      />
    </template>
  </Layout>
</template>

<script>
import Layout from 'tui/components/layouts/LayoutOneColumn';
import Notifications from 'totara_notification/components/Notifications';

export default {
  components: {
    Layout,
    Notifications,
  },

  props: {
    canChangeDeliveryChannelDefaults: {
      type: Boolean,
    },

    contextId: {
      type: [Number, String],
      required: true,
    },

    extendedContext: {
      type: Object,
      default() {
        // Just return empty object by default.
        return {};
      },
      validate(prop) {
        if (
          !('component' in prop) ||
          !('area' in prop) ||
          !('itemId' in prop)
        ) {
          return false;
        }

        if (prop.component !== '' || prop.area !== '' || prop.itemId != 0) {
          // We only accept all the fields to have value. Not either of the fields.
          return prop.component !== '' && prop.area !== '' && prop.itemId != 0;
        }

        return true;
      },
    },

    preferredEditorFormat: {
      type: [Number, String],
    },

    title: {
      type: String,
      required: true,
    },
  },
};
</script>
