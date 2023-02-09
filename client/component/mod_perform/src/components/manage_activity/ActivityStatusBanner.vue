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

  @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
  @module mod_perform
-->

<template>
  <ActionCard :has-shadow="true">
    <template v-slot:card-body>
      <!-- Draft Message -->
      <template v-if="state === ACTIVITY_STATUS_DRAFT">
        <span v-html="$str('activity_status_banner_draft', 'mod_perform')" />
        <HelpIcon
          class="tui-activityStatusBanner__helpIcon"
          :icon-label="$str('activity_status_banner_help_title', 'mod_perform')"
          :desc-id="$id('activation-help')"
        >
          <p>{{ $str('activity_status_banner_help_intro', 'mod_perform') }}</p>
          <ul>
            <li>
              {{ $str('activation_criteria_assignments', 'mod_perform') }}
            </li>
            <li>{{ $str('activation_criteria_elements', 'mod_perform') }}</li>
            <li>
              {{ $str('activation_criteria_relationships', 'mod_perform') }}
            </li>
            <li>{{ $str('activation_criteria_schedule', 'mod_perform') }}</li>
          </ul>
        </HelpIcon>
      </template>

      <!-- Active Message -->
      <span
        v-else-if="state === ACTIVITY_STATUS_ACTIVE"
        v-html="$str('activity_status_banner_active', 'mod_perform')"
      />
    </template>

    <template v-if="activity.can_potentially_activate" v-slot:card-action>
      <Button
        :text="$str('activity_action_activate', 'mod_perform')"
        :disabled="disabled"
        @click="$emit('activate')"
      />
    </template>
  </ActionCard>
</template>

<script>
import ActionCard from 'tui/components/card/ActionCard';
import Button from 'tui/components/buttons/Button';
import HelpIcon from 'tui/components/form/HelpIcon';
import {
  ACTIVITY_STATUS_ACTIVE,
  ACTIVITY_STATUS_DRAFT,
} from 'mod_perform/constants';

export default {
  components: {
    ActionCard,
    Button,
    HelpIcon,
  },

  props: {
    activity: {
      required: true,
      type: Object,
    },
    disabled: Boolean,
  },

  data() {
    return {
      ACTIVITY_STATUS_ACTIVE,
      ACTIVITY_STATUS_DRAFT,
    };
  },

  computed: {
    /**
     * State name.
     * @returns {String}
     */
    state() {
      return this.activity.state_details.name;
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "activation_criteria_assignments",
      "activation_criteria_elements",
      "activation_criteria_relationships",
      "activation_criteria_schedule",
      "activity_action_activate",
      "activity_status_banner_active",
      "activity_status_banner_draft",
      "activity_status_banner_help_intro",
      "activity_status_banner_help_title"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-activityStatusBanner__helpIcon {
  margin-left: var(--gap-2);
}
</style>
