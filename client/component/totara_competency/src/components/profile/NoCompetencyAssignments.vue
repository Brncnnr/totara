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

  @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
  @module totara_competency
-->

<template>
  <div v-if="display" class="tui-noCompetencyAssignments">
    <NotificationBanner
      type="warning"
      :message="$str('no_competencies_assigned', 'totara_competency')"
    />
    <div
      v-if="isAssignEnabled"
      class="tui-noCompetencyAssignments__search-competencies"
    >
      <ActionLink
        :href="selfAssignmentUrl"
        :text="
          isMine
            ? $str('self_assign_competencies', 'totara_competency')
            : $str('assign_competencies', 'totara_competency')
        "
        :styleclass="{ primary: true }"
      />
    </div>
  </div>
</template>

<script>
import ActionLink from 'tui/components/links/ActionLink';
import NotificationBanner from 'tui/components/notifications/NotificationBanner';

export default {
  components: {
    ActionLink,
    NotificationBanner,
  },

  props: {
    display: {
      type: Boolean,
      default: true,
    },

    selfAssignmentUrl: {
      required: true,
      type: String,
    },

    isMine: Boolean,

    isAssignEnabled: Boolean,
  },
};
</script>

<lang-strings>
{
  "totara_competency": [
    "assign_competencies",
    "self_assign_competencies",
    "no_competencies_assigned"
  ]
}
</lang-strings>

<style lang="scss">
.tui-noCompetencyAssignments {
  &__search-competencies {
    display: flex;
    flex-direction: row-reverse;
    margin-top: 35px;
  }
}
</style>
