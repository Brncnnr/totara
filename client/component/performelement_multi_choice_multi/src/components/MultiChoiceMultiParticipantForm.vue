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

  @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
  @module performelement_multi_choice_multi
-->
<template>
  <!-- Handle the different view switching (read only / print / form),
  populate form content if editable and display others responses -->
  <ElementParticipantFormContent
    v-bind="$attrs"
    :element="element"
    :is-draft="isDraft"
    :from-print="false"
  >
    <template v-slot:content="{ labelId }">
      <FormScope :path="path" :process="process">
        <div class="tui-multiChoiceMultiParticipantForm">
          <FormRowDetails :id="labelId">
            <span v-for="(settingString, i) in settingStrings" :key="i">
              {{ settingString }} <br v-if="i < settingString.length" />
            </span>
          </FormRowDetails>
          <FormCheckboxGroup :validations="validations" name="response">
            <Checkbox
              v-for="item in element.data.options"
              :key="item.name"
              :value="item.name"
            >
              {{ item.value }}
            </Checkbox>
          </FormCheckboxGroup>
        </div>
      </FormScope>
    </template>
  </ElementParticipantFormContent>
</template>

<script>
import Checkbox from 'tui/components/form/Checkbox';
import ElementParticipantFormContent from 'mod_perform/components/element/ElementParticipantFormContent';
import FormCheckboxGroup from 'tui/components/uniform/FormCheckboxGroup';
import FormRowDetails from 'tui/components/form/FormRowDetails';
import FormScope from 'tui/components/reform/FormScope';
import { v as validation } from 'tui/validation';

export default {
  components: {
    Checkbox,
    ElementParticipantFormContent,
    FormCheckboxGroup,
    FormRowDetails,
    FormScope,
  },

  props: {
    element: Object,
    isDraft: Boolean,
    path: {
      type: [String, Array],
      default: '',
    },
  },

  computed: {
    /**
     * The min selection restriction (if set).
     *
     * @return {Number|null}
     */
    minSelectionRestriction() {
      const value = this.element.data.min;

      if (!value) {
        return null;
      }

      return parseInt(value, 10);
    },

    /**
     * The max selection restriction (if set).
     *
     * @return {Number|null}
     */
    maxSelectionRestriction() {
      const value = this.element.data.max;

      if (!value) {
        return null;
      }

      return parseInt(value, 10);
    },

    /**
     * The restriction setting explanations to be displayed above the element.
     *
     * @return {String[]}
     */
    settingStrings() {
      if (!this.element) {
        return [];
      }

      const strings = [];

      if (
        this.minSelectionRestriction !== null &&
        this.maxSelectionRestriction !== null &&
        this.minSelectionRestriction === this.maxSelectionRestriction
      ) {
        return [
          this.$str(
            'participant_restriction_min_max',
            'performelement_multi_choice_multi',
            this.minSelectionRestriction
          ),
        ];
      }

      if (this.minSelectionRestriction !== null) {
        strings.push(
          this.$str(
            'participant_restriction_min',
            'performelement_multi_choice_multi',
            this.minSelectionRestriction
          )
        );
      }

      if (this.maxSelectionRestriction !== null) {
        strings.push(
          this.$str(
            'participant_restriction_max',
            'performelement_multi_choice_multi',
            this.maxSelectionRestriction
          )
        );
      }

      return strings;
    },

    /**
     * An array of validation rules for the element.
     * The rules returned depend on if we are saving as draft or if a response is required or not.
     *
     * @return {(function|object)[]}
     */
    validations() {
      if (this.isDraft) {
        return [];
      }

      const rules = [
        this.exactSelectionRestrictionRule,
        this.minSelectionRestrictionRule,
        this.maxSelectionRestrictionRule,
      ];

      if (this.element && this.element.is_required) {
        return [validation.required(), ...rules];
      }

      return rules;
    },
  },

  methods: {
    /**
     * Validation run for enforcing the an exact selection count rule (if configured).
     *
     *
     * @param value
     * @return {null|*}
     */
    exactSelectionRestrictionRule(value) {
      if (!value) {
        value = [];
      }

      const minRestriction = this.minSelectionRestriction;
      const maxRestriction = this.maxSelectionRestriction;

      if (minRestriction === null && maxRestriction === null) {
        return null;
      }

      if (minRestriction !== maxRestriction) {
        return null;
      }

      // If the question is not required skip this validation if no answers have been selected.
      if (value.length === 0 && this.element && !this.element.is_required) {
        return null;
      }

      if (value.length < minRestriction || value.length > maxRestriction) {
        return this.$str(
          'participant_restriction_min_max',
          'performelement_multi_choice_multi',
          minRestriction
        );
      }

      return null;
    },

    /**
     * Validation run for enforcing the min selection count rule (if configured).
     *
     * @param value
     * @return {null|*}
     */
    minSelectionRestrictionRule(value) {
      if (!value) {
        value = [];
      }

      const minRestriction = this.minSelectionRestriction;

      if (minRestriction === null) {
        return null;
      }

      // If the question is not required skip this validation if no answers have been selected.
      if (value.length === 0 && this.element && !this.element.is_required) {
        return null;
      }

      if (value.length < minRestriction) {
        return this.$str(
          'participant_restriction_min',
          'performelement_multi_choice_multi',
          minRestriction
        );
      }

      return null;
    },

    /**
     * Validation run for enforcing the max selection count rule (if configured).
     *
     * @param value
     * @return {null|*}
     */
    maxSelectionRestrictionRule(value) {
      if (!value) {
        return null;
      }

      const maxRestriction = this.maxSelectionRestriction;

      if (maxRestriction === null) {
        return null;
      }

      if (value.length > maxRestriction) {
        return this.$str(
          'participant_restriction_max',
          'performelement_multi_choice_multi',
          maxRestriction
        );
      }

      return null;
    },

    /**
     * Process the form values.
     *
     * @param value
     * @return {null|string[]}
     */
    process(value) {
      if (!value || !Array.isArray(value.response)) {
        return null;
      }

      return value.response;
    },
  },
};
</script>

<lang-strings>
{
  "performelement_multi_choice_multi": [
    "participant_restriction_min_max",
    "participant_restriction_min",
    "participant_restriction_max"
  ]
}
</lang-strings>
