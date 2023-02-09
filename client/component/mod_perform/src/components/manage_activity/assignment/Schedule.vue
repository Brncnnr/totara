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

  @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module mod_perform
-->
<template>
  <div class="tui-performAssignmentSchedule">
    <h3 class="tui-performAssignmentSchedule__heading">
      {{ $str('activity_instance_creation_heading', 'mod_perform') }}
    </h3>

    <Toggles v-model="toggleValues" :activity="activity" />

    <Uniform
      ref="form"
      v-slot="{ reset }"
      class="tui-performAssignmentSchedule__form"
      :initial-values="initialValues"
      input-width="full"
      @submit="showConfirmationModal"
      @change="onChange"
    >
      <CreationRange
        class="tui-performAssignmentSchedule__form-section"
        :is-open="toggleValues.scheduleIsOpen"
        :is-fixed="toggleValues.scheduleIsFixed"
        :dynamic-date-sources="dynamicDateSources"
        :dynamic-date-setting-component="dynamicDateSettingComponent"
      />

      <FrequencySettings
        class="tui-performAssignmentSchedule__form-section"
        :is-open="toggleValues.scheduleIsOpen"
        :is-repeating="toggleValues.repeatingIsEnabled"
      />

      <DueDateSettings
        class="tui-performAssignmentSchedule__form-section"
        :close-on-due-date="activity.settings.close_on_due_date"
        :schedule-is-limited-fixed="dueDateSupportsFixed"
      />

      <FormScope path="additionalSettings">
        <JobAssignment
          class="tui-performAssignmentSchedule__form-section"
          :dynamic-source-name="dynamicDateSourceName"
          :uses-job-based-dynamic-source="isUsingJobBasedDynamicSource"
        />
      </FormScope>

      <ButtonGroup class="tui-performAssignmentSchedule__form-buttons">
        <Button
          :disabled="isSaving"
          :styleclass="{ primary: 'true' }"
          :text="$str('schedule_save_changes', 'mod_perform')"
          :formnovalidate="true"
          type="submit"
        />
        <Button
          :disabled="isSaving"
          :text="$str('cancel', 'core')"
          @click="reset"
        />
      </ButtonGroup>
    </Uniform>

    <ConfirmationModal
      :title="$str('schedule_confirm_title', 'mod_perform')"
      :confirm-button-text="$str('modal_confirm', 'mod_perform')"
      :open="confirmationModalOpen"
      @confirm="trySave"
      @cancel="hideConfirmationModal"
    >
      <p>
        {{
          $str(
            isActive
              ? 'schedule_confirm_title_active'
              : 'schedule_confirm_title_draft',
            'mod_perform'
          )
        }}
      </p>
    </ConfirmationModal>
  </div>
</template>

<script>
// Imports
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import CreationRange from 'mod_perform/components/manage_activity/assignment/schedule/AssignmentScheduleCreationRange';
import DueDateSettings from 'mod_perform/components/manage_activity/assignment/schedule/AssignmentScheduleDueDate';
import FrequencySettings from 'mod_perform/components/manage_activity/assignment/schedule/AssignmentScheduleFrequencySettings';
import JobAssignment from 'mod_perform/components/manage_activity/assignment/schedule/AssignmentScheduleJobAssignment';
import Toggles from 'mod_perform/components/manage_activity/assignment/schedule/AssignmentScheduleToggles';
import { FormScope, Uniform } from 'tui/components/uniform';

// Util
import { notify } from 'tui/notifications';
import {
  ACTIVITY_STATUS_ACTIVE,
  DATE_RESOLVER_JOB_BASED,
  RELATIVE_DATE_DIRECTION_AFTER,
  RELATIVE_DATE_DIRECTION_BEFORE,
  RELATIVE_DATE_UNIT_DAY,
  RELATIVE_DATE_UNIT_WEEK,
  SCHEDULE_REPEATING_TRIGGER_MIN_TIME,
  SCHEDULE_REPEATING_TRIGGER_TIME_SINCE,
  SCHEDULE_REPEATING_TRIGGER_TYPE_CREATION,
  SCHEDULE_REPEATING_TYPE_AFTER_CREATION,
} from 'mod_perform/constants';

