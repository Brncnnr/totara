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
  @module pathway_manual
-->

<template>
  <span>
    <span
      v-if="latestRating != null"
      class="tui-bulkManualRatingLastRatingBlock__blocks"
    >
      <template v-if="showValue">
        <span v-if="latestRating.scale_value == null">
          {{ $str('rating_set_to_none', 'pathway_manual') }}
        </span>
        <span v-else>{{ latestRating.scale_value.name }}</span>
      </template>
      <span v-if="isToday">
        {{ $str('today', 'core') }}
      </span>
      <span v-else>
        {{ latestRating.date }}
      </span>
      <span v-if="!isCurrentUser && !raterPurged">{{
        $str(
          'user_fullname_wrapper',
          'pathway_manual',
          latestRating.rater.fullname
        )
      }}</span>
      <span v-else-if="raterPurged">
        {{ $str('rater_details_removed', 'pathway_manual') }}
      </span>
    </span>
    <span v-else class="tui-bulkManualRatingLastRatingBlock__neverRated">{{
      $str('never_rated', 'pathway_manual')
    }}</span>
  </span>
</template>

<script>
export default {
  props: {
    latestRating: {
      type: Object,
    },
    currentUserId: {
      required: true,
      type: Number,
    },
    showValue: {
      type: Boolean,
      default: true,
    },
  },

  computed: {
    /**
     * Is the date of the last rating today?
     * @returns {boolean}
     */
    isToday() {
      let specifiedDate = new Date(this.latestRating.date_iso8601);
      let today = new Date();
      return specifiedDate.setHours(0, 0, 0, 0) === today.setHours(0, 0, 0, 0);
    },

    /**
     * Has the user data been purged for the person who last rated?
     * @returns {boolean}
     */
    raterPurged() {
      return this.latestRating.rater == null;
    },

    /**
     * Was the current user the last person to make a rating?
     * @returns {boolean}
     */
    isCurrentUser() {
      if (this.raterPurged) {
        return false;
      }
      return parseInt(this.latestRating.rater.id) === this.currentUserId;
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "today"
    ],
    "pathway_manual": [
      "never_rated",
      "rater_details_removed",
      "rating_set_to_none",
      "user_fullname_wrapper"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-bulkManualRatingLastRatingBlock {
  @media (min-width: $tui-screen-xs) {
    &__blocks {
      & > span {
        display: block;
      }
    }

    &__neverRated {
      display: none;
    }
  }
}
</style>
