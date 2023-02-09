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
  <div class="tui-redisplayAdminSummary">
    <PerformAdminCustomElementSummary
      :extra-fields="extraFields"
      :identifier="identifier"
      :is-required="isRequired"
      :settings="settings"
      :title="title"
      @display="$emit('display')"
    />
  </div>
</template>

<script>
import PerformAdminCustomElementSummary from 'mod_perform/components/element/PerformAdminCustomElementSummary';

export default {
  components: {
    PerformAdminCustomElementSummary,
  },
  props: {
    data: Object,
    identifier: String,
    isRequired: Boolean,
    settings: Object,
    title: String,
    type: Object,
    extraPluginConfigData: Object,
  },
  computed: {
    extraFields() {
      const extraFields = [
        {
          title: this.$str(
            'questions_for_response_aggregation',
            'performelement_aggregation'
          ),
          options: this.sectionElementsForAggregation.map(sectionElement => {
            return {
              value: `${sectionElement.element.title} (${sectionElement.element.element_plugin.name})`,
            };
          }),
        },
        {
          title: this.$str(
            'calculations_to_display',
            'performelement_aggregation'
          ),
          options: this.calculationsToDisplay.map(calculation => {
            return { value: calculation };
          }),
        },
      ];

      if (this.hasExcludedValues) {
        extraFields.push({
          title: this.$str('excluded_values', 'performelement_aggregation'),
          helpmsg: this.$str(
            'excluded_values_help_text',
            'performelement_aggregation'
          ),
          options: this.excludedValues.map(calculation => {
            return { value: calculation };
          }),
        });
      }

      return extraFields;
    },
    sectionElementsForAggregation() {
      return this.data.sourceSectionElementIds.map(id =>
        this.aggregatableSectionElements.find(
          sectionElement => Number(sectionElement.id) === Number(id)
        )
      );
    },
    aggregatableSectionElements() {
      return this.data.aggregatableSections.reduce(
        (aggregatable_elements, aggregatableSection) => [
          ...aggregatable_elements,
          ...aggregatableSection.aggregatable_section_elements,
        ],
        []
      );
    },
    calculationsToDisplay() {
      const options = this.extraPluginConfigData.calculations;

      return this.data.calculations.map(
        chosenCalculation =>
          options.find(option => option.name === chosenCalculation).label
      );
    },
    hasExcludedValues() {
      return this.excludedValues.length > 0;
    },
    excludedValues() {
      if (!this.data.excludedValues) {
        return [];
      }

      // Remove any empty entries.
      return this.data.excludedValues.filter(
        value => value !== null && value.trim() !== ''
      );
    },
  },
};
</script>

<lang-strings>
  {
    "performelement_aggregation": [
      "calculations_to_display",
      "excluded_values",
      "excluded_values_help_text",
      "questions_for_response_aggregation"
    ]
  }
</lang-strings>
