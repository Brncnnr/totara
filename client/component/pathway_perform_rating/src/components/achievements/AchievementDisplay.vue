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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module pathway_perform_rating
-->

<template>
  <div class="tui-pathwayPerformRatingAchievement">
    <template v-if="!ratings">
      {{ $str('no_rating_given', 'pathway_perform_rating') }}
    </template>
    <template v-else>
      <!-- Rating collapsible group -->
      <Collapsible :label="activityName" :initial-state="true">
        <AchievementDisplayRater :rating="ratings" />
      </Collapsible>
    </template>
  </div>
</template>

<script>
// Components
import AchievementDisplayRater from 'pathway_perform_rating/components/achievements/AchievementDisplayRater';
import Collapsible from 'tui/components/collapsible/Collapsible';

// GraphQL
import RatingsQuery from 'pathway_perform_rating/graphql/linked_competencies_rating';

export default {
  components: {
    AchievementDisplayRater,
    Collapsible,
  },

  inheritAttrs: false,
  props: {
    competencyId: {
      required: true,
      type: Number,
    },
    userId: {
      required: true,
      type: Number,
    },
  },

  data: function() {
    return {
      activityName: null,
      ratings: null,
    };
  },

  apollo: {
    ratings: {
      query: RatingsQuery,
      context: { batch: true },
      variables() {
        return {
          input: {
            competency_id: this.competencyId,
            user_id: this.userId,
          },
        };
      },
      update({ pathway_perform_rating_linked_competencies_rating: ratings }) {
        this.activityName =
          ratings.rating &&
          ratings.rating.activity &&
          ratings.rating.activity.name
            ? ratings.rating.activity.name
            : this.$str('activity_removed', 'pathway_perform_rating');
        this.$emit('loaded');
        return ratings.rating;
      },
    },
  },
};
</script>

<lang-strings>
  {
    "pathway_perform_rating": [
      "activity_removed",
      "no_rating_given"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-pathwayPerformRatingAchievement {
  & > * + * {
    margin-top: var(--gap-6);
  }
}
</style>
