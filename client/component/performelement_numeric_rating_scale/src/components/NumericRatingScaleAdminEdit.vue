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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module performelement_numeric_rating_scale
-->

<template>
  <div class="tui-numericRatingScaleAdminEdit">
    <PerformAdminCustomElementEdit
      :initial-values="initialValues"
      :settings="settings"
      @cancel="$emit('display')"
      @change="updateValues"
      @update="$emit('update', $event)"
    >
      <!-- Min value -->
      <FormRow
        :label="$str('low_value_label', 'performelement_numeric_rating_scale')"
        :helpmsg="
          $str('numeric_min_value_help', 'performelement_numeric_rating_scale')
        "
        required
      >
        <FormNumber
          name="lowValue"
          :validations="v => [v.required(), v.integer()]"
          char-length="10"
        />
      </FormRow>

      <!-- Max value -->
      <FormRow
        :label="$str('high_value_label', 'performelement_numeric_rating_scale')"
        :helpmsg="
          $str('numeric_max_value_help', 'performelement_numeric_rating_scale')
        "
        required
      >
        <FormNumber
          name="highValue"
          :validations="v => [v.required(), v.integer(), v.min(minValue)]"
          char-length="10"
        />
      </FormRow>

      <!-- Default value -->
      <FormRow
        :label="
          $str('default_number_label', 'performelement_numeric_rating_scale')
        "
        :helpmsg="
          $str('default_value_help_text', 'performelement_numeric_rating_scale')
        "
        required
      >
        <FormNumber
          name="defaultValue"
          :validations="
            v => [v.required(), v.integer(), v.min(lowValue), v.max(highValue)]
          "
          char-length="10"
        />
      </FormRow>

      <!-- Enable description -->
      <FormRow>
        <FormCheckbox name="descriptionEnabled">
          {{ $str('element_enable_description', 'mod_perform') }}
        </FormCheckbox>
      </FormRow>

      <!-- Description weka, use v-show rather than v-if to pre-boot weka -->
      <FormRow
        v-show="descriptionEnabled"
        v-slot="{ id }"
        :label="$str('element_description', 'mod_perform')"
        :required="descriptionEnabled"
      >
        <FormField
          v-slot="{ value, update }"
          name="descriptionWekaDoc"
          :validations="v => (descriptionEnabled ? [v.required()] : [])"
        >
          <Weka
            :id="id"
            :context-id="activityContextId"
            :value="value"
            :usage-identifier="{
              component: 'performelement_numeric_rating_scale',
              area: 'content',
            }"
            variant="description"
            @input="update"
          />
        </FormField>
      </FormRow>
    </PerformAdminCustomElementEdit>
  </div>
</template>

<script>
import {
  FormCheckbox,
  FormField,
  FormRow,
  FormNumber,
} from 'tui/components/uniform';
import PerformAdminCustomElementEdit from 'mod_perform/components/element/PerformAdminCustomElementEdit';
import Weka from 'editor_weka/components/Weka';
import WekaValue from 'editor_weka/WekaValue';

export default {
  components: {
    FormCheckbox,
    FormField,
    FormRow,
    FormNumber,
    PerformAdminCustomElementEdit,
    Weka,
  },

  inheritAttrs: false,

  props: {
    data: Object,
    identifier: String,
    isRequired: Boolean,
    rawTitle: String,
    settings: Object,
    activityContextId: [Number, String],
  },

  data() {
    return {
      initialValues: {
        defaultValue:
          this.data && this.data.defaultValue ? this.data.defaultValue : null,
        highValue:
          this.data && this.data.highValue ? this.data.highValue : null,
        identifier: this.identifier,
        lowValue: this.data && this.data.lowValue ? this.data.lowValue : null,
        descriptionEnabled:
          (this.data && this.data.descriptionEnabled) || false,
        descriptionWekaDoc: this.data.descriptionWekaDoc
          ? WekaValue.fromDoc(this.data.descriptionWekaDoc)
          : WekaValue.empty(),
        rawTitle: this.rawTitle,
        responseRequired: this.isRequired,
      },

      lowValue: this.data && this.data.lowValue ? this.data.lowValue : '0',
      highValue: this.data && this.data.highValue ? this.data.highValue : '0',
      descriptionEnabled: (this.data && this.data.descriptionEnabled) || false,
    };
  },

  computed: {
    minValue() {
      return this.lowValue ? Number(this.lowValue) + 2 : null;
    },
  },

  methods: {
    /**
     * Update values based on user input for validation and weka show/hide.
     *
     * @param {Object} values
     */
    updateValues({ lowValue, highValue, descriptionEnabled }) {
      this.lowValue = lowValue;
      this.highValue = highValue;
      this.descriptionEnabled = descriptionEnabled;
    },
  },
};
</script>
<lang-strings>
{
  "performelement_numeric_rating_scale": [
    "default_number_label",
    "default_value_help_text",
    "high_value_label",
    "low_value_label",
    "numeric_max_value_help",
    "numeric_min_value_help",
    "scale_numeric_values"
  ],
  "mod_perform": [
    "element_description",
    "element_enable_description"
  ]
}
</lang-strings>
