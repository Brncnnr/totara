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
  <FormScope path="dueDate" :validate="validator">
    <div class="tui-assignmentScheduleDueDate">
      <!-- heading bar -->
      <div class="tui-assignmentScheduleDueDate__heading">
        <h4 class="tui-assignmentScheduleDueDate__heading-header">
          {{ $str('due_date', 'mod_perform') }}
        </h4>

        <div class="tui-assignmentScheduleDueDate__heading-toggle">
          <FormToggleSwitch
            :aria-label="$str('due_date', 'mod_perform')"
            name="dueDateEnabled"
            @input="dueDateToggleChange"
          />
        </div>
      </div>

      <Field v-slot="{ value }" name="dueDateEnabled">
        <div class="tui-assignmentScheduleDueDate__description">
          <h5 class="tui-assignmentScheduleDueDate__description-header">
            {{
              $str(
                value ? 'due_date_is_enabled' : 'due_date_is_disabled',
                'mod_perform'
              )
            }}
          </h5>

          <div class="tui-assignmentScheduleDueDate__description-text">
            <p>
              {{
                $str(
                  value
                    ? 'due_date_enabled_description'
                    : 'due_date_disabled_description',
                  'mod_perform'
                )
              }}
            </p>

            <p v-if="closeOnDueDate && value">
              {{
                $str(
                  'workflow_automatic_closure_on_due_date_help',
                  'mod_perform'
                )
              }}
            </p>
          </div>
        </div>
      </Field>

      <Field v-slot="{ value }" name="dueDateEnabled">
        <div v-if="value" class="tui-assignmentScheduleDueDate__form">
          <!-- Due Date is not limited/fixed -->
          <template v-if="!scheduleIsLimitedFixed">
            <FormRow
              :label="
                $str('due_date_enabled_relative_date_label', 'mod_perform')
              "
            >
              <FormField
                v-slot="{ labelId, value, update }"
                :name="['dueDateOffset', 'relative']"
              >
                <RadioDateRange
                  :aria-labelledby="labelId"
                  name="dueDateOffset"
                  :value="value"
                  @input="update"
                />
              </FormField>
            </FormRow>
          </template>

          <!-- Due Date Is Limited AND Fixed -->

          <FormRowStack v-else>
            <FormRow :label="$str('trigger', 'mod_perform')">
              <FormRadioGroup name="dueDateType">
                <FormRadioWithInput
                  v-slot="{
                    disabledRadio,
                    nameLabel,
                    setAccessibleLabel,
                    update,
                    value,
                  }"
                  :name="['dueDateOffset', 'relative']"
                  :text="$str('due_date_enabled_relative_date', 'mod_perform')"
                  value="relative"
                >
                  <RadioDateRange
                    :disabled="disabledRadio"
                    :name="nameLabel"
                    :value="value"
                    @input="update($event)"
                    @accessible-change="
                      a =>
                        setAccessibleLabel(
                          $str(
                            'due_date_enabled_relative_date_a11y',
                            'mod_perform',
                            {
                              range: a.range,
                              value: a.value,
                            }
                          )
                        )
                    "
                  />
                </FormRadioWithInput>
                <Radio value="fixed">
                  {{ $str('due_date_enabled_fixed_date', 'mod_perform') }}
                </Radio>
              </FormRadioGroup>
            </FormRow>

            <FormRow v-slot="{ labelId }" :label="$str('date', 'mod_perform')">
              <FieldGroup :aria-labelledby="labelId">
                <FormDateSelector
                  :id="$id('fixed-date-from')"
                  :disabled="disableFixedDate"
                  has-timezone
                  name="fixedDueDate"
                  type="date"
                  :validations="v => [v.required()]"
                />
              </FieldGroup>
            </FormRow>
          </FormRowStack>
        </div>
      </Field>

      <ConfirmationModal
        :confirm-button-text="$str('disable', 'core')"
        :open="dueDateWarningOpen"
        :title="
          $str(
            'workflow_automatic_closure_disable_due_date_warning_title',
            'mod_perform'
          )
        "
        @confirm="dueDateWarningOpen = false"
        @cancel="dueDateDisableCancelled"
      >
        <p>
          {{
            $str(
              'workflow_automatic_closure_disable_due_date_warning_main',
              'mod_perform'
            )
          }}
        </p>
        <p>
          {{
            $str(
              'workflow_automatic_closure_disable_due_date_warning_secondary',
              'mod_perform'
            )
          }}
        </p>
      </ConfirmationModal>
    </div>
  </FormScope>
