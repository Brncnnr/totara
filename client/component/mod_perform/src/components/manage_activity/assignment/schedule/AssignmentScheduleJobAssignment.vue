<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module mod_perform
-->

<template>
  <FormField
    v-slot="{ value, update }"
    class="tui-assignmentScheduleJobAssignment"
    name="subjectInstanceGeneration"
    :validate="jobBasedCanDisableValidator"
  >
    <div class="tui-assignmentScheduleJobAssignment__heading">
      <h4 class="tui-assignmentScheduleJobAssignment__heading-header">
        {{ $str('schedule_job_assignment_based_instances', 'mod_perform') }}
      </h4>

      <ToggleSwitch
        :aria-label="
          $str('schedule_job_assignment_based_instances', 'mod_perform')
        "
        class="tui-assignmentScheduleJobAssignment__heading-toggle"
        :value="fieldValueToBool(value)"
        @input="update(fieldValueToString($event))"
      />
    </div>

    <div class="tui-assignmentScheduleJobAssignment__description">
      <h5 class="tui-assignmentScheduleJobAssignment__description-header">
        {{
          $str(
            fieldValueToBool(value)
              ? 'schedule_job_assignment_based_instances_enabled'
              : 'schedule_job_assignment_based_instances_disabled',
            'mod_perform'
          )
        }}
      </h5>

      <div>
        {{
          $str(
            fieldValueToBool(value)
              ? 'schedule_job_assignment_based_instances_enabled_description'
              : 'schedule_job_assignment_based_instances_disabled_description',
            'mod_perform'
          )
        }}
      </div>
    </div>
  </FormField>
</template>

<script>
import ToggleSwitch from 'tui/components/toggle/ToggleSwitch';
import { FormField } from 'tui/components/uniform';
import {
  SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT,
  SUBJECT_INSTANCE_GENERATION_ONE_PER_JOB,
} from 'mod_perform/constants';

export default {
  components: {
    FormField,
    ToggleSwitch,
  },

  props: {
    dynamicSourceName: {
      type: String,
    },
    usesJobBasedDynamicSource: {
      type: Boolean,
    },
  },

  methods: {
    /**
     * Convert field value to a boolean for toggle switch
     *
     * @param {String} value
     * @return {Boolean}
     */
    fieldValueToBool(fieldValue) {
      return fieldValue === SUBJECT_INSTANCE_GENERATION_ONE_PER_JOB;
    },

    /**
     * Convert field value to a String for query
     *
     * @param {Boolean} value
     * @return {String}
     */
    fieldValueToString(fieldValue) {
      return fieldValue
        ? SUBJECT_INSTANCE_GENERATION_ONE_PER_JOB
        : SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT;
    },

    /**
     * Validator for disabled job assigned based instance
     * May not disable if job based dynamic source is used
     *
     * @param {String} value
     * @return {Object}
     */
    jobBasedCanDisableValidator(value) {
      if (
        this.usesJobBasedDynamicSource &&
        value === SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT
      ) {
        return this.$str(
          'schedule_job_assignment_based_disable_error',
          'mod_perform',
          this.dynamicSourceName
        );
      }

      return null;
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "schedule_job_assignment_based_disable_error",
      "schedule_job_assignment_based_instances",
      "schedule_job_assignment_based_instances_disabled",
      "schedule_job_assignment_based_instances_disabled_description",
      "schedule_job_assignment_based_instances_enabled",
      "schedule_job_assignment_based_instances_enabled_description"
    ]
  }

</lang-strings>

<style lang="scss">
.tui-assignmentScheduleJobAssignment {
  & > * + * {
    max-width: 800px;
    margin-top: var(--gap-4);
  }

  &__heading {
    display: flex;
    align-items: center;
    padding-bottom: var(--gap-2);
    border-bottom: var(--border-width-thin) solid var(--color-neutral-5);

    &-header {
      margin: 0;
      @include tui-font-heading-x-small();
    }

    &-toggle {
      margin-left: auto;
    }
  }

  &__description {
    & > * + * {
      margin-top: var(--gap-2);
    }

    &-header {
      margin: 0;
      @include tui-font-heading-label();
    }
  }
}
</style>
