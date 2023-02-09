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

  @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
  @module mod_perform
-->

<template>
  <Loader :loading="$apollo.loading || isLoading">
    <div class="tui-activityNotifications">
      <h3 class="tui-activityNotifications__header">
        <span class="tui-activityNotifications__title">
          {{ $str('manage_activities_tabs_notifications', 'mod_perform') }}
        </span>
      </h3>
      <Notifications :context-id="activityContextId" />
    </div>
  </Loader>
</template>

<script>
import Loader from 'tui/components/loading/Loader';
import Notifications from 'totara_notification/components/Notifications';

export default {
  components: {
    Loader,
    Notifications,
  },

  props: {
    activityContextId: {
      type: Number,
      required: true,
    },
  },
  data() {
    return {
      isLoading: false,
    };
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "manage_activities_tabs_notifications"
    ]
  }
</lang-strings>
