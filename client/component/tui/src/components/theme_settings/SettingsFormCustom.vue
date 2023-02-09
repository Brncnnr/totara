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

  @author Dave Wallace <dave.wallace@totaralearning.com>
  @package tui
-->

<template>
  <Uniform
    v-if="initialValuesSet"
    :initial-values="initialValues"
    :errors="errorsForm"
    @change="handleChange"
    @submit="submit"
  >
    <FormRowStack spacing="large">
      <FormRow
        v-if="customFooterEditable"
        :label="$str('formcustom_label_customfooter', 'totara_tui')"
        :is-stacked="true"
        :aria-describedby="$id('formcustom-customfooter-details')"
      >
        <FormTextarea
          :name="['formcustom_field_customfooter', 'value']"
          :rows="rows('formcustom_field_customfooter', 6, 20)"
          char-length="full"
        />
        <FormRowDetails :id="$id('formcustom-customfooter-details')">
          {{ $str('formcustom_details_customfooter', 'totara_tui') }}
        </FormRowDetails>
      </FormRow>

      <FormRow
        v-if="customCssEditable"
        :label="$str('formcustom_label_customcss', 'totara_tui')"
        :is-stacked="true"
        :aria-describedby="$id('formcustom-customcss-details')"
      >
        <FormTextarea
          :name="['formcustom_field_customcss', 'value']"
          spellcheck="false"
          :rows="rows('formcustom_field_customcss', 6, 30)"
          char-length="full"
        />
        <FormRowDetails :id="$id('formcustom-customcss-details')">
          {{ $str('formcustom_details_customcss', 'totara_tui') }}
        </FormRowDetails>
      </FormRow>

      <FormRow>
        <ButtonGroup>
          <Button
            :styleclass="{ primary: 'true' }"
            :text="$str('save', 'totara_core')"
            :aria-label="
              $str(
                'saveextended',
                'totara_core',
                $str('tabcustom', 'totara_tui') +
                  ' ' +
                  $str('settings', 'totara_core')
              )
            "
            :disabled="isSaving"
            type="submit"
          />
        </ButtonGroup>
      </FormRow>
    </FormRowStack>
  </Uniform>
</template>

<script>
import theme_settings from 'tui/lib/theme_settings';
import {
  Uniform,
  FormRow,
  FormRowStack,
  FormTextarea,
} from 'tui/components/uniform';
import FormRowDetails from 'tui/components/form/FormRowDetails';
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';

export default {
  components: {
    Uniform,
    FormRow,
    FormRowStack,
    FormRowDetails,
    FormTextarea,
    Button,
    ButtonGroup,
  },

  props: {
    /**
     * Array of Objects, each describing the properties for fields that are part
     * of this Form. There is only an Object present in this Array if it came
     * from the server as it was previously saved
     */
    savedFormFieldData: {
      type: Array,
      default: function() {
        return [];
      },
    },

    /**
     * Saving state, controlled by parent component GraphQl mutation handling
     */
    isSaving: {
      type: Boolean,
      default: function() {
        return false;
      },
    },

    /**
     * Tenant ID or null if global/multi-tenancy not enabled.
     */
    selectedTenantId: Number,

    /**
     *  Customizable tenant settings
     */
    customizableTenantSettings: {
      type: [Array, String],
      required: false,
    },
  },

  data() {
    return {
      initialValues: {
        formcustom_field_customcss: {
          value: '',
          type: 'text',
        },
        formcustom_field_customfooter: {
          value: '',
          type: 'text',
        },
      },
      initialValuesSet: false,
      theme_settings: theme_settings,
      errorsForm: null,
      valuesForm: null,
      resultForm: null,
    };
  },

  computed: {
    customFooterEditable() {
      return this.canEditSetting('formcustom_field_customfooter');
    },

    customCssEditable() {
      return this.canEditSetting('formcustom_field_customcss');
    },
  },

  /**
   * Prepare data for consumption within Uniform
   **/
  mounted() {
    // Set the data for this Form based on (in order):
    // - use previously saved Form data from GraphQL query
    // - missing field data then supplied by Theme JSON mapping data
    // - then locally held state until (takes precedence until page is reloaded)
    let mergedFormData = this.theme_settings.mergeFormData(this.initialValues, [
      this.savedFormFieldData,
      this.valuesForm || [],
    ]);
    this.initialValues = this.theme_settings.getResolvedInitialValues(
      mergedFormData
    );
    this.initialValuesSet = true;
    this.$emit('mounted', { category: 'custom', values: this.initialValues });
  },

  methods: {
    handleChange(values) {
      this.valuesForm = values;
      if (this.errorsForm) {
        this.errorsForm = null;
      }
    },

    /**
     * Check whether the specific setting can be customized
     * @param {String} key
     * @return {Boolean}
     */
    canEditSetting(key) {
      if (!this.selectedTenantId) {
        return true;
      }

      if (!this.customizableTenantSettings) {
        return false;
      }

      if (Array.isArray(this.customizableTenantSettings)) {
        return this.customizableTenantSettings.includes(key);
      }

      return this.customizableTenantSettings === '*';
    },

    /**
     * Adjust the height of a textarea field as the user types, up to
     * a supplied limit, which then invokes a scrollbar
     **/
    rows(field, minLines, maxLines) {
      let text = '';
      if (this.valuesForm && field in this.valuesForm) {
        text = this.valuesForm[field].value;
      } else if (this.initialValues && field in this.initialValues) {
        text = this.initialValues[field].value;
      }
      let lines = (text.match(/\n/g) || []).length + 1;
      if (lines < minLines) {
        return minLines;
      }
      if (lines > maxLines) {
        return maxLines;
      }
      return lines;
    },

    /**
     * Handle submission of an embedded form.
     *
     * @param {Object} currentValues The submitted form data.
     */
    submit(currentValues) {
      if (this.errorsForm) {
        this.errorsForm = null;
      }
      this.resultForm = currentValues;

      let dataToMutate = this.formatDataForMutation(currentValues);
      this.$emit('submit', dataToMutate);
    },

    /**
     * Takes Form field data and formats it to meet GraphQL mutation expectations
     *
     * @param {Object} currentValues The submitted form data.
     * @return {Object}
     **/
    formatDataForMutation(currentValues) {
      let data = {
        form: 'custom',
        fields: [],
      };

      Object.keys(currentValues).forEach(field => {
        if (!this.canEditSetting(field)) return;
        data.fields.push({
          name: field,
          type: currentValues[field].type,
          value: String(currentValues[field].value),
        });
      });

      return data;
    },
  },
};
</script>

<lang-strings>
{
  "totara_tui": [
    "formcustom_label_customcss",
    "formcustom_details_customcss",
    "formcustom_label_customfooter",
    "formcustom_details_customfooter",
    "tabcustom"
  ],
  "totara_core": [
    "save",
    "saveextended",
    "settings"
  ]
}
</lang-strings>
