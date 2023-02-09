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
  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module mod_perform
-->
<template>
  <div class="tui-assignmentScheduleCreationRange">
    <h4 class="tui-assignmentScheduleCreationRange__title">
      {{ title }}
    </h4>

    <div class="tui-assignmentScheduleCreationRange__description">
      {{ description }}
    </div>

    <template v-if="isFixed">
      <FormScope path="scheduleFixed" :validate="fixedDateValidator">
        <div class="tui-assignmentScheduleCreationRange__form">
          <FormRowStack>
            <FormRow
              v-slot="{ id, labelId }"
              :label="$str('schedule_range_date_start', 'mod_perform')"
            >
              <FieldGroup :aria-labelledby="labelId">
                <FormDateSelector
                  :id="id"
                  name="from"
                  type="date"
                  has-timezone
                  :validations="v => [v.required()]"
                />
              </FieldGroup>
            </FormRow>

            <FormRow
              v-if="!isOpen"
              v-slot="{ labelId }"
              :label="$str('schedule_range_date_end', 'mod_perform')"
            >
              <FieldGroup :aria-labelledby="labelId">
                <FormDateSelector
                  :id="$id('fixed-date-to')"
                  name="to"
                  type="date"
                  :validations="v => [v.required()]"
                />
              </FieldGroup>
            </FormRow>
          </FormRowStack>
        </div>
      </FormScope>
    </template>

    <template v-else>
      <FormScope path="scheduleDynamic" :validate="relativeDateValidator">
        <div class="tui-assignmentScheduleCreationRange__form">
          <FormRowStack>
            <FormRow
              :label="
                $str('relative_date_selector_reference_date', 'mod_perform')
              "
            >
              <FormSelect
                v-if="dynamicDateSources"
                :id="$id('relative-date-reference-date')"
                name="dynamic_source"
                char-length="25"
                :options="dynamicSourcesForSelect"
              />
            </FormRow>

            <!-- Dynamic activity select -->
            <component
              :is="dynamicSettingComponentFor()"
              v-if="showCustomDynamicSetting"
              :data="dynamicDateSettingComponent.data"
              :config-data="dynamicDateSettingComponent.configData"
            />

            <FormRow
              :label="$str('schedule_range_use_anniversary', 'mod_perform')"
              :is-stacked="true"
              :aria-describedby="$id('use-anniversary')"
            >
              <div class="tui-assignmentScheduleCreationRange__form-checkbox">
                <FormCheckbox name="useAnniversary" />
              </div>
              <FormRowDetails :id="$id('use-anniversary')">
                {{ $str('schedule_use_anniversary_label', 'mod_perform') }}
              </FormRowDetails>
            </FormRow>

            <!-- From date -->
            <FormRow :label="$str('schedule_range_date_start', 'mod_perform')">
              <FormRadioGroup name="fromDirection">
                <Radio :value="false">
                  {{ $str('schedule_on_date', 'mod_perform') }}
                </Radio>

                <FormRadioWithInput
                  v-slot="{
                    disabledRadio,
                    nameLabel,
                    setAccessibleLabel,
                    update,
                    value,
                  }"
                  :name="['fromOffset', 'before']"
                  :text="$str('schedule_before', 'mod_perform')"
                  :value="beforeValue"
                >
                  <RadioDateRange
                    :disabled="disabledRadio"
                    :name="nameLabel"
                    :value="value"
                    @input="update($event)"
                    @accessible-change="
                      a =>
                        setAccessibleLabel(
                          $str('schedule_before_a11y', 'mod_perform', {
                            range: a.range,
                            value: a.value,
                          })
                        )
                    "
                  />
                </FormRadioWithInput>

                <FormRadioWithInput
                  v-slot="{
                    disabledRadio,
                    nameLabel,
                    setAccessibleLabel,
                    update,
                    value,
                  }"
                  :name="['fromOffset', 'after']"
                  :text="$str('schedule_after', 'mod_perform')"
                  :value="afterValue"
                >
                  <RadioDateRange
                    :disabled="disabledRadio"
                    :name="nameLabel"
                    :value="value"
                    @input="update($event)"
                    @accessible-change="
                      a =>
                        setAccessibleLabel(
                          $str('schedule_after_a11y', 'mod_perform', {
                            range: a.range,
                            value: a.value,
                          })
                        )
                    "
                  />
                </FormRadioWithInput>
              </FormRadioGroup>
            </FormRow>

            <!-- Until date -->
            <FormRow
              v-if="!isOpen"
              :label="$str('schedule_range_date_end', 'mod_perform')"
            >
              <FormRadioGroup name="toDirection">
                <Radio :value="false">
                  {{ $str('schedule_on_date', 'mod_perform') }}
                </Radio>

                <FormRadioWithInput
                  v-slot="{
                    disabledRadio,
                    nameLabel,
                    setAccessibleLabel,
                    update,
                    value,
                  }"
                  :name="['toOffset', 'before']"
                  :text="$str('schedule_before', 'mod_perform')"
                  :value="beforeValue"
                >
                  <RadioDateRange
                    :disabled="disabledRadio"
                    :name="nameLabel"
                    :value="value"
                    @input="update($event)"
                    @accessible-change="
                      a =>
                        setAccessibleLabel(
                          $str('schedule_before_a11y', 'mod_perform', {
                            range: a.range,
                            value: a.value,
                          })
                        )
                    "
                  />
                </FormRadioWithInput>

                <FormRadioWithInput
                  v-slot="{
                    disabledRadio,
                    nameLabel,
                    setAccessibleLabel,
                    update,
                    value,
                  }"
                  :name="['toOffset', 'after']"
                  :text="$str('schedule_after', 'mod_perform')"
                  :value="afterValue"
                >
                  <RadioDateRange
                    :disabled="disabledRadio"
                    :name="nameLabel"
                    :value="value"
                    @input="update($event)"
                    @accessible-change="
                      a =>
                        setAccessibleLabel(
                          $str('schedule_after_a11y', 'mod_perform', {
                            range: a.range,
                            value: a.value,
                          })
                        )
                    "
                  />
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
import FieldGroup from 'tui/components/form/FieldGroup';
import FormCheckbox from 'tui/components/uniform/FormCheckbox';
import FormRadioGroup from 'tui/components/uniform/FormRadioGroup';
import FormRowDetails from 'tui/components/form/FormRowDetails';
import Radio from 'tui/components/form/Radio';
import RadioDateRange from 'tui/components/form/RadioDateRangeInput';
import {
  FormDateSelector,
  FormRadioWithInput,
  FormRow,
  FormRowStack,
  FormScope,
  FormSelect,
} from 'tui/components/uniform';
import { isIsoAfter } from 'tui/date';

