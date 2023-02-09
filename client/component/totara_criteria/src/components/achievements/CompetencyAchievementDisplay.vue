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
  <div class="tui-criteriaCompetencyAchievement">
    <AchievementLayout>
      <template v-slot:left>
        <div class="tui-criteriaCompetencyAchievement__goal">
          <!-- Proficiency goal title -->
          <h5 class="tui-criteriaCompetencyAchievement__title">
            {{ criteriaHeading }}
          </h5>

          <!-- Proficiency progress circle -->
          <ProgressCircle
            :complete="criteriaComplete"
            :completed="
              criteriaComplete
                ? numberOfRequiredCompetencies
                : achievedCompetencies
            "
            :target="numberOfRequiredCompetencies"
          />
        </div>
      </template>
      <template v-slot:right>
        <Table
          :data="achievements.items"
          :expandable-rows="true"
          :no-items-text="noCompetenciesString"
        >
          <template v-slot:row="{ row, expand, expandState }">
            <!-- Competency details expand cell -->
            <ExpandCell
              :aria-label="row.competency.fullname"
              size="1"
              :expand-state="expandState"
              @click="expand()"
            />

            <!-- Competency name cell -->
            <Cell
              size="9"
              :column-header="$str('competencies', 'totara_criteria')"
            >
              {{ row.competency.fullname }}
            </Cell>

            <!-- Competency achivement level cell -->
            <Cell
              size="3"
              :class="'tui-criteriaCompetencyAchievement__level'"
              :column-header="$str('achievement_level', 'totara_criteria')"
            >
              <template v-if="row.value">
                {{ row.value.name }}
              </template>
              <template v-else>
                <span
                  class="tui-criteriaCompetencyAchievement__level-notAvailable"
                >
                  {{ $str('not_available', 'totara_criteria') }}
                </span>
              </template>
            </Cell>

            <!-- Competency completion cell -->
            <Cell
              size="3"
              :column-header="$str('completion', 'totara_criteria')"
              align="end"
            >
              <div
                v-if="row.value && row.value.proficient"
                class="tui-criteriaCompetencyAchievement__completion-complete"
              >
                <CheckIcon size="200" />
                {{ $str('complete', 'totara_criteria') }}
              </div>
              <div
                v-else
                class="tui-criteriaCompetencyAchievement__completion-notComplete"
              >
                {{ $str('not_complete', 'totara_criteria') }}
              </div>
            </Cell>
          </template>

          <!-- Competency expanded row -->
          <template v-slot:expand-content="{ row }">
            <div class="tui-criteriaCompetencyAchievement__summary">
              <h6 class="tui-criteriaCompetencyAchievement__summary-header">
                {{ row.competency.fullname }}
              </h6>
              <div
                class="tui-criteriaCompetencyAchievement__summary-body"
                v-html="row.competency.description"
              />

              <!-- Display view competency link-->
              <ActionLink
                v-if="row.assigned"
                :href="
                  $url('/totara/competency/profile/details/index.php', {
                    competency_id: row.competency.id,
                    user_id: userId,
                  })
                "
                :text="$str('view_competency', 'totara_criteria')"
                :class="'tui-criteriaCompetencyAchievement__summary-button'"
                :styleclass="{
                  primary: true,
                  small: true,
                }"
              />

              <!-- Display self assign competency button-->
              <div v-else-if="row.self_assignable">
                <Button
                  :text="
                    $str(
                      achievements.current_user
                        ? 'self_assign_competency'
                        : 'assign_competency',
                      'totara_criteria'
                    )
                  "
                  :styleclass="{
                    primary: true,
                    small: true,
                  }"
                  :class="'tui-criteriaCompetencyAchievement__summary-button'"
                  @click="showModal(row.competency)"
                />
              </div>
            </div>

            <!-- Display self assign competency modal-->
            <ConfirmationModal
              :open="modalOpen"
              :title="
                $str('confirm_assign_competency_title', 'totara_criteria')
              "
              @confirm="assignCompetency(row.competency)"
              @cancel="closeModal"
            >
              {{
                $str(
                  achievements.current_user
                    ? 'confirm_assign_competency_body_by_self'
                    : 'confirm_assign_competency_body_by_other',
                  'totara_criteria',
                  row.competency.fullname
                )
              }}
            </ConfirmationModal>
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
import Button from 'tui/components/buttons/Button';
import Cell from 'tui/components/datatable/Cell';
import CheckIcon from 'tui/components/icons/CheckSuccess';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import ExpandCell from 'tui/components/datatable/ExpandCell';
import ProgressCircle from 'totara_competency/components/achievements/ProgressCircle';
import Table from 'tui/components/datatable/Table';
import { notify } from 'tui/notifications';
// GraphQL
import CreateUserAssignmentMutation from 'totara_competency/graphql/create_user_assignments';

