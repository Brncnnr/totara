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
  @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
  @module totara_competency
-->

<template>
  <div v-if="competencyContentExists" class="tui-linkedReviewViewCompetency">
    <h4 class="tui-linkedReviewViewCompetency__title">
      <a
        v-if="!fromPrint && !preview && !isExternalParticipant"
        :href="competencyUrl"
        :aria-label="
          $str(
            'selected_competency',
            'totara_competency',
            content.competency.display_name
          )
        "
      >
        {{ content.competency.display_name }}
      </a>
      <template v-else>
        {{ content.competency.display_name }}
      </template>
    </h4>

    <div
      class="tui-linkedReviewViewCompetency__description"
      v-html="content.competency.description"
    />

    <div class="tui-linkedReviewViewCompetency__overview">
      <div v-if="!preview" class="tui-linkedReviewViewCompetency__timestamp">
        {{ createdAt }}
      </div>

      <div class="tui-linkedReviewViewCompetency__bar">
        <Grid :stack-at="600">
          <GridItem :units="3">
            <p class="tui-linkedReviewViewCompetency__bar-header">
              {{ $str('reason_assigned', 'totara_competency') }}
            </p>
            <div class="tui-linkedReviewViewCompetency__bar-value">
              {{ content.assignment.reason_assigned }}
            </div>
          </GridItem>

          <GridItem :units="5">
            <div
              class="tui-linkedReviewViewCompetency__bar-wrap"
              :class="'tui-linkedReviewViewCompetency__bar-wrap-' + state"
            >
              <p class="tui-linkedReviewViewCompetency__bar-header">
                {{ $str('achievement_level', 'totara_competency') }}
                <InfoIconButton
                  v-if="!fromPrint"
                  class="tui-linkedReviewViewCompetency__bar-headerHelp"
                  :is-help-for="$str('rating_scale', 'totara_competency')"
                >
                  <RatingScaleOverview :scale="scale" />
                </InfoIconButton>
              </p>
              <div class="tui-linkedReviewViewCompetency__bar-value">
                {{ content.achievement.name }}
              </div>
            </div>
          </GridItem>

          <GridItem
            :units="4"
            class="tui-linkedReviewViewCompetency__bar-status"
          >
            <ProgressTrackerNavCircleAchievement :states="progressStates" />
            <span
              class="tui-linkedReviewViewCompetency__bar-statusText"
              :class="{
                'tui-linkedReviewViewCompetency__bar-statusTextComplete':
                  state === 'complete',
              }"
            >
              {{
                content.achievement.proficient
                  ? $str('proficient', 'totara_competency')
                  : $str('not_proficient', 'totara_competency')
              }}
            </span>
          </GridItem>
        </Grid>
      </div>
    </div>
  </div>

  <div v-else class="tui-linkedReviewViewCompetencyMissing">
    {{ $str('perform_review_competency_missing', 'totara_competency') }}
  </div>
</template>

<script>
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
import InfoIconButton from 'tui/components/buttons/InfoIconButton';
import ProgressTrackerNavCircleAchievement from 'tui/components/progresstracker/ProgressTrackerNavCircleAchievement';
import RatingScaleOverview from 'totara_competency/components/RatingScaleOverview';

export default {
  components: {
    Grid,
    GridItem,
    InfoIconButton,
    ProgressTrackerNavCircleAchievement,
    RatingScaleOverview,
  },

  props: {
    content: {
      type: Object,
    },
    createdAt: String,
    fromPrint: Boolean,
    isExternalParticipant: Boolean,
    preview: Boolean,
    subjectUser: Object,
  },

  computed: {
    /**
     * Provide URL for competency
     *
     * @return {String}
     */
    competencyUrl() {
      return this.$url('/totara/competency/profile/details/index.php', {
        competency_id: this.content.competency.id,
        user_id: this.subjectUser.id,
      });
    },

    /**
     * Return scale values
     * @return {Object}
     */
    scale() {
      return { values: this.content.scale_values };
    },

    /**
     * Checks if the competency exists in the content property.
     *
     * @return {Boolean}
     */
    competencyContentExists() {
      return (
        this.content &&
        this.content.competency &&
        this.content.scale_values &&
        this.content.achievement &&
        this.content.assignment
      );
    },

    /**
     * Return proficient state (pending, complete, achieved)
     *
     * @return {String}
     */
    state() {
      if (this.content.achievement.id && this.content.achievement.proficient) {
        return 'achieved';
      } else if (this.content.achievement.id) {
        return 'complete';
      } else {
        return 'pending';
      }
    },

    /**
     * Return states for progress tracking marker
     *
     * @return {Array}
     */
    progressStates() {
      let states = [this.state];

      if (this.state !== 'complete') {
        states.push('target');
      }
      return states;
    },
  },
};
</script>

<lang-strings>
  {
    "totara_competency": [
      "achievement_level",
      "not_proficient",
      "perform_review_competency_missing",
      "proficient",
      "rating_scale",
      "reason_assigned",
      "selected_competency"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-linkedReviewViewCompetency {
  & > * + * {
    margin-top: var(--gap-4);
  }

  &__title {
    @include tui-font-heading-x-small();
    margin: 0;
  }

  &__overview {
    & > * + * {
      margin-top: var(--gap-1);
    }
  }

  &__timestamp {
    @include tui-font-body-small();
  }

  &__bar {
    display: flex;
    padding: var(--gap-4);
    background: var(--color-neutral-1);
    border: var(--border-width-thin) solid var(--color-neutral-5);

    &-status {
      display: flex;
      align-items: center;
      justify-content: flex-end;

      & > * + * {
        margin-left: var(--gap-2);
      }
    }

    &-statusText {
      margin-left: var(--gap-2);
      @include tui-font-heading-small();

      .dir-rtl & {
        margin: 0 var(--gap-2) 0 0;
      }
    }

    &-statusTextComplete {
      margin-left: var(--gap-1);

      .dir-rtl & {
        margin: 0 var(--gap-1) 0 0;
      }
    }

    &-header {
      margin: 0;
      @include tui-font-body();
    }

    &-headerHelp {
      display: inline-block;
    }

    &-value {
      @include tui-font-heavy();
    }

    &-wrap {
      padding-left: var(--gap-2);
      border-style: solid;
      border-width: 0 0 0 var(--border-width-thick);

      .dir-rtl & {
        padding-right: var(--gap-2);
        border-width: 0 var(--border-width-thick) 0 0;
      }

      &-achieved {
        border-color: var(--progresstracker-color-achieved);
      }

      &-complete {
        border-color: var(--progresstracker-color-complete);
      }

      &-pending {
        border-color: var(--progresstracker-color-pending);
      }
    }
  }

  &__ratings {
    & > * + * {
      margin-top: var(--gap-6);
    }
  }
}
</style>
