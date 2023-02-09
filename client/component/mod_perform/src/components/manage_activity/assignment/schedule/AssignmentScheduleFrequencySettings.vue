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

  @author Riana Rossouw <riana.rossouw@totaralearning.com>
  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module mod_perform
-->
<template>
  <div class="tui-assignmentScheduleFrequencySettings">
    <h4 class="tui-assignmentScheduleFrequencySettings__title">
      {{ title }}
    </h4>

    <div class="tui-assignmentScheduleFrequencySettings__description">
      {{
        $str(
          isRepeating
            ? 'schedule_repeating_enabled_description'
            : 'schedule_repeating_disabled_description',
          'mod_perform'
        )
      }}
    </div>

    <template v-if="isRepeating">
      <FormScope :validate="frequencyTriggerValidator" path="repeatingValues">
        <div class="tui-assignmentScheduleFrequencySettings__form">
          <FormRowStack>
            <FormRow :label="$str('trigger_type', 'mod_perform')">
              <FormSelect
                char-length="20"
                name="repeatingTriggerType"
                :options="getTriggerTypeOptions()"
                @input="updateTriggerSelection"
              />
            </FormRow>

            <Field v-slot="{ value }" name="repeatingTriggerType">
              <FormRow :label="$str('trigger', 'mod_perform')">
                <FormRadioGroup name="repeatingTrigger" :no-padding-top="true">
                  <!-- Time since creation -->
                  <FormRadioWithInput
                    v-if="value !== typeCreation"
                    :name="['repeatingOffset', minTime]"
                    :text="
                      $str(
                        'schedule_repeating_minimum_time_since_creation',
                        'mod_perform'
                      )
                    "
                    :value="minTime"
                  >
                    <template
                      v-slot="{
                        disabledRadio,
                        nameLabel,
                        setAccessibleLabel,
                        update,
                        value,
                      }"
                    >
                      <RadioDateRange
                        :disabled="disabledRadio"
                        :name="nameLabel"
                        :value="value"
                        @accessible-change="
                          a =>
                            setAccessibleLabel(
                              $str(
                                'schedule_repeating_minimum_time_since_creation_a11y',
                                'mod_perform',
                                {
                                  range: a.range,
                                  value: a.value,
                                }
                              )
                            )
                        "
                        @input="update($event)"
                      />
                    </template>
                  </FormRadioWithInput>

                  <!-- Time after trigger type -->
                  <FormRadioWithInput
                    :label-partials="{ type: getTriggerTypeInlineText(value) }"
                    :name="['repeatingOffset', timeSince]"
                    :text="
                      $str(
                        'schedule_repeating_every_time_after_type',
                        'mod_perform',
                        {
                          type: getTriggerTypeInlineText(value),
                        }
                      )
                    "
                    :value="timeSince"
                  >
                    <template
                      v-slot="{
                        disabledRadio,
                        labelPartials,
                        nameLabel,
                        setAccessibleLabel,
                        update,
                        value,
                      }"
                    >
                      <RadioDateRange
                        :disabled="disabledRadio"
                        :name="nameLabel"
                        :value="value"
                        @accessible-change="
                          a =>
                            setAccessibleLabel(
                              $str(
                                'schedule_repeating_every_time_after_type_a11y',
                                'mod_perform',
                                {
                                  range: a.range,
                                  type: labelPartials.type,
                                  value: a.value,
                                }
                              )
                            )
                        "
                        @input="update($event)"
                      />
                    </template>
                  </FormRadioWithInput>
                </FormRadioGroup>
              </FormRow>
            </Field>

            <!-- Repeating limit -->
            <FormRow
              :label="$str('schedule_repeating_limit_label', 'mod_perform')"
            >
              <FormRadioGroup name="repeatingIsLimited">
                <Radio :value="false">
                  {{ noLimitLabel }}
                </Radio>

                <FormRadioWithInput
                  :name="['repeatingLimit']"
                  :text="
                    $str('schedule_repeating_limit_maximum_of', 'mod_perform')
                  "
                  :value="true"
                >
                  <template
                    v-slot="{
                      disabledRadio,
                      nameLabel,
                      setAccessibleLabel,
                      update,
                      value,
                    }"
                  >
                    <RadioNumberInput
                      :disabled="disabledRadio"
                      :name="nameLabel"
                      :value="value"
                      @input="update($event)"
                      @accessible-change="
                        a =>
                          setAccessibleLabel(
                            $str(
                              'schedule_repeating_limit_maximum_of_a11y',
                              'mod_perform',
                              a
                            )
                          )
                      "
                    />
                  </template>
                </FormRadioWithInput>
              </FormRadioGroup>
            </FormRow>
          </FormRowStack>
        </div>
      </FormScope>
    </template>
  </div>
