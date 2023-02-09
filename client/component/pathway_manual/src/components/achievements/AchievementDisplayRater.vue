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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module pathway_manual
-->

<template>
  <div class="tui-pathwayManualAchievementRater">
    <AchievementLayout>
      <!-- Learning manual rating proficiency left content -->
      <template v-slot:left>
        <div class="tui-pathwayManualAchievementRater__overview">
          <div class="tui-pathwayManualAchievementRater__overview-text">
            {{ $str('rating_by', 'pathway_manual') }}
          </div>
          <div class="tui-pathwayManualAchievementRater__overview-role">
            {{ rater.role_display_name }}
          </div>
        </div>
      </template>
      <template v-slot:right>
        <Table
          :data="[rater]"
          :border-bottom-hidden="true"
          :border-top-hidden="true"
          class="tui-pathwayLearningPlanAchievement__list"
        >
          <template v-slot:row="{ row }">
            <template v-if="!hasRating(row)">
              <Cell size="10">
                {{ $str('no_rating_given', 'pathway_manual') }}
              </Cell>

              <!-- Add rating button cell -->
              <Cell size="2" align="end" valign="center">
                <ActionLink
                  v-if="row.role.has_role"
                  :href="getAddRatingUrl(row.role)"
                  :text="$str('add_rating', 'pathway_manual')"
                  :styleclass="{
                    primary: true,
                    small: true,
                  }"
                />
              </Cell>
            </template>

            <template v-else>
              <Cell
                size="3"
                :column-header="$str('rater', 'pathway_manual')"
                valign="center"
              >
                <div class="tui-pathwayLearningPlanAchievement__rater">
                  <!-- Rater avatar & name cell -->
                  <Avatar
                    :src="getProfilePhotoUrl(row)"
                    :alt="getProfilePhotoAlt(row)"
                    size="xsmall"
                  />
                  <div class="tui-pathwayLearningPlanAchievement__rater-name">
                    {{ getUserName(row) }}
                  </div>
                </div>
              </Cell>

              <!-- Rated date -->
              <Cell
                size="3"
                :column-header="$str('date', 'pathway_manual')"
                valign="center"
              >
                {{ row.latest_rating.date }}
              </Cell>

              <!-- Rated rating -->
              <Cell
                size="3"
                :column-header="$str('rating', 'pathway_manual')"
                valign="center"
              >
                <div class="tui-pathwayLearningPlanAchievement__rating">
                  <div class="tui-pathwayLearningPlanAchievement__rating-value">
                    <template v-if="row.latest_rating.scale_value">
                      {{ row.latest_rating.scale_value.name }}
                    </template>
                    <template v-else>
                      {{ $str('rating_set_to_none', 'pathway_manual') }}
                    </template>
                  </div>

                  <!-- Comment icon with popover (if comment provided) -->
                  <Popover v-if="hasComment(row)" :triggers="['click']">
                    <template v-slot:trigger>
                      <ButtonIcon
                        :aria-label="$str('view_comment', 'pathway_manual')"
                        :styleclass="{
                          small: true,
                          transparent: true,
                        }"
                      >
                        <CommentIcon
                          custom-class="tui-pathwayLearningPlanAchievement__rating-icon"
                        />
                      </ButtonIcon>
                    </template>
                    <h4
                      class="tui-pathwayLearningPlanAchievement__comment-header"
                    >
                      {{ $str('comment', 'pathway_manual') }}
                    </h4>
                    <div
                      class="tui-pathwayLearningPlanAchievement__comment-body"
                    >
                      {{ row.latest_rating.comment }}
                    </div>
                  </Popover>
                </div>
              </Cell>

              <!-- Add rating button cell -->
              <Cell
                size="3"
                :column-header="
                  row.role.has_role ? $str('rate', 'pathway_manual') : ''
                "
                align="end"
                valign="center"
              >
                <ActionLink
                  v-if="row.role.has_role"
                  :href="getAddRatingUrl(row.role)"
                  :text="$str('add_rating', 'pathway_manual')"
                  :styleclass="{
                    primary: true,
                    small: true,
                  }"
                />
              </Cell>
            </template>
          </template>
        </Table>
      </template>
    </AchievementLayout>
  </div>
</template>

<script>
// Components
import AchievementLayout from 'totara_competency/components/achievements/AchievementLayout';
import ActionLink from 'tui/components/links/ActionLink';
import Avatar from 'tui/components/avatar/Avatar';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Cell from 'tui/components/datatable/Cell';
import CommentIcon from 'tui/components/icons/Comment';
import Popover from 'tui/components/popover/Popover';
import Table from 'tui/components/datatable/Table';

export default {
  components: {
    AchievementLayout,
    ActionLink,
    Avatar,
    ButtonIcon,
    Cell,
    CommentIcon,
    Popover,
    Table,
  },

  inheritAttrs: false,
  props: {
    assignmentId: {
      required: true,
      type: Number,
    },
    rater: {
      required: true,
      type: Object,
    },
    userId: {
      required: true,
      type: Number,
    },
  },

  methods: {
    /**
     * Check if rating value for assessor
     *
     * @return {Boolean}
     */
    hasRating(role) {
      return role.latest_rating != null;
    },

    /**
     * Check if rater has been purged
     *
     * @return {Boolean}
     */
    raterPurged(roleRating) {
      return roleRating.latest_rating.rater == null;
    },

    /**
     * Check if assessor provided a comment
     *
     * @return {Boolean}
     */
    hasComment(roleRating) {
      return roleRating.latest_rating.comment != null;
    },

    /**
     * Provide URL for add rating page
     *
     * @return {String}
     */
    getAddRatingUrl(role) {
      return this.$url('/totara/competency/rate_competencies.php', {
        user_id: this.userId,
        role: role.name,
        assignment_id: this.assignmentId,
      });
    },

    /**
     * Provide URL for user avatar
     *
     * @return {String}
     */
    getProfilePhotoUrl(roleRating) {
      if (this.raterPurged(roleRating)) {
        return roleRating.default_profile_picture;
      }

      return roleRating.latest_rating.rater.profileimageurl;
    },

    /**
     * Provide URL for user avatar
     *
     * @return {String}
     */
    getProfilePhotoAlt(roleRating) {
      if (this.raterPurged(roleRating)) {
        return '';
      }

      return roleRating.latest_rating.rater.profileimagealt;
    },

    /**
     * Return username
     *
     * @return {String}
     */
    getUserName(roleRating) {
      if (this.raterPurged(roleRating)) {
        return this.$str('rater_details_removed', 'pathway_manual');
      }
      return roleRating.latest_rating.rater.fullname;
    },
  },
};
</script>

<lang-strings>
  {
    "pathway_manual" : [
      "add_rating",
      "date",
      "comment",
      "no_rating_given",
      "rate",
      "rater_details_removed",
      "rater",
      "rating",
      "rating_by",
      "rating_set_to_none",
      "view_comment"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-pathwayManualAchievementRater {
  margin-top: var(--gap-2);
  padding: var(--gap-4);
  border: var(--border-width-thin) solid var(--color-neutral-5);
  border-radius: 6px;

  &__overview {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    align-items: center;

    &-role {
      @include tui-font-heavy();
    }
  }
}
</style>
