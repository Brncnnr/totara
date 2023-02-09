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
  <div class="tui-pathwayPerformRatingAchievementRater">
    <AchievementLayout>
      <!-- Learning manual rating proficiency left content -->
      <template v-slot:left>
        <div class="tui-pathwayPerformRatingAchievementRater__overview">
          <h4 class="tui-pathwayPerformRatingAchievementRater__overview-text">
            {{ $str('rating_by', 'pathway_perform_rating') }}
          </h4>
          <div
            class="tui-pathwayPerformRatingAchievementRater__overview-relationship"
          >
            {{ rating.rater_role }}
          </div>
        </div>
      </template>
      <template v-slot:right>
        <Grid :stack-at="750">
          <GridItem
            :units="3"
            class="tui-pathwayPerformRatingAchievementRater__item"
          >
            <h4 class="tui-pathwayPerformRatingAchievementRater__item-header">
              {{ $str('rater', 'pathway_perform_rating') }}
            </h4>
            <div>
              <!-- Rater avatar & name cell -->
              <MiniProfileCard
                :display="getUserDetails(rating)"
                no-border
                no-padding
              />
            </div>
          </GridItem>

          <GridItem
            :units="3"
            class="tui-pathwayPerformRatingAchievementRater__item"
          >
            <h4 class="tui-pathwayPerformRatingAchievementRater__item-header">
              {{ $str('date') }}
            </h4>
            <div>
              <!-- Rated date -->
              {{ rating.created_at }}
            </div>
          </GridItem>

          <GridItem
            :units="6"
            class="tui-pathwayPerformRatingAchievementRater__item"
          >
            <h4 class="tui-pathwayPerformRatingAchievementRater__item-header">
              {{ $str('rating', 'pathway_perform_rating') }}
            </h4>

            <!-- Rated rating -->
            <div>
              <template v-if="rating.scale_value">
                {{ rating.scale_value.name }}
              </template>
              <template v-else>
                {{ $str('rating_set_to_none', 'pathway_perform_rating') }}
              </template>
            </div>
          </GridItem>
        </Grid>
      </template>
    </AchievementLayout>
  </div>
</template>

<script>
// Components
import AchievementLayout from 'totara_competency/components/achievements/AchievementLayout';
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
import MiniProfileCard from 'tui/components/profile/MiniProfileCard';

export default {
  components: {
    AchievementLayout,
    Grid,
    GridItem,
    MiniProfileCard,
  },

  inheritAttrs: false,
  props: {
    rating: {
      required: true,
      type: Object,
    },
  },

  methods: {
    /**
     * Check if rater has been purged
     *
     * @return {Boolean}
     */
    raterPurged(rater) {
      return rater == null;
    },

    /**
     * Return user details for profile card
     *
     * @return {Object}
     */
    getUserDetails(row) {
      let rater = row.rater_user;
      // Get username
      let name = this.raterPurged(rater)
        ? this.$str('rater_details_removed', 'pathway_perform_rating')
        : rater.fullname;

      let avatar = this.raterPurged(rater)
        ? row.defaultImageurl
        : rater.profileimageurl;

      let details = {
        profile_picture_alt: null,
        profile_picture_url: avatar,
        profile_url: null,
        display_fields: [
          {
            value: name,
            associate_url: null,
          },
        ],
      };

      return details;
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "date"
    ],
    "pathway_perform_rating": [
      "rater",
      "rater_details_removed",
      "rating",
      "rating_by",
      "rating_set_to_none",
      "your_rating"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-pathwayPerformRatingAchievementRater {
  margin-top: var(--gap-2);
  padding: var(--gap-4);
  border: var(--border-width-thin) solid var(--color-neutral-5);
  border-radius: var(--border-radius-normal);

  &__overview {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    align-items: center;

    &-text {
      margin: 0;
      @include tui-font-body();
    }

    &-relationship {
      @include tui-font-heavy();
    }
  }

  &__item {
    & > * + * {
      margin-top: var(--gap-2);
    }

    &-header {
      margin: 0;
      @include tui-font-heading-label-small();
    }
  }
}

@media (min-width: $tui-screen-sm) {
  .tui-pathwayPerformRatingAchievementRater {
    &__item {
      &-header {
        @include sr-only();
      }
    }
  }
}
</style>
