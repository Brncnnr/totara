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
  @module totara_criteria
-->

<template>
  <div class="tui-criteriaCourseAchievement">
    <!-- Criteria overview & course list -->
    <AchievementLayout>
      <template v-slot:left>
        <div class="tui-criteriaCourseAchievement__goal">
          <!-- Proficiency goal title -->
          <h4 class="tui-criteriaCourseAchievement__title">
            {{ $str('complete_courses', 'totara_criteria') }}
          </h4>

          <!-- Proficiency progress circle -->
          <ProgressCircle
            :complete="criteriaFulfilled"
            :completed="
              completedNumberOfCourses >= targetNumberOfCourses
                ? targetNumberOfCourses
                : completedNumberOfCourses
            "
            :target="targetNumberOfCourses"
          />
        </div>
      </template>

      <template v-slot:right>
        <Table
          :data="achievements.items"
          :expandable-rows="true"
          :no-items-text="$str('no_courses', 'totara_criteria')"
        >
          <template v-slot:row="{ row, expand, expandState }">
            <!-- Course details expand cell -->
            <ExpandCell
              :aria-label="getCourseName(row)"
              :expand-state="expandState"
              @click="expand()"
            />

            <!-- Course name cell -->
            <Cell size="9" :column-header="$str('courses', 'totara_criteria')">
              {{ getCourseName(row) }}
            </Cell>

            <!-- Course progress cell -->
            <Cell
              size="3"
              :class="'tui-criteriaCourseAchievement__progress'"
              :column-header="$str('progress', 'totara_criteria')"
            >
              <!-- If the user started with this course there should be progress otherwise just print the summary -->
              <div
                v-if="hasProgress(row)"
                class="tui-criteriaCourseAchievement__progress-bar"
              >
                <Progress v-if="displayed" :value="row.course.progress" />
              </div>
              <div v-else class="tui-criteriaCourseAchievement__progress-empty">
                {{ getProgressSummary(row) }}
              </div>
            </Cell>

            <!-- Course completion cell -->
            <Cell
              size="3"
              :column-header="$str('completion', 'totara_criteria')"
              align="end"
            >
              <div
                v-if="isComplete(row)"
                class="tui-criteriaCourseAchievement__completion-complete"
              >
                <CheckIcon size="200" />
                {{ $str('complete', 'totara_criteria') }}
              </div>
              <div
                v-else
                class="tui-criteriaCourseAchievement__completion-notComplete"
              >
                {{ $str('not_complete', 'totara_criteria') }}
              </div>
            </Cell>
          </template>

          <template v-slot:expand-content="{ row }">
            <div class="tui-criteriaCourseAchievement__summary">
              <h6 class="tui-criteriaCourseAchievement__summary-header">
                {{ row.course.fullname }}
              </h6>
              <div
                class="tui-criteriaCourseAchievement__summary-body"
                v-html="row.course.description"
              />

              <ActionLink
                :href="row.course.url_view"
                :text="$str('go_to_course', 'totara_criteria')"
                :class="'tui-criteriaCourseAchievement__summary-button'"
                :styleclass="{
                  primary: true,
                  small: true,
                }"
              />
            </div>
          </template>
        </Table>
      </template>
    </AchievementLayout>
  </div>
</template>

<script>
import AchievementLayout from 'totara_competency/components/achievements/AchievementLayout';
import ActionLink from 'tui/components/links/ActionLink';
import Cell from 'tui/components/datatable/Cell';
import CheckIcon from 'tui/components/icons/CheckSuccess';
import ExpandCell from 'tui/components/datatable/ExpandCell';
import Progress from 'tui/components/progress/Progress';
import ProgressCircle from 'totara_competency/components/achievements/ProgressCircle';
import Table from 'tui/components/datatable/Table';

export default {
  components: {
    AchievementLayout,
    ActionLink,
    Cell,
    CheckIcon,
    ExpandCell,
    Progress,
    ProgressCircle,
    Table,
  },

  props: {
    achievements: {
      required: true,
      type: Object,
    },
    displayed: Boolean,
  },

  computed: {
    /**
     * Return bool for criteria fulfilled
     *
     * @return {Boolean}
     */
    criteriaFulfilled() {
      return (
        this.isValid &&
        this.targetNumberOfCourses > 0 &&
        this.completedNumberOfCourses >= this.targetNumberOfCourses
      );
    },

    /**
     * Return int for number of courses
     *
     * @return {Integer}
     */
    numberOfCourses() {
      return this.achievements.items ? this.achievements.items.length : 0;
    },

    /**
     * Return int for number of courses completed
     *
     * @return {Integer}
     */
    completedNumberOfCourses() {
      let complete = 0;

      if (!this.numberOfCourses) {
        return complete;
      }

      this.achievements.items.forEach(item => {
        if (item.course && item.course.progress === 100) {
          complete++;
        }
      });

      return complete;
    },

    /**
     * Return int for required number of courses completed to fulfil criteria
     *
     * @return {Integer}
     */
    targetNumberOfCourses() {
      // If aggregation_method is set to achieve ALL courses
      if (this.achievements.aggregation_method === 1) {
        return this.numberOfCourses;
      }
      return this.achievements.required_items;
    },

    /**
     * Returns true if it is possible for the achievement path to be competed. Returns false if it is not possible (e.g. course completion not being tracked).
     *
     * @return {Boolean}
     */
    isValid() {
      return this.achievements.is_valid;
    },
  },

  methods: {
    /**
     * Return course name or unavailable to user string
     *
     * @return {String}
     */
    getCourseName(row) {
      return row.course
        ? row.course.fullname
        : this.$str('hidden_course', 'totara_criteria');
    },

    /**
     * Return bool based on progress data
     *
     * @return {Boolean}
     */
    hasProgress(row) {
      return row.course && row.course.progress > 0;
    },

    /**
     * Return progress summary.
     *
     * @param row
     * @return {String}
     */
    getProgressSummary(row) {
      return row.course && row.course.progress_summary
        ? row.course.progress_summary
        : this.$str('not_available', 'totara_criteria');
    },

    /**
     * Return bool based on course completion
     *
     * @return {Boolean}
     */
    isComplete(row) {
      return row.course && row.course.progress === 100;
    },
  },
};
</script>

<lang-strings>
  {
    "totara_criteria": [
      "complete",
      "completion",
      "complete_courses",
      "courses",
      "go_to_course",
      "hidden_course",
      "no_courses",
      "not_available",
      "not_complete",
      "progress"
    ]
  }

</lang-strings>

<style lang="scss">
.tui-criteriaCourseAchievement {
  &__title {
    margin: 0;
    text-align: center;
    hyphens: manual;
    @include tui-font-heading-x-small();
  }

  &__goal {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    max-width: 100%;
  }

  &__progress {
    margin: auto 0;

    &-bar {
      width: 100%;
    }
  }

  &__summary {
    padding: var(--gap-2) var(--gap-2) 0;

    &-header {
      margin: 0;
      @include tui-font-heading-x-small();
    }

    &-body {
      margin-top: var(--gap-4);
    }

    &-button {
      margin-top: var(--gap-4);
    }
  }
}

@media (min-width: $tui-screen-xs) {
  .tui-criteriaCourseAchievement {
    &__completion {
      &-notComplete {
        @include sr-only();
      }
    }
  }
}
</style>