</template>

<script>
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import Field from 'tui/components/reform/Field';
import FieldGroup from 'tui/components/form/FieldGroup';
import Radio from 'tui/components/form/Radio';
import RadioDateRange from 'tui/components/form/RadioDateRangeInput';

// Util
import { isIsoAfter } from 'tui/date';

import {
  FormDateSelector,
  FormField,
  FormRadioGroup,
  FormRadioWithInput,
  FormRow,
  FormRowStack,
  FormScope,
  FormToggleSwitch,
} from 'tui/components/uniform';

export default {
  inject: {
    reformScope: {},
  },

  components: {
    ConfirmationModal,
    Field,
    FieldGroup,
    FormDateSelector,
    FormField,
    FormRadioGroup,
    FormRadioWithInput,
    FormRow,
    FormRowStack,
    FormScope,
    FormToggleSwitch,
    Radio,
    RadioDateRange,
  },

  props: {
    closeOnDueDate: {
      type: Boolean,
      required: true,
    },
    scheduleIsLimitedFixed: {
      type: Boolean,
      required: true,
    },
  },

  data() {
    return {
      dueDateWarningOpen: false,
    };
  },

  computed: {
    /**
     * Check the radio group state to see if fixed date should be disabled
     *
     * @return {Boolean}
     */
    disableFixedDate() {
      let dateType = this.reformScope.getValue(['dueDate', 'dueDateType']);
      return dateType === 'relative';
    },
  },

  methods: {
    /**
     * Check if we need to show a confirmation to user
     * This is when the 'close on due date' setting in enabled
     *
     * @param {Boolean} value
     */
    dueDateToggleChange(value) {
      if (!value && this.closeOnDueDate) {
        this.dueDateWarningOpen = true;
      }
    },

    /**
     * Don't disabled the due date
     *
     */
    dueDateDisableCancelled() {
      this.dueDateWarningOpen = false;
      this.reformScope.update(['dueDate', 'dueDateEnabled'], true);
    },

    /**
     * Validate that the dates input is logically correct.
     *
     * @param {Object} values form form
     * @return {Object}
     */
    validator(values) {
      // If using a fixed due date
      if (values.dueDateType === 'fixed') {
        // Due date must be at least a day after. Note timezones are not factored in here,
        // but with an entire day difference there is a very slim chance of the validation being technically incorrect.
        let scheduleFixed = this.reformScope.getValue('scheduleFixed');

        if (
          values.fixedDueDate.iso === scheduleFixed.to.iso ||
          !isIsoAfter(values.fixedDueDate.iso, scheduleFixed.to.iso)
        ) {
          return {
            fixedDueDate: this.$str(
              'due_date_error_must_be_after_creation_date',
              'mod_perform'
            ),
          };
        }
      } else {
        const offsetValue = Number(values.dueDateOffset.relative.value);

        if (!Number.isInteger(offsetValue)) {
          return {
            dueDateOffset: {
              relative: this.$str('due_date_error_not_integer', 'mod_perform'),
            },
          };
        } else if (offsetValue <= 0) {
          return {
            dueDateOffset: {
              relative: this.$str(
                'due_date_error_must_be_after_creation_date',
                'mod_perform'
              ),
            },
          };
        }
      }

      return null;
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "disable"
    ],
    "mod_perform": [
      "date",
      "due_date",
      "due_date_error_not_integer",
      "due_date_error_must_be_after_creation_date",
      "due_date_disabled",
      "due_date_disabled_description",
      "due_date_enabled",
      "due_date_enabled_description",
      "due_date_enabled_fixed_date",
      "due_date_enabled_relative_date",
      "due_date_enabled_relative_date_a11y",
      "due_date_enabled_relative_date_label",
      "due_date_is_disabled",
      "due_date_is_enabled",
      "trigger",
      "workflow_automatic_closure_on_due_date_help",
      "workflow_automatic_closure_disable_due_date_warning_main",
      "workflow_automatic_closure_disable_due_date_warning_secondary",
      "workflow_automatic_closure_disable_due_date_warning_title"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-assignmentScheduleDueDate {
  & > * + * {
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
    max-width: 800px;

    & > * + * {
      margin-top: var(--gap-2);
    }

    &-header {
      margin: 0;
      @include tui-font-heading-label();
    }

    &-text {
      & > * {
        margin: 0;
      }

      & > * + * {
        margin-top: var(--gap-4);
      }
    }
  }

  &__form {
    margin-top: var(--gap-8);
  }
}
</style>
