<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module container_workspace
-->
<template>
  <ButtonIcon
    :aria-label="buttonAriaLabel"
    :styleclass="{
      transparentNoPadding: true,
    }"
    @click.prevent="$emit('update', !muted)"
  >
    <Notification v-if="!muted" />
    <NotificationDisabled v-else />
  </ButtonIcon>
</template>

<script>
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Notification from 'tui/components/icons/Notification';
import NotificationDisabled from 'tui/components/icons/NotificationDisabled';

export default {
  components: {
    ButtonIcon,
    Notification,
    NotificationDisabled,
  },

  props: {
    muted: {
      type: Boolean,
      required: true,
    },
  },

  computed: {
    buttonAriaLabel() {
      if (this.muted) {
        return this.$str('unmute_workspace', 'container_workspace');
      }

      return this.$str('mute_workspace', 'container_workspace');
    },
  },
};
</script>

<lang-strings>
{
  "container_workspace": [
    "mute_workspace",
    "unmute_workspace"
  ]
}
</lang-strings>
