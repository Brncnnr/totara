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

  @module performelement_aggregation
-->

<template>
  <div class="tui-aggregationAdminEdit">
    <PerformAdminCustomElementEdit
      :initial-values="initialValues"
      :settings="settings"
      validation-mode="auto"
      @cancel="$emit('display')"
      @update="$emit('update', getElementForUpdate($event))"
    >
      <FormRow
        v-slot="{ labelId }"
        :label="$str('questions_to_aggregate', 'performelement_aggregation')"
        required
      >
        <Loader :loading="loading">
          <FieldArray
            v-slot="{ items, push, remove }"
            path="sourceSectionElementIds"
            :validate="sourceSectionElementsValidation"
          >
            <Repeater
              :rows="items"
              :min-rows="2"
              :delete-icon="true"
              :aria-labelledby="labelId"
              @add="push()"
              @remove="(item, i) => remove(i)"
            >
              <template v-slot="{ index }">
                <FormSelect
                  :aria-label="
                    $str(
                      'aggregate_question_n',
                      'performelement_aggregation',
                      index + 1
                    )
                  "
                  :options="activityAggregatableElementOptions"
                  :name="[index, 'value']"
                  :validations="v => [v.required()]"
                />
              </template>
              <template v-slot:add>
                <ButtonIcon
                  :aria-label="
                    $str('add_question', 'performelement_aggregation')
                  "
                  :styleclass="{ small: true }"
                  :text="$str('add_question', 'performelement_aggregation')"
                  @click="push(createEmptyRepeaterValue(questionCounter++))"
                >
                  <AddIcon />
                </ButtonIcon>
              </template>
            </Repeater>
          </FieldArray>
        </Loader>
      </FormRow>

      <FormRow
        v-slot="{ labelId }"
        :label="$str('calculations_to_display', 'performelement_aggregation')"
        required
      >
        <FormCheckboxGroup
          :validations="v => [v.required()]"
          name="calculations"
        >
          <Checkbox
            v-for="calculationOption in calculationOptions"
            :key="calculationOption.name"
            :value="calculationOption.name"
          >
            {{ calculationOption.label }}
          </Checkbox>
        </FormCheckboxGroup>
      </FormRow>

      <FormRow
        v-slot="{ labelId }"
        :label="$str('excluded_values', 'performelement_aggregation')"
        :helpmsg="
          $str('excluded_values_help_text', 'performelement_aggregation')
        "
      >
        <FieldArray v-slot="{ items, push, remove }" path="excludedValues">
          <Repeater
            :rows="items"
            :min-rows="1"
            :delete-icon="true"
            :aria-labelledby="labelId"
            @add="push()"
            @remove="(item, i) => remove(i)"
          >
            <template v-slot="{ index }">
              <FormNumber
                :aria-label="
                  $str(
                    'excluded_value_n',
                    'performelement_aggregation',
                    index + 1
                  )
                "
                :name="[index, 'value']"
                char-length="4"
              />
            </template>
            <template v-slot:add>
              <ButtonIcon
                :aria-label="
                  $str('add_excluded_value', 'performelement_aggregation')
                "
                :styleclass="{ small: true }"
                @click="push(createEmptyRepeaterValue(excludedValuesCounter++))"
              >
                <AddIcon />
              </ButtonIcon>
            </template>
          </Repeater>
        </FieldArray>
      </FormRow>
    </PerformAdminCustomElementEdit>
  </div>
</template>

<script>
import {
  FormRow,
  FormSelect,
  FieldArray,
  FormNumber,
  FormCheckboxGroup,
} from 'tui/components/uniform';
import Checkbox from 'tui/components/form/Checkbox';
import Loader from 'tui/components/loading/Loader';
import Repeater from 'tui/components/form/Repeater';
import AddIcon from 'tui/components/icons/Add';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import PerformAdminCustomElementEdit from 'mod_perform/components/element/PerformAdminCustomElementEdit';
import aggregatableQuestionElementsQuery from 'performelement_aggregation/graphql/aggregatable_question_elements';

