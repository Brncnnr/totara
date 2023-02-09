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
  @module pathway_learning_plan
-->

<template>
  <div class="tui-pathwayLearningPlanAchievement">
    <!-- String if no plans available -->
    <div v-if="!hasPlans" class="tui-pathwayLearningPlanAchievement__empty">
      {{ $str('no_available_learning_plans', 'pathway_learning_plan') }}
    </div>

    <!-- Learning plan proficiency content -->
    <div v-else class="tui-pathwayLearningPlanAchievement__content">
      <AchievementLayout>
        <!-- Learning plan proficiency left content -->
        <template v-slot:left>
          <div class="tui-pathwayLearningPlanAchievement__overview">
            <h5 class="tui-pathwayLearningPlanAchievement__title">
              {{
                $str('achievement_via_learning_plan', 'pathway_learning_plan')
              }}
            </h5>
            <template v-if="hasValue">
              <div class="tui-pathwayLearningPlanAchievement__value">
                <span class="tui-pathwayLearningPlanAchievement__value-title">
                  {{ plans.scale_value.name }}
                </span>
                {{ $str('set_on', 'pathway_learning_plan', plans.date) }}
              </div>
            </template>
            <template v-else>
              <div class="tui-pathwayLearningPlanAchievement__noValue">
                {{ $str('no_rating_set', 'pathway_learning_plan') }}
              </div>
            </template>
          </div>
        </template>

        <template v-slot:right>
          <Table
            :data="plans.learning_plans"
            :expandable-rows="true"
            class="tui-pathwayLearningPlanAchievement__list"
          >
            <template v-slot:row="{ row, expand, expandState }">
              <!-- learning plan, that can't be viewed -->
              <template v-if="!row.can_view">
                <ExpandCell :header="true" />
                <Cell size="11">
                  {{ $str('no_permission_view_plan', 'pathway_learning_plan') }}
                </Cell>
              </template>

              <template v-else>
                <!-- learning plan expand cell -->
                <ExpandCell
                  :aria-label="row.name"
                  :expand-state="expandState"
                  @click="expand()"
                />

                <!-- learning plan name cell -->
                <Cell
                  size="11"
                  :column-header="$str('name', 'pathway_learning_plan')"
                >
                  {{ row.name }}
                </Cell>
              </template>
            </template>

            <!-- Expanded row content -->
            <template v-slot:expand-content="{ row }">
              <div class="tui-pathwayLearningPlanAchievement__summary">
                <h6 class="tui-pathwayLearningPlanAchievement__summary-header">
                  {{ row.name }}
                </h6>
                <div
                  v-if="row.description"
                  class="tui-pathwayLearningPlanAchievement__summary-body"
                  v-html="row.description"
                />

                <ActionLink
                  :href="getPlanUrl(row.id)"
                  :text="$str('view_plan', 'pathway_learning_plan')"
                  :class="'tui-pathwayLearningPlanAchievement__summary-button'"
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
  </div>
</template>

<script>
// Components
import AchievementLayout from 'totara_competency/components/achievements/AchievementLayout';
import ActionLink from 'tui/components/links/ActionLink';
import Cell from 'tui/components/datatable/Cell';
import ExpandCell from 'tui/components/datatable/ExpandCell';
import Table from 'tui/components/datatable/Table';
// GraphQL
import CompetencyPlansQuery from 'pathway_learning_plan/graphql/competency_plans';

export default {
  components: {
    AchievementLayout,
    ActionLink,
    Cell,
    ExpandCell,
    Table,
  },

  inheritAttrs: false,

  props: {
    assignmentId: {
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
      plans: [],
    };
  },

  apollo: {
    plans: {
      query: CompetencyPlansQuery,
      context: { batch: true },
      variables() {
        return {
          assignment_id: this.assignmentId,
          user_id: this.userId,
        };
      },
      update({ pathway_learning_plan_competency_plans: plans }) {
        this.$emit('loaded');
        return plans;
      },
    },
  },

  computed: {
    /**
     * Check if data contains learning plan
     *
     * @return {Boolean}
     */
    hasPlans() {
      return this.plans.learning_plans;
    },

    /**
     * Check if a scale value has been set
     *
     * @return {Boolean}
     */
    hasValue() {
      return this.hasPlans && this.plans.scale_value != null;
    },
  },

  methods: {
    /**
     * Return URL for plan
     *
     * @param {Integer} planId
     * @return {String}
     */
    getPlanUrl(planId) {
      return this.$url('/totara/plan/component.php', {
        c: 'competency',
        id: planId,
      });
    },
  },
};
</script>

<lang-strings>
  {
    "pathway_learning_plan" : [
      "achievement_via_learning_plan",
      "name",
      "no_available_learning_plans",
      "no_rating_set",
      "no_permission_view_plan",
      "set_on",
      "view_plan",
      "work_towards_level"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-pathwayLearningPlanAchievement {
  &__empty {
    font-size: var(--font-size-16);
    @include tui-font-hint();
  }

  &__comment {
    &-header {
      margin: 0;
      @include tui-font-heading-x-small();
    }

    &-body {
      margin-top: var(--gap-2);
    }
  }

  &__overview {
    max-width: 100%;
  }

  &__summary {
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

  &__title {
    margin: 0;
    @include tui-font-heading-x-small();
    text-align: center;
  }

  &__noValue {
    margin-top: var(--gap-4);
    @include tui-font-hint();
  }

  &__rater {
    display: flex;

    &-name {
      margin: auto 0 auto var(--gap-2);
    }
  }

  &__rating {
    display: flex;

    &-icon {
      position: relative;
      top: -1px;
    }

    &-value {
      margin-right: var(--gap-1);
    }
  }

  &__value {
    margin-top: var(--gap-4);

    &-title {
      @include tui-font-heavy();
    }
  }

  &__content {
    padding: var(--gap-4);
    border: var(--border-width-thin) solid var(--color-neutral-5);
    border-radius: 6px;
  }
}
</style>
