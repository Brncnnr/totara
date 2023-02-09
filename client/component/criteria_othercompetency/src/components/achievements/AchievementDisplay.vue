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

  @author Marco Song <marco.song@totaralearning.com>
  @module criteria_othercompetency
-->

<template>
  <CompetencyAchievementDisplay
    type="otherCompetency"
    :achievements="achievements"
    :user-id="userId"
    @self-assigned="$apollo.queries.achievements.refetch()"
  />
</template>

<script>
// Components
import CompetencyAchievementDisplay from 'totara_criteria/components/achievements/CompetencyAchievementDisplay';
// GraphQL
import AchievementsQuery from 'criteria_othercompetency/graphql/achievements';

export default {
  components: { CompetencyAchievementDisplay },

  props: {
    assignmentId: {
      required: true,
      type: Number,
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

  data() {
    return {
      achievements: {
        items: [],
      },
    };
  },

  apollo: {
    /**
     * Fetch a criteria set for other competency completion
     *
     * @return {Object}
     */
    achievements: {
      query: AchievementsQuery,
      context: { batch: true },
      variables() {
        return {
          assignment_id: this.assignmentId,
          instance_id: this.instanceId,
          user_id: this.userId,
        };
      },
      update({ criteria_othercompetency_achievements: achievements }) {
        this.$emit('loaded');
        return achievements;
      },
    },
  },
};
</script>