export default {
  components: {
    AchievementLayout,
    ActionLink,
    Button,
    Cell,
    CheckIcon,
    ConfirmationModal,
    ExpandCell,
    ProgressCircle,
    Table,
  },

  props: {
    achievements: {
      required: true,
      type: Object,
    },
    type: {
      required: true,
      type: String,
    },
    userId: {
      required: true,
      type: Number,
    },
  },

  data: function() {
    return {
      modalOpen: false,
    };
  },

  computed: {
    /**
     * Return int for number of completed competencies
     *
     * @return {Integer}
     */
    achievedCompetencies() {
      return this.achievements.items.reduce((total, current) => {
        return current.value && current.value.proficient ? (total += 1) : total;
      }, 0);
    },

    /**
     * Check if the criteria has been completed
     *
     * @return {Boolean}
     */
    criteriaComplete() {
      return (
        this.isValid &&
        this.numberOfRequiredCompetencies > 0 &&
        this.achievedCompetencies >= this.numberOfRequiredCompetencies
      );
    },

    /**
     * Return criteria header strings based on competency type
     *
     * @return {String}
     */
    criteriaHeading() {
      if (this.type === 'otherCompetency') {
        return this.$str(
          'achieve_proficiency_in_other_competencies',
          'totara_criteria'
        );
      }
      return this.$str(
        'achieve_proficiency_in_child_competencies',
        'totara_criteria'
      );
    },

    /**
     * Return no competency strings based on competency type
     *
     * @return {String}
     */
    noCompetenciesString() {
      if (this.type === 'otherCompetency') {
        return this.$str('error_no_competencies', 'criteria_othercompetency');
      }
      return this.$str('error_no_children', 'criteria_childcompetency');
    },

    /**
     * Return int for required number of competencies completed to fulfill criteria
     *
     * @return {Integer}
     */
    numberOfRequiredCompetencies() {
      if (this.achievements.aggregation_method === 1) {
        return this.achievements.items.length;
      }
      return this.achievements.required_items;
    },

    /**
     * Returns true if it is possible for the achievement path to be competed. Returns false if it is not possible
     * (e.g. an other/child competency item has a course completion not being tracked).
     *
     * @return {Boolean}
     */
    isValid() {
      return this.achievements.is_valid;
    },
  },

  methods: {
    /**
     * Trigger a mutation to assign selected competency
     *
     */
    assignCompetency(competency) {
      this.$apollo
        .mutate({
          // Query
          mutation: CreateUserAssignmentMutation,
          // Parameters
          variables: {
            competency_ids: [competency.id],
            user_id: this.userId,
          },
        })
        .then(({ data }) => {
          if (data && data.totara_competency_create_user_assignments) {
            let result = data.totara_competency_create_user_assignments;

            // Due to this being a batch api designed to tolerate partial success,
            // single assignment can silently fail, indicated by no results being returned.
            if (result.length > 0) {
              this.$emit('self-assigned');
            } else {
              this.triggerErrorNotification(
                this.$str('error_competency_assignment', 'totara_criteria')
              );
            }
          }
        })
        .catch(error => {
          console.error(error);
          this.triggerErrorNotification(
            this.$str('error_competency_assignment', 'totara_criteria')
          );
        })
        .finally(() => this.closeModal());
    },

    /**
     * Display error messages when competency assignment fails
     *
     */
    triggerErrorNotification(message) {
      notify({
        message: message,
        type: 'error',
      });
    },

    /**
     * Show assign competency modal
     *
     */
    showModal() {
      this.modalOpen = true;
    },

    /**
     * Close assign competency modal
     *
     */
    closeModal() {
      this.modalOpen = false;
    },
  },
};
</script>

<lang-strings>
  {
    "criteria_childcompetency": [
      "error_no_children"
    ],
    "criteria_othercompetency": [
      "error_no_competencies"
    ],
    "totara_criteria": [
      "achieve_proficiency_in_child_competencies",
      "achieve_proficiency_in_other_competencies",
      "assign_competency",
      "competencies",
      "complete",
      "completion",
      "confirm_assign_competency_body_by_other",
      "confirm_assign_competency_body_by_self",
      "confirm_assign_competency_title",
      "error_competency_assignment",
      "network_error",
      "not_available",
      "not_complete",
      "achievement_level",
      "self_assign_competency",
      "view_competency"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-criteriaCompetencyAchievement {
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
  .tui-criteriaCompetencyAchievement {
    &__completion {
      &-notComplete {
        @include sr-only();
      }
    }
    &__level {
      &-notAvailable {
        @include sr-only();
      }
    }
  }
}
</style>
