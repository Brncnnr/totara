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

@author Arshad Anwer <arshad.anwer@totaralearning.com>
@module hierarchy_goal
-->

<template>
  <div v-if="isStatusChangePossible" class="tui-linkedReviewChangeStatus">
    <ChangeStatus
      :from-print="fromPrint"
      :status="data.status_change"
      :required="required"
    >
      <template v-slot:display>
        <Grid class="tui-linkedReviewChangeStatus__summary" :stack-at="700">
          <GridItem :units="4">
            <template>
              <span>
                {{
                  $str('goal_updated_by', 'hierarchy_goal', {
                    user: data.status_change.status_changer_user.fullname,
                    relationship: userRelationship,
                  })
                }}
              </span>
            </template>
          </GridItem>
          <GridItem :units="2">
            <span
              class="tui-linkedReviewChangeStatus__summary-dateAccessibleTitle"
            >
              {{ $str('a11y_goal_status_updated_date', 'hierarchy_goal') }}
            </span>
            <span>{{ data.status_change.created_at }}</span>
          </GridItem>
          <GridItem :units="6">
            <template v-if="data.status_change">
              <span class="tui-linkedReviewChangeStatus__summary-status">
                {{
                  $str(
                    'updated_goal_status',
                    'hierarchy_goal',
                    data.status_change.scale_value.name
                  )
                }}
              </span>
            </template>
          </GridItem>
        </Grid>
      </template>
      <template v-slot:form="{ titleId }">
        <Uniform
          v-if="content.can_change_status && content.scale_values"
          class="tui-linkedReviewChangeStatus__form"
          input-width="full"
          :vertical="true"
          :initial-values="initialValues"
          @submit="confirmGoalSelection"
        >
          <FormRow
            v-if="!fromPrint"
            :label="
              $str(
                'goal_status_response_subject',
                'hierarchy_goal',
                userRelationship
              )
            "
          >
            <div class="tui-linkedReviewChangeStatus__statusWrapper">
              <FormSelect
                :aria-labelledby="titleId"
                name="status"
                :options="statusOptions"
                char-length="15"
              />
              <div>
                <Button
                  :styleclass="{ primary: true, small: true }"
                  :text="$str('submit_status', 'hierarchy_goal')"
                  type="submit"
                />
              </div>
            </div>
          </FormRow>
          <FormRow
            v-else
            :label="
              $str(
                'goal_status_response_subject',
                'hierarchy_goal',
                userRelationship
              )
            "
          >
            <FormRadioGroup name="status">
              <Radio
                v-for="item in statusOptions"
                :key="item.id"
                :value="item.id"
              >
                {{ item.label }}
              </Radio>
            </FormRadioGroup>
          </FormRow>
        </Uniform>
        <div
          v-else-if="isPersonalGoal && !content.scale_values"
          class="tui-linkedReviewChangeStatus__unavailableMessage"
        >
          {{ $str('goal_scale_unavailable', 'hierarchy_goal') }}
        </div>
        <div
          v-if="!content.can_change_status && content.scale_values"
          class="tui-linkedReviewChangeStatus__answeredBy"
        >
          {{
            $str(
              'goal_status_answered_by_other',
              'hierarchy_goal',
              userRelationship
            )
          }}
        </div>
      </template>
    </ChangeStatus>
    <ConfirmationModal
      :open="modalOpen"
      :title="$str('submit_goal_title', 'hierarchy_goal')"
      :confirm-button-text="$str('submit_status', 'hierarchy_goal')"
      :loading="isSaving"
      @confirm="saveGoal"
      @cancel="cancelGoal"
    >
      <p
        v-html="
          $str('goal_confirmation_body_1', 'hierarchy_goal', {
            goal_name: content.goal.display_name,
            scale_value: selectedScaleValueName(),
            user: subjectUser.fullname,
          })
        "
      />
      <div>{{ $str('goal_confirmation_body_2', 'hierarchy_goal') }}</div>
    </ConfirmationModal>
  </div>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import ChangeStatus from 'hierarchy_goal/components/ChangeStatus';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import {
  FormRadioGroup,
  FormRow,
  FormSelect,
  Uniform,
} from 'tui/components/uniform';
import Grid from 'tui/components/grid/Grid';
import GridItem from 'tui/components/grid/GridItem';
import Radio from 'tui/components/form/Radio';
import { notify } from 'tui/notifications';
import { PERSONAL_GOAL } from '../js/constants';
// Query
import goalStatusUpdate from 'hierarchy_goal/graphql/perform_linked_goals_change_status';

