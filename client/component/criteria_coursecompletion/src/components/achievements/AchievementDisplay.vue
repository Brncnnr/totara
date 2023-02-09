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
  @module criteria_coursecompletion
-->

<template>
  <CourseAchievementDisplay
    :achievements="achievements"
    :displayed="displayed"
  />
</template>

<script>
// Components
import CourseAchievementDisplay from 'totara_criteria/components/achievements/CourseAchievementDisplay';
// GraphQL
import AchievementsQuery from 'criteria_coursecompletion/graphql/achievements';

export default {
  components: { CourseAchievementDisplay },
  inheritAttrs: false,
  props: {
    instanceId: {
      required: true,
      type: Number,
    },
    userId: {
      required: true,
      type: Number,
    },
    displayed: Boolean,
  },

  data: function() {
    return {
      achievements: {
        items: [],
      },
    };
  },

  apollo: {
    /**
     * Fetch a criteria set for course completion
     *
     * @return {Object}
     */
    achievements: {
      query: AchievementsQuery,
      context: { batch: true },
      variables() {
        return {
          instance_id: this.instanceId,
          user_id: this.userId,
        };
      },
      update({ criteria_coursecompletion_achievements: achievements }) {
        this.$emit('loaded');
        return achievements;
      },
    },
  },
};
</script>