</template>

<script>
import Field from 'tui/components/reform/Field';
import Radio from 'tui/components/form/Radio';
import RadioDateRange from 'tui/components/form/RadioDateRangeInput';
import RadioNumberInput from 'tui/components/form/RadioNumberInput';

import {
  FormRadioGroup,
  FormRadioWithInput,
  FormRow,
  FormRowStack,
  FormScope,
  FormSelect,
} from 'tui/components/uniform';

import {
  SCHEDULE_REPEATING_TRIGGER_MIN_TIME,
  SCHEDULE_REPEATING_TRIGGER_TIME_SINCE,
  SCHEDULE_REPEATING_TRIGGER_TYPE_CLOSURE,
  SCHEDULE_REPEATING_TRIGGER_TYPE_COMPLETION,
  SCHEDULE_REPEATING_TRIGGER_TYPE_COMPLETION_CLOSURE,
  SCHEDULE_REPEATING_TRIGGER_TYPE_CREATION,
} from 'mod_perform/constants';

export default {
  inject: {
    reformScope: {},
  },

  components: {
    Field,
    FormRadioGroup,
    FormRadioWithInput,
    FormRow,
    FormRowStack,
    FormScope,
    FormSelect,
    Radio,
    RadioDateRange,
    RadioNumberInput,
  },

  props: {
    isOpen: {
      type: Boolean,
      required: true,
    },
    isRepeating: {
      type: Boolean,
      required: true,
    },
  },

  data() {
    return {
      minTime: SCHEDULE_REPEATING_TRIGGER_MIN_TIME,
      timeSince: SCHEDULE_REPEATING_TRIGGER_TIME_SINCE,
      typeClosure: SCHEDULE_REPEATING_TRIGGER_TYPE_CLOSURE,
      typeCompletion: SCHEDULE_REPEATING_TRIGGER_TYPE_COMPLETION,
      typeCompletionClosure: SCHEDULE_REPEATING_TRIGGER_TYPE_COMPLETION_CLOSURE,
      typeCreation: SCHEDULE_REPEATING_TRIGGER_TYPE_CREATION,
    };
  },

  computed: {
    /**
     * Setting title
     *
     * @return {String}
     */
    title() {
      return this.$str(
        this.isRepeating
          ? 'schedule_repeating_enabled_heading'
          : 'schedule_repeating_disabled_heading',
        'mod_perform'
      );
    },

    /**
     * Label for no limit radio option
     *
     * @return {String}
     */
    noLimitLabel() {
      return this.$str(
        this.isOpen
          ? 'schedule_repeating_limit_none_open_ended'
          : 'schedule_repeating_limit_none',
        'mod_perform'
      );
    },
  },

  methods: {
    /**
     * Validate the number inputs
     *
     * @param {Object} values form values
     * @return {Object}
     */
    frequencyTriggerValidator(values) {
      const errors = {};
      const trigger = values.repeatingTrigger;
      const offsetValue = Number(values.repeatingOffset[trigger].value);

      if (values.repeatingLimit) {
        const repeatingLimit = Number(values.repeatingLimit);
        const errorRepeating = this._validateNumber(repeatingLimit);

        if (errorRepeating) {
          errors.repeatingLimit = errorRepeating;
        }
      }

      let errorOffset = this._validateNumber(offsetValue);
      if (errorOffset) {
        errors.repeatingOffset = { [trigger]: errorOffset };
      }

      return errors;
    },

    _validateNumber(number) {
      // Let's have 32-bit max integer as a limit to be on the safe side.
      const maxValue = 2147483647;
      const belowMinValueErrorString = this.$str(
        'schedule_repeating_date_min_value',
        'mod_perform'
      );
      const aboveMaxValueErrorString = this.$str(
        'schedule_repeating_date_max_value',
        'mod_perform',
        maxValue
      );
      const notAWholeNumber = this.$str(
        'schedule_repeating_date_error_value',
        'mod_perform'
      );

      if (!Number.isInteger(number)) {
        return notAWholeNumber;
      } else if (number < 1) {
        return belowMinValueErrorString;
      } else if (number > maxValue) {
        return aboveMaxValueErrorString;
      }
      return null;
    },

    /**
     * Get the matching values for the current selection and return inline text string
     *
     * @param {String} value select input value
     * @return {String}
     */
    getTriggerTypeInlineText(value) {
      let option = this.getTriggerTypeOptions().find(i => i.id === value);
      return option ? option.text : '';
    },

    /**
     * Available data for the different trigger types
     *
     * @return {Array}
     */
    getTriggerTypeOptions() {
      return [
        {
          id: this.typeCreation,
          label: this.$str(
            'schedule_repeating_trigger_type_creation',
            'mod_perform'
          ),
          text: this.$str(
            'schedule_repeating_trigger_type_creation_inline',
            'mod_perform'
          ),
        },
        {
          id: this.typeCompletion,
          label: this.$str(
            'schedule_repeating_trigger_type_completion',
            'mod_perform'
          ),
          text: this.$str(
            'schedule_repeating_trigger_type_completion_inline',
            'mod_perform'
          ),
        },
        {
          id: this.typeClosure,
          label: this.$str(
            'schedule_repeating_trigger_type_closure',
            'mod_perform'
          ),
          text: this.$str(
            'schedule_repeating_trigger_type_closure_inline',
            'mod_perform'
          ),
        },
        {
          id: this.typeCompletionClosure,
          label: this.$str(
            'schedule_repeating_trigger_type_completion_closure',
            'mod_perform'
          ),
          text: this.$str(
            'schedule_repeating_trigger_type_completion_closure_inline',
            'mod_perform'
          ),
        },
      ];
    },

    /**
     * Update the trigger radio selection when only one option available
     *
     * @param {String} value Trigger types
     */
    updateTriggerSelection(value) {
      if (value === this.typeCreation) {
        this.reformScope.update(
          ['repeatingValues', 'repeatingTrigger'],
          this.timeSince
        );
      }
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "schedule_repeating_date_error_value",
      "schedule_repeating_date_max_value",
      "schedule_repeating_date_min_value",
      "schedule_repeating_disabled_heading",
      "schedule_repeating_disabled_description",
      "schedule_repeating_enabled_heading",
      "schedule_repeating_enabled_description",
      "schedule_repeating_every_time_after_type",
      "schedule_repeating_every_time_after_type_a11y",
      "schedule_repeating_limit_label",
      "schedule_repeating_limit_maximum_of",
      "schedule_repeating_limit_maximum_of_a11y",
      "schedule_repeating_limit_none",
      "schedule_repeating_limit_none_open_ended",
      "schedule_repeating_minimum_time_since_creation",
      "schedule_repeating_minimum_time_since_creation_a11y",
      "schedule_repeating_trigger_type_closure",
      "schedule_repeating_trigger_type_closure_inline",
      "schedule_repeating_trigger_type_completion",
      "schedule_repeating_trigger_type_completion_inline",
      "schedule_repeating_trigger_type_completion_closure",
      "schedule_repeating_trigger_type_completion_closure_inline",
      "schedule_repeating_trigger_type_creation",
      "schedule_repeating_trigger_type_creation_inline",
      "trigger",
      "trigger_type"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-assignmentScheduleFrequencySettings {
  &__title {
    margin: 0;
    @include tui-font-heading-x-small();
  }

  &__description {
    margin-top: var(--gap-4);
  }

  &__form {
    margin-top: var(--gap-8);
  }
}
</style>