export default {
  components: {
    Button,
    ChangeStatus,
    ConfirmationModal,
    FormRadioGroup,
    FormRow,
    FormSelect,
    Grid,
    GridItem,
    Radio,
    Uniform,
  },

  props: {
    content: {
      type: Object,
      required: true,
    },
    elementData: Object,
    fromPrint: Boolean,
    participantInstanceId: [String, Number],
    required: Boolean,
    sectionElementId: [String, Number],
    subjectUser: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      contentTypeSettings: this.elementData.content_type_settings,
      data: this.content,
      initialValues: {
        status: 0,
      },
      isSaving: false,
      modalOpen: false,
      selectedGoalStatusId: 0,
    };
  },

  computed: {
    /**
     * Provide the status options for the form select
     *
     * @return {Array}
     */
    statusOptions() {
      let defaultOpt = {
        id: 0,
        label: this.$str('goal_status_select', 'hierarchy_goal'),
      };

      if (!this.data.scale_values) {
        return [defaultOpt];
      }

      let options = this.data.scale_values;
      options = options.map(option => {
        return {
          id: option.id,
          label: option.name,
        };
      });
      if (!this.fromPrint) {
        options.unshift(defaultOpt);
      }
      return options;
    },

    // Get relationship of the user who updated the goal status
    userRelationship() {
      return this.contentTypeSettings.status_change_relationship_name;
    },

    isPersonalGoal() {
      return this.elementData.content_type === PERSONAL_GOAL;
    },

    isStatusChangePossible() {
      return (
        this.contentTypeSettings.enable_status_change && this.goalContentExists
      );
    },

    /**
     * Checks if the goal exists in the content property.
     * It will not exist if it has been deleted from the system after it was selected.
     *
     * @return {Boolean}
     */
    goalContentExists() {
      if (!this.content) {
        return false;
      }

      if (this.isPersonalGoal) {
        return this.content.goal ? true : false;
      } else {
        return this.content.goal && this.content.status;
      }
    },
  },

  methods: {
    confirmGoalSelection(values) {
      this.selectedGoalStatusId = values.status;

      if (this.selectedGoalStatusId === 0) {
        return;
      }
      this.modalOpen = true;
    },

    async saveGoal() {
      this.isSaving = true;
      try {
        const data = await this.$apollo.mutate({
          mutation: goalStatusUpdate,
          variables: {
            input: {
              participant_instance_id: this.participantInstanceId,
              goal_assignment_id: this.data.id,
              goal_type: this.data.goal.goal_scope,
              scale_value_id: this.selectedGoalStatusId,
              section_element_id: this.sectionElementId,
            },
          },
        });

        const result =
          data.data.hierarchy_goal_perform_linked_goals_change_status;

        if (result.already_exists) {
          this.$emit(
            'show-banner',
            this.$str('goal_exists_message', 'hierarchy_goal')
          );
        } else {
          this.data = Object.assign({}, this.data, {
            status_change: result.perform_status,
          });

          notify({
            message: this.$str('goal_status_updated', 'hierarchy_goal'),
            type: 'success',
          });
        }
      } catch (e) {
        notify({
          message: this.$str('goal_status_updated_error', 'hierarchy_goal'),
          type: 'error',
        });
      }

      this.isSaving = false;
      this.cancelGoal();
    },

    cancelGoal() {
      this.selectedGoalStatusId = 0;
      this.modalOpen = false;
    },

    selectedScaleValueName() {
      if (this.selectedGoalStatusId === 0) {
        return;
      }

      let scaleName = this.data.scale_values.find(
        v => v.id === this.selectedGoalStatusId
      ).name;
      return scaleName;
    },
  },
};
</script>

<lang-strings>
{
  "hierarchy_goal": [
    "a11y_goal_status_updated_date",
    "goal_status_updated_error",
    "goal_confirmation_body_1",
    "goal_confirmation_body_2",
    "goal_exists_message",
    "goal_updated_by",
    "goal_status_select",
    "goal_status_response_subject",
    "goal_status_answered_by_other",
    "goal_status_updated",
    "goal_scale_unavailable",
    "updated_goal_status",
    "submit_status",
    "submit_goal_title"
  ]
}
</lang-strings>

<style lang="scss">
.tui-linkedReviewChangeStatus {
  &__summary {
    &-dateAccessibleTitle {
      @include sr-only();
    }

    &-status {
      @include tui-font-heavy();
    }
  }

  &__statusWrapper {
    display: flex;

    > :first-child {
      margin-right: var(--gap-4);
    }
  }
}
</style>