// graphQL
import UpdateTrackScheduleMutation from 'mod_perform/graphql/update_track_schedule';

export default {
  components: {
    Button,
    ButtonGroup,
    ConfirmationModal,
    CreationRange,
    DueDateSettings,
    FormScope,
    FrequencySettings,
    JobAssignment,
    Toggles,
    Uniform,
  },

  props: {
    /**
     * This property had been deprecated, it is no longer used.
     * Use activity.id instead.
     * @deprecated Since Totara 16
     */
    activityId: {
      type: Number,
      required: false,
    },
    activity: {
      type: Object,
      required: true,
    },
    activityState: {
      type: String,
      required: true,
    },
    defaultFixedDate: {
      type: Object,
    },
    dynamicDateSources: {
      type: Array,
      required: true,
    },
    track: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      confirmationModalOpen: false,
      dueDateSupportsFixed:
        !this.track.schedule_is_open && this.track.schedule_is_fixed,
      dynamicDateSettingComponent: {
        name: this.getDynamicCustomSettingComponent(),
        data: this.getDynamicCustomSettingData(this.track),
        configData: this.getDynamicCustomSettingConfig(),
      },
      dynamicDateSourceName: this.getDynamicDateSourceName(),
      formValuesToSave: null,
      hasMounted: false,
      initialValues: this.getInitialValues(this.track),
      isSaving: false,
      isUsingJobBasedDynamicSource: this.isDynamicDateSourceJobBased(),
      toggleValues: {
        repeatingIsEnabled: this.track.repeating_is_enabled,
        scheduleIsOpen: this.track.schedule_is_open,
        scheduleIsFixed: this.track.schedule_is_fixed,
      },
    };
  },

  computed: {
    isActive() {
      return this.activityState == ACTIVITY_STATUS_ACTIVE;
    },
  },

  watch: {
    toggleValues(values) {
      // Ensure due date form fields are in a valid state
      // if limited and fixed enabled allow a fixed due date otherwise it's relative
      if (!values.scheduleIsOpen && values.scheduleIsFixed) {
        this.dueDateSupportsFixed = true;
      } else {
        this.dueDateSupportsFixed = false;
        this.$refs.form.update(['dueDate', 'dueDateType'], 'relative');
      }

      this.hasUnsavedChanges(true);
    },
  },

  mounted() {
    this.hasMounted = true;
  },

  methods: {
    /**
     * React to changes in the form.
     */
    onChange(values) {
      if (!this.hasMounted) {
        return;
      }

      if (values.scheduleDynamic.dynamic_source) {
        const selectedDynamicSource = this.getSelectedDynamicDateSourceFromValues(
          values
        );

        this.dynamicDateSettingComponent.name =
          selectedDynamicSource.custom_setting_component;

        this.dynamicDateSettingComponent.data = JSON.parse(
          selectedDynamicSource.custom_data
        );

        this.isUsingJobBasedDynamicSource =
          !this.toggleValues.scheduleIsFixed &&
          this.isDynamicDateSourceJobBased(selectedDynamicSource);
        this.dynamicDateSourceName = this.getDynamicDateSourceName(
          selectedDynamicSource
        );
      }
      this.hasUnsavedChanges(true);
    },

    /**
     * Generate the initial form values
     *
     * @param {Object} track
     * @return {Object}
     */
    getInitialValues(track) {
      // Due date default settings
      let dueDate = {
        dueDateEnabled: track.due_date_is_enabled,
        dueDateOffset: {
          relative: {
            value: track.due_date_offset ? track.due_date_offset.count : 14,
            range: track.due_date_offset
              ? track.due_date_offset.unit
              : RELATIVE_DATE_UNIT_DAY,
          },
        },
        dueDateType: track.due_date_is_fixed ? 'fixed' : 'relative',
        fixedDueDate: track.due_date_fixed || this.defaultFixedDate,
      };

      // Get a string key representing a resolver and option key pair.
      let dynamicSource = track.schedule_dynamic_source
        ? track.schedule_dynamic_source
        : this.dynamicDateSources[0];

      let scheduleDynamic = {
        dynamicCustomSettings: this.getDynamicCustomSettingData(track),
        dynamic_source:
          dynamicSource.resolver_class_name + '--' + dynamicSource.option_key,
        fromDirection: RELATIVE_DATE_DIRECTION_BEFORE,
        fromOffset: {
          after: {
            range: RELATIVE_DATE_UNIT_DAY,
            value: 1,
          },
          before: {
            range: RELATIVE_DATE_UNIT_DAY,
            value: 1,
          },
        },
        toDirection: RELATIVE_DATE_DIRECTION_BEFORE,
        toOffset: {
          after: {
            range: RELATIVE_DATE_UNIT_DAY,
            value: 1,
          },
          before: {
            range: RELATIVE_DATE_UNIT_DAY,
            value: 1,
          },
        },
        useAnniversary: track.schedule_use_anniversary,
      };

      // Schedule settings before & after dates
      if (track.schedule_dynamic_from) {
        const fromDirection = track.schedule_dynamic_from.direction;
        if (track.schedule_dynamic_from.count === 0) {
          scheduleDynamic.fromDirection = false;
        } else if (fromDirection === RELATIVE_DATE_DIRECTION_BEFORE) {
          // Direction
          scheduleDynamic.fromDirection = fromDirection;
          // Before value
          scheduleDynamic.fromOffset.before.value =
            track.schedule_dynamic_from.count;
          // Before range
          scheduleDynamic.fromOffset.before.range =
            track.schedule_dynamic_from.unit;
        } else if (fromDirection === RELATIVE_DATE_DIRECTION_AFTER) {
          // Direction
          scheduleDynamic.fromDirection = fromDirection;
          // After value
          scheduleDynamic.fromOffset.after.value =
            track.schedule_dynamic_from.count;
          // After range
          scheduleDynamic.fromOffset.after.range =
            track.schedule_dynamic_from.unit;
        }
      }

      if (track.schedule_dynamic_to) {
        const toDirection = track.schedule_dynamic_to.direction;

        if (track.schedule_dynamic_to.count === 0) {
          scheduleDynamic.toDirection = false;
        } else if (toDirection === RELATIVE_DATE_DIRECTION_BEFORE) {
          // Direction
          scheduleDynamic.toDirection = toDirection;
          // Before value
          scheduleDynamic.toOffset.before.value =
            track.schedule_dynamic_to.count;
          // Before range
          scheduleDynamic.toOffset.before.range =
            track.schedule_dynamic_to.unit;
        } else if (toDirection === RELATIVE_DATE_DIRECTION_AFTER) {
          // Direction
          scheduleDynamic.toDirection = toDirection;
          // After value
          scheduleDynamic.toOffset.after.value =
            track.schedule_dynamic_to.count;
          // After range
          scheduleDynamic.toOffset.after.range = track.schedule_dynamic_to.unit;
        }
      }

      let repeatingValues = {
        repeatingIsLimited: false,
        repeatingLimit: 3,
        repeatingOffset: {
          [SCHEDULE_REPEATING_TRIGGER_MIN_TIME]: {
            range: RELATIVE_DATE_UNIT_WEEK,
            value: 4,
          },
          [SCHEDULE_REPEATING_TRIGGER_TIME_SINCE]: {
            range: RELATIVE_DATE_UNIT_WEEK,
            value: 4,
          },
        },
        repeatingType: SCHEDULE_REPEATING_TYPE_AFTER_CREATION,
        repeatingTrigger: SCHEDULE_REPEATING_TRIGGER_TIME_SINCE,
        repeatingTriggerType: SCHEDULE_REPEATING_TRIGGER_TYPE_CREATION,
      };

      // Set existing repeating type, values & limit
      if (track.repeating_type) {
        repeatingValues.repeatingType = track.repeating_type;
      }
      if (track.repeating_trigger_type) {
        repeatingValues.repeatingTriggerType = track.repeating_trigger_type;
      }

      if (track.repeating_trigger_interval) {
        const repeatTriggerInterval = track.repeating_trigger_interval;
        repeatingValues.repeatingTrigger = repeatTriggerInterval;

        repeatingValues.repeatingOffset[repeatTriggerInterval].value =
          track.repeating_offset.count;
        repeatingValues.repeatingOffset[repeatTriggerInterval].range =
          track.repeating_offset.unit;
      }

      if (track.repeating_is_limited) {
        repeatingValues.repeatingIsLimited = track.repeating_is_limited;
      }

      if (track.repeating_limit) {
        repeatingValues.repeatingLimit = track.repeating_limit;
      }

      return {
        additionalSettings: {
          subjectInstanceGeneration: track.subject_instance_generation,
        },
        // Due date initial settings
        dueDate: dueDate,

        // Repeating initial settings
        repeatingValues: repeatingValues,

        // Creation range initial settings
        scheduleDynamic: scheduleDynamic,
        scheduleFixed: {
          from: track.schedule_fixed_from || this.defaultFixedDate,
          to: track.schedule_fixed_to || this.defaultFixedDate,
        },
      };
    },

    /**
     * Extract the Uniform values and instance variables
     * into a variable payload for the save mutation.
     *
     * @returns {Object}
     */
    getMutationVariables() {
      const formValues = this.formValuesToSave;
      const track_schedule = Object.assign(
        {
          track_id: this.track.id,
        },
        this.getScheduleVariables(formValues),
        this.getAdditionalSettingsVariables(formValues),
        this.getDueDateVariables(formValues),
        this.getRepeatingVariables(formValues)
      );
      return { track_schedule };
    },

    /**
     * Add the additional settings variables for the mutation.
     *
     * @returns {Object}
     */
    getAdditionalSettingsVariables(form) {
      return {
        subject_instance_generation:
          form.additionalSettings.subjectInstanceGeneration,
      };
    },

    /**
     * Add the required due date variables for the mutation.
     *
     * @returns {Object}
     */
    getDueDateVariables(form) {
      const gql = { due_date_is_enabled: form.dueDate.dueDateEnabled };

      if (!form.dueDate.dueDateEnabled) {
        return gql;
      }

      if (this.dueDateSupportsFixed) {
        gql.due_date_is_fixed = form.dueDate.dueDateType === 'fixed';
      }

      if (gql.due_date_is_fixed) {
        gql.due_date_fixed = form.dueDate.fixedDueDate;
      } else {
        gql.due_date_offset = {
          count: Number(form.dueDate.dueDateOffset.relative.value),
          unit: form.dueDate.dueDateOffset.relative.range,
        };
      }
      return gql;
    },

    /**
     * Add the required repeating variables for the mutation.
     *
     * @returns {Object}
     */
    getRepeatingVariables(form) {
      const gql = {
        repeating_is_enabled: this.toggleValues.repeatingIsEnabled,
      };

      if (this.toggleValues.repeatingIsEnabled) {
        const values = form.repeatingValues;
        const repeatingTrigger = values.repeatingTrigger;

        gql.repeating_is_limited = values.repeatingIsLimited;
        if (values.repeatingIsLimited) {
          gql.repeating_limit = values.repeatingLimit;
        }
        gql.repeating_offset = {
          count: values.repeatingOffset[repeatingTrigger].value,
          unit: values.repeatingOffset[repeatingTrigger].range,
        };
        gql.repeating_trigger_interval = repeatingTrigger;
        gql.repeating_trigger_type = values.repeatingTriggerType;
        gql.repeating_type = 'UNSET'; // Deprecated
      }

      return gql;
    },

    /**
     * Add the required schedule variables for the mutation.
     *
     * @returns {Object}
     */
    getScheduleVariables(form) {
      const gql = {
        schedule_is_open: this.toggleValues.scheduleIsOpen,
        schedule_is_fixed: this.toggleValues.scheduleIsFixed,
      };

      if (this.toggleValues.scheduleIsFixed) {
        let timezone = form.scheduleFixed.from.timezone;

        // Fixed start date
        gql.schedule_fixed_from = {
          iso: form.scheduleFixed.from.iso,
          timezone,
        };

        if (!this.toggleValues.scheduleIsOpen) {
          // Fixed start date with closing date
          gql.schedule_fixed_to = {
            iso: form.scheduleFixed.to.iso,
            timezone,
          };
        }
      } else {
        gql.schedule_use_anniversary = form.scheduleDynamic.useAnniversary;
        // Dynamic start date

        if (
          form.scheduleDynamic.fromDirection === RELATIVE_DATE_DIRECTION_BEFORE
        ) {
          gql.schedule_dynamic_from = {
            count: form.scheduleDynamic.fromOffset.before.value,
            unit: form.scheduleDynamic.fromOffset.before.range,
            direction: form.scheduleDynamic.fromDirection,
          };
        } else if (
          form.scheduleDynamic.fromDirection === RELATIVE_DATE_DIRECTION_AFTER
        ) {
          gql.schedule_dynamic_from = {
            count: form.scheduleDynamic.fromOffset.after.value,
            unit: form.scheduleDynamic.fromOffset.after.range,
            direction: form.scheduleDynamic.fromDirection,
          };
        } else {
          gql.schedule_dynamic_from = {
            count: 0,
            unit: 'DAY',
            direction: RELATIVE_DATE_DIRECTION_BEFORE,
          };
        }

        const dynamicDateSourceParts = form.scheduleDynamic.dynamic_source.split(
          '--'
        );

        const customData = Object.assign(
          {},
          this.getDynamicCustomSettingConfig(),
          form.scheduleDynamic.dynamicCustomSettings
        );

        gql.schedule_dynamic_source = {
          resolver_class_name: dynamicDateSourceParts[0],
          option_key: dynamicDateSourceParts[1],
          custom_data: JSON.stringify(customData),
        };

        gql.due_date_offset = null;

        if (!this.toggleValues.scheduleIsOpen) {
          // Dynamic start date with closing date
          if (
            form.scheduleDynamic.toDirection === RELATIVE_DATE_DIRECTION_BEFORE
          ) {
            gql.schedule_dynamic_to = {
              count: form.scheduleDynamic.toOffset.before.value,
              unit: form.scheduleDynamic.toOffset.before.range,
              direction: form.scheduleDynamic.toDirection,
            };
          } else if (
            form.scheduleDynamic.toDirection === RELATIVE_DATE_DIRECTION_AFTER
          ) {
            gql.schedule_dynamic_to = {
              count: form.scheduleDynamic.toOffset.after.value,
              unit: form.scheduleDynamic.toOffset.after.range,
              direction: form.scheduleDynamic.toDirection,
            };
          } else {
            gql.schedule_dynamic_to = {
              count: 0,
              unit: 'DAY',
              direction: RELATIVE_DATE_DIRECTION_BEFORE,
            };
          }
        }
      }

      return gql;
    },

    /**
     * Get dynamic custom setting data
     *
     * @param track
     * @returns {object}
     */
    getDynamicCustomSettingData(track) {
      let data = {};
      if (track.schedule_dynamic_source) {
        data = JSON.parse(track.schedule_dynamic_source.custom_data);
      } else {
        data = JSON.parse(this.dynamicDateSources[0].custom_data);
      }
      return data;
    },

    /**
     * Get dynamic custom setting component
     *
     * @returns {string}
     */
    getDynamicCustomSettingComponent() {
      let componentName = null;
      if (this.track && this.track.schedule_dynamic_source) {
        componentName = this.track.schedule_dynamic_source
          .custom_setting_component;
      } else {
        componentName = this.dynamicDateSources[0].custom_setting_component;
      }
      return componentName;
    },

    /**
     * Get the selected dynamic date source from the uniform values.
     *
     * @returns {object}
     */
    getSelectedDynamicDateSourceFromValues(values) {
      return this.dynamicDateSources
        .filter(
          item =>
            `${item.resolver_class_name}--${item.option_key}` ==
            values.scheduleDynamic.dynamic_source
        )
        .shift();
    },

    /**
     * Get dynamic custom setting general configs.
     *
     * @returns {object}
     */
    getDynamicCustomSettingConfig() {
      return { this_activity_id: this.activity.id };
    },

    /**
     * @returns {string || null}
     */
    getDynamicDateSourceName(dynamicSource) {
      if (dynamicSource) {
        return dynamicSource.display_name;
      }
      if (this.track && this.track.schedule_dynamic_source) {
        return this.track.schedule_dynamic_source.display_name;
      }
      return null;
    },

    /**
     * Check selected source contains custom settings
     *
     * @param optionKey
     * @returns {boolean}
     */
    isDynamicCustomSettingSource(optionKey) {
      let source = this.dynamicDateSources
        .filter(
          item => `${item.resolver_class_name}--${item.option_key}` == optionKey
        )
        .shift();

      return source.custom_setting_component !== null;
    },

    /**
     *
     * @param {object || null} dynamicSource
     * @returns {boolean}
     */
    isDynamicDateSourceJobBased(dynamicSource) {
      if (dynamicSource) {
        return dynamicSource.resolver_base == DATE_RESOLVER_JOB_BASED;
      }
      if (this.track && this.track.schedule_dynamic_source) {
        return (
          this.track.schedule_dynamic_source.resolver_base ==
          DATE_RESOLVER_JOB_BASED
        );
      }
      return false;
    },

    /**
     * Call the save mutation
     *
     * @returns {Promise}
     */
    async save() {
      const variables = this.getMutationVariables();
      const { data: resultData } = await this.$apollo.mutate({
        mutation: UpdateTrackScheduleMutation,
        variables,
      });

      // Force the top level query to be re-fetched because some data at the top level relies on data managed at this
      // level, for example: track.due_date_is_enabled.
      this.$emit('refetch-core-query');

      return resultData.mod_perform_update_track_schedule.track;
    },

    /**
     * Try to persist the schedule to the back end emitting events on success/failure.
     *
     */
    async trySave() {
      // Used to disable buttons
      this.isSaving = true;
      try {
        const track = await this.save();
        this.initialValues = this.getInitialValues(track);
        this.hideConfirmationModal();
        this.showSuccessNotification();
        this.isSaving = false;
        this.hasUnsavedChanges(false);
      } catch (e) {
        this.hideConfirmationModal();
        this.showErrorNotification();
        this.isSaving = false;
      }
    },

    /**
     * Shows the schedule confirmation dialog.
     *
     * @param {object} values
     */
    showConfirmationModal(values) {
      this.formValuesToSave = values;
      this.confirmationModalOpen = true;
    },

    /**
     * Hides the save confirmation dialog.
     */
    hideConfirmationModal() {
      this.formValuesToSave = null;
      this.confirmationModalOpen = false;
    },

    /**
     * Show a generic saving error toast.
     */
    showErrorNotification() {
      notify({
        message: this.$str('toast_error_generic_update', 'mod_perform'),
        type: 'error',
      });
    },

    /**
     * Show a generic saving success toast.
     */
    showSuccessNotification() {
      notify({
        message: this.$str('toast_success_save_schedule', 'mod_perform'),
        type: 'success',
      });
    },

    hasUnsavedChanges(hasUnsavedChanges) {
      this.$emit('unsaved-changes', hasUnsavedChanges);
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "activity_instance_creation_heading",
      "modal_confirm",
      "schedule_confirm_title",
      "schedule_confirm_title_active",
      "schedule_confirm_title_draft",
      "schedule_save_changes",
      "schedule_is_fixed",
      "schedule_is_open",
      "toast_error_generic_update",
      "toast_success_save_schedule"
    ],
    "core": [
      "cancel"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performAssignmentSchedule {
  & > * + * {
    margin-top: var(--gap-8);
  }

  &__heading {
    margin: 0;
    @include tui-font-heading-small;
  }

  &__form {
    & > * + * {
      margin-top: var(--gap-4);
    }

    &-section {
      padding: var(--gap-8);
      background: var(--color-neutral-3);
    }

    &-buttons {
      margin-top: var(--gap-8);
    }
  }
}
</style>
