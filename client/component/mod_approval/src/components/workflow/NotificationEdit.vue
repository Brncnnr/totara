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

  @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
  @module mod_approval
-->

<template>
  <!-- TODO: TL-31662 override NotificationPage heading size to 20px to match design -->
  <NotificationPage
    v-if="$selectors.getActiveStage($context)"
    :context-id="$selectors.getContextId($context)"
    :extended-context="$selectors.getActiveExtendedContext($context)"
    :title="
      $str(
        'notifications_title',
        'mod_approval',
        $selectors.getActiveStageNumber($context)
      )
    "
  />
</template>

<script>
import NotificationPage from 'totara_notification/pages/NotificationPage';
import { MOD_APPROVAL__WORKFLOW_EDIT } from 'mod_approval/constants';

export default {
  components: { NotificationPage },
  xState: { machineId: MOD_APPROVAL__WORKFLOW_EDIT },
};
</script>

<lang-strings>
{
  "mod_approval": [
    "notifications_title"
  ]
}
</lang-strings>
