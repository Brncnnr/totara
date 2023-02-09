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

  @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
  @module criteria_onactivate
-->

<template>
  <div class="tui-criteriaOnActiveAchievement">
    <AchievementLayout>
      <template v-slot:left>
        <h5 class="tui-criteriaOnActiveAchievement__title">
          {{ $str('achievement_display', 'criteria_onactivate') }}
        </h5>
      </template>
      <template v-slot:right>
        {{ $str('achieved_on', 'totara_competency', { date: dateAchieved }) }}
      </template>
    </AchievementLayout>
  </div>
</template>

<script>
import AchievementLayout from 'totara_competency/components/achievements/AchievementLayout';

export default {
  components: {
    AchievementLayout,
  },

  props: {
    assignmentId: {
      required: true,
      type: Number,
    },
    dateAchieved: {
      type: String,
    },
    instanceId: {
      required: true,
      type: Number,
    },
    userId: {
      required: true,
      type: Number,
    },
  },

  mounted() {
    this.$emit('loaded');
  },
};
</script>

<lang-strings>
{
  "criteria_onactivate": [
    "achievement_display"
  ],
  "totara_competency" : [
    "achieved_on"
  ]
}
</lang-strings>

<style lang="scss">
.tui-criteriaOnActiveAchievement {
  &__title {
    max-width: 100%;
  }
}
</style>