import {
  RELATIVE_DATE_DIRECTION_AFTER,
  RELATIVE_DATE_DIRECTION_BEFORE,
  RELATIVE_DATE_UNIT_DAY,
  RELATIVE_DATE_UNIT_WEEK,
} from 'mod_perform/constants';

export default {
  components: {
    FieldGroup,
    FormCheckbox,
    FormDateSelector,
    FormRadioGroup,
    FormRadioWithInput,
    FormRow,
    FormRowDetails,
    FormScope,
    FormSelect,
    FormRowStack,
    Radio,
    RadioDateRange,
  },

  props: {
    dynamicDateSettingComponent: {
      type: Object,
    },
    dynamicDateSources: {
      type: Array,
      required: true,
    },
    isFixed: {
      type: Boolean,
      required: true,
    },
    isOpen: {
      type: Boolean,
      required: true,
    },
  },

  data() {
    return {
      afterValue: RELATIVE_DATE_DIRECTION_AFTER,
      beforeValue: RELATIVE_DATE_DIRECTION_BEFORE,
    };
  },

  computed: {
    title() {
      if (this.isOpen && this.isFixed) {
        // Open-ended range defined by fixed dates
        return this.$str('schedule_range_heading_open_fixed', 'mod_perform');
      } else if (!this.isOpen && this.isFixed) {
        // Limited creation range defined by fixed dates
        return this.$str('schedule_range_heading_limited_fixed', 'mod_perform');
      } else if (this.isOpen && !this.isFixed) {
        // Open-ended creation range defined by dynamic dates
        return this.$str('schedule_range_heading_open_dynamic', 'mod_perform');
      } else {
        // Limited creation range defined by dynamic dates
        return this.$str(
          'schedule_range_heading_limited_dynamic',
          'mod_perform'
        );
      }
    },

    description() {
      if (!this.isFixed) {
        return this.$str(
          'schedule_range_date_description_limited_relative',
          'mod_perform'
        );
      } else {
        return this.$str('schedule_range_date_description', 'mod_perform');
      }
    },

    dynamicSourcesForSelect() {
      if (!this.dynamicDateSources) {
        return [];
      }
      return this.dynamicDateSources.map(option => {
        return {
          label: option.display_name,
          id: `${option.resolver_class_name}--${option.option_key}`,
        };
      });
    },

    showCustomDynamicSetting() {
      if (
        !this.dynamicDateSettingComponent ||
        !this.dynamicDateSettingComponent.name
      ) {
        return false;
      }
      return true;
    },
  },

  methods: {
    /**
     * Validate that the before & after dates are in order
     */
    dynamicSettingComponentFor() {
      return tui.asyncComponent(this.dynamicDateSettingComponent.name);
    },

    fixedDateValidator(values) {
      const errors = {};

      if (this.isOpen) {
        return errors;
      }

      if (!isIsoAfter(values.to.iso, values.from.iso)) {
        errors.to = this.$str('fixed_date_selector_error_range', 'mod_perform');
      }
      return errors;
    },

    relativeDateValidator(values) {
      const errors = {};
      const rangeOrderErrorString = this.$str(
        'relative_date_selector_error_range',
        'mod_perform'
      );
      const belowMinValueErrorString = this.$str(
        'schedule_range_date_min_value',
        'mod_perform'
      );

      const notAWholeNumber = this.$str(
        'relative_date_selector_error_value',
        'mod_perform'
      );

      const fromDirection = values.fromDirection;
      let fromRange = RELATIVE_DATE_UNIT_DAY;
      let fromValue = 0;
      const toDirection = values.toDirection;
      let toRange = RELATIVE_DATE_UNIT_DAY;
      let toValue = 0;

      if (fromDirection === RELATIVE_DATE_DIRECTION_BEFORE) {
        fromValue = Number(values.fromOffset.before.value);
        fromRange = values.fromOffset.before.range;
      } else if (fromDirection === RELATIVE_DATE_DIRECTION_AFTER) {
        fromValue = Number(values.fromOffset.after.value);
        fromRange = values.fromOffset.after.range;
      }

      if (toDirection === RELATIVE_DATE_DIRECTION_BEFORE) {
        toValue = Number(values.toOffset.before.value);
        toRange = values.toOffset.before.range;
      } else if (toDirection === RELATIVE_DATE_DIRECTION_AFTER) {
        toValue = Number(values.toOffset.after.value);
        toRange = values.toOffset.after.range;
      }

      if (fromRange == RELATIVE_DATE_UNIT_WEEK) {
        fromValue *= 7;
      }
      if (toRange == RELATIVE_DATE_UNIT_WEEK) {
        toValue *= 7;
      }

      if (fromDirection && !Number.isInteger(fromValue)) {
        if (fromDirection === RELATIVE_DATE_DIRECTION_BEFORE) {
          errors.fromOffset = { before: notAWholeNumber };
        } else {
          errors.fromOffset = { after: notAWholeNumber };
        }
      } else if (toDirection && !Number.isInteger(toValue)) {
        if (toDirection === RELATIVE_DATE_DIRECTION_BEFORE) {
          errors.toOffset = { before: notAWholeNumber };
        } else {
          errors.toOffset = { after: notAWholeNumber };
        }
      } else if (fromDirection && fromValue < 1) {
        if (fromDirection === RELATIVE_DATE_DIRECTION_BEFORE) {
          errors.fromOffset = { before: belowMinValueErrorString };
        } else {
          errors.fromOffset = { after: belowMinValueErrorString };
        }
      } else if (toDirection && toValue < 1) {
        if (toDirection === RELATIVE_DATE_DIRECTION_BEFORE) {
          errors.toOffset = { before: belowMinValueErrorString };
        } else {
          errors.toOffset = { after: belowMinValueErrorString };
        }
      }

      if (this.isOpen) {
        return errors;
      }

      if (
        fromDirection === RELATIVE_DATE_DIRECTION_AFTER &&
        toDirection === RELATIVE_DATE_DIRECTION_BEFORE
      ) {
        errors.toOffset = { before: rangeOrderErrorString };
      } else if (
        fromDirection === RELATIVE_DATE_DIRECTION_AFTER &&
        !toDirection
      ) {
        errors.toDirection = rangeOrderErrorString;
      } else if (
        fromDirection === RELATIVE_DATE_DIRECTION_AFTER &&
        toDirection === RELATIVE_DATE_DIRECTION_AFTER &&
        fromValue > toValue
      ) {
        errors.toOffset = { after: rangeOrderErrorString };
      } else if (
        !fromDirection &&
        toDirection === RELATIVE_DATE_DIRECTION_BEFORE &&
        fromValue < toValue
      ) {
        errors.toOffset = { before: rangeOrderErrorString };
      } else if (
        fromDirection === RELATIVE_DATE_DIRECTION_BEFORE &&
        toDirection === RELATIVE_DATE_DIRECTION_BEFORE &&
        fromValue < toValue
      ) {
        errors.toOffset = { before: rangeOrderErrorString };
      }
      return errors;
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "fixed_date_selector_error_range",
      "relative_date_selector_reference_date",
      "relative_date_selector_error_range",
      "relative_date_selector_error_value",
      "schedule_after",
      "schedule_after_a11y",
      "schedule_before",
      "schedule_before_a11y",
      "schedule_on_date",
      "schedule_range_date_description",
      "schedule_range_date_description_limited_relative",
      "schedule_range_date_end",
      "schedule_range_date_min_value",
      "schedule_range_date_start",
      "schedule_range_heading_limited_dynamic",
      "schedule_range_heading_limited_fixed",
      "schedule_range_heading_open_dynamic",
      "schedule_range_heading_open_fixed",
      "schedule_range_use_anniversary",
      "schedule_use_anniversary_label"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-assignmentScheduleCreationRange {
  &__title {
    margin: 0;
    @include tui-font-heading-x-small();
  }

  &__description {
    margin-top: var(--gap-4);
  }

  &__form {
    margin-top: var(--gap-8);

    &-checkbox {
      margin-top: var(--gap-2);
    }
  }
}
</style>