export default {
  components: {
    Checkbox,
    FormCheckboxGroup,
    FormRow,
    FormSelect,
    FormNumber,
    Repeater,
    FieldArray,
    AddIcon,
    ButtonIcon,
    Loader,
    PerformAdminCustomElementEdit,
  },
  inheritAttrs: false,
  props: {
    identifier: String,
    rawData: Object,
    rawTitle: String,
    settings: Object,
    data: Object,
    currentActivityId: Number,
    extraPluginConfigData: Object,
  },
  data() {
    const initialValues = {
      sourceSectionElementIds: this.createValuesForRepeater(
        this.rawData.sourceSectionElementIds,
        false
      ),
      calculations: this.rawData.calculations,
      excludedValues: this.createValuesForRepeater(
        this.rawData.excludedValues,
        true
      ),
      identifier: this.identifier,
      rawTitle: this.rawTitle,
    };

    return {
      loading: false,
      initialValues,
      questionCounter: initialValues.sourceSectionElementIds.length,
      excludedValuesCounter: initialValues.excludedValues.length,
    };
  },
  computed: {
    activityAggregatableElementOptions() {
      const sections = this.rawData.aggregatableSections;

      let elementOptions;

      if (!sections || sections.length === 0) {
        return [
          {
            id: null,
            label: this.$str(
              'no_available_questions',
              'performelement_aggregation'
            ),
          },
        ];
      }

      // If there are multiple groups
      if (sections.length > 1) {
        elementOptions = sections.map(group => {
          return {
            label: group.title,
            options: group.aggregatable_section_elements.map(sectionElement => {
              return {
                id: sectionElement.id,
                label: sectionElement.element.title,
              };
            }),
          };
        });
      } else {
        elementOptions = sections[0].aggregatable_section_elements.map(
          sectionElement => {
            return {
              id: sectionElement.id,
              label: sectionElement.element.title,
            };
          }
        );
      }

      let defaultOption = {
        id: null,
        label: this.$str(
          'select_question_element',
          'performelement_aggregation'
        ),
      };

      elementOptions.unshift(defaultOption);
      return elementOptions;
    },
    calculationOptions() {
      return this.extraPluginConfigData.calculations;
    },
  },
  created() {
    if (!this.rawData.aggregatableSections) {
      this.fetchAggregatableSectionElements();
    }
  },
  methods: {
    /**
     * Fetch available source section elements,
     * should only be called on new element creation because this
     * information is available on the element.raw_data.
     */
    async fetchAggregatableSectionElements() {
      this.loading = true;

      const result = await this.$apollo.query({
        query: aggregatableQuestionElementsQuery,
        variables: { input: { activity_id: this.currentActivityId } },
        fetchPolicy: 'network-only',
      });

      this.$set(
        this.rawData,
        'aggregatableSections',
        result.data.performelement_aggregation_aggregatable_question_elements
          .sections
      );

      this.loading = false;
    },

    /**
     * Convert the element data to a format for persisting.
     */
    getElementForUpdate(element) {
      element.data.sourceSectionElementIds = element.data.sourceSectionElementIds.map(
        option => option.value
      );

      element.data.excludedValues = element.data.excludedValues.map(
        option => option.value
      );

      return element;
    },

    /**
     * Convert source section element ids in to a format for the repeater component.
     * @param values {number[]|undefined}
     * @param singleEmpty {boolean}
     * @return {{name: number, value: (null|string|number)}[]}
     */
    createValuesForRepeater(values, singleEmpty) {
      if (!values) {
        values = [];
      }

      const forRepeater = values.map((id, index) => {
        return {
          name: index,
          value: id,
        };
      });

      if (forRepeater.length === 0) {
        forRepeater.push(this.createEmptyRepeaterValue(forRepeater.length));
      }

      if (forRepeater.length === 1 && !singleEmpty) {
        forRepeater.push(this.createEmptyRepeaterValue(forRepeater.length));
      }

      return forRepeater;
    },

    /**
     * Create an empty object in to a format for the repeater component.
     * @param name {number}
     * @returns {{name: number, value: null}}
     */
    createEmptyRepeaterValue(name) {
      return {
        name,
        value: null,
      };
    },

    /**
     * Validation rule to ensure the same question is not selected more than once.
     *
     * @param items {{name: number, value: number|string}[]}
     * @return {{value: string|null}[]}
     */
    sourceSectionElementsValidation(items) {
      const message = this.$str(
        'duplicate_questions_validation_error',
        'performelement_aggregation'
      );

      const isDuplicate = itemToCheck => {
        // Exclude empty values, these are to be handled by the individual required validation.
        if (!itemToCheck.value) {
          return;
        }

        return items.some(
          item =>
            item.name !== itemToCheck.name &&
            Number(item.value) === Number(itemToCheck.value)
        );
      };

      return items.map(item => {
        return {
          value: isDuplicate(item) ? message : null,
        };
      });
    },
  },
};
</script>

<lang-strings>
  {
    "performelement_aggregation": [
      "add_excluded_value",
      "add_question",
      "aggregate_question_n",
      "calculations_to_display",
      "duplicate_questions_validation_error",
      "excluded_value_n",
      "excluded_values",
      "excluded_values",
      "excluded_values_help_text",
      "no_available_questions",
      "questions_for_response_aggregation",
      "questions_to_aggregate",
      "select_question_element"
    ]
  }
</lang-strings>
