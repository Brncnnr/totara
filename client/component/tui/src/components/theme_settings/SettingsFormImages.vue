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
    :validate="validate"
    @change="handleChange"
    @submit="submit"
  >
    <FormRowStack spacing="large">
      <Collapsible
        :label="$str('formimages_group_core', 'totara_tui')"
        :initial-state="true"
      >
        <FormRowStack spacing="large">
          <FormRow
            :label="$str('formimages_label_displaylogin', 'totara_tui')"
            :is-stacked="true"
            :aria-describedby="$id('formimages-displaylogin-defaults')"
          >
            <FormCheckbox :name="['formimages_field_displaylogin', 'value']" />
            <FormRowDefaults :id="$id('formimages-displaylogin-defaults')">{{
              $str('enabled', 'totara_core')
            }}</FormRowDefaults>
          </FormRow>

          <FormRow
            v-if="loginEditable"
            :label="$str('formimages_label_login', 'totara_tui')"
            :is-stacked="true"
          >
            <ImageUploadSetting
              :key="key"
              :metadata="fileData.sitelogin"
              :aria-label-extension="
                $str(
                  'defaultimage',
                  'totara_tui',
                  $str('formimages_label_login', 'totara_tui')
                )
              "
              :aria-describedby="$id('formimages-login-details')"
              :context-id="contextId"
              :show-delete="showDelete(fileData.sitelogin)"
              @update="saveImage"
              @delete="resetImage"
            />
            <FormRowDetails :id="$id('formimages-login-details')">
              {{ $str('formimages_details_login', 'totara_tui') }}
            </FormRowDetails>
          </FormRow>

          <FormRow
            v-if="loginEditable"
            :label="$str('formimages_label_loginalttext', 'totara_tui')"
            :is-stacked="true"
            :aria-describedby="$id('formimages-loginalttext-details')"
          >
            <FormText :name="['formimages_field_loginalttext', 'value']" />
            <FormRowDetails :id="$id('formimages-loginalttext-details')">
              {{ $str('formimages_details_loginalttext', 'totara_tui') }}
            </FormRowDetails>
          </FormRow>
        </FormRowStack>
      </Collapsible>

      <Collapsible
        v-if="canEditLearnImages"
        :label="$str('formimages_group_learn', 'totara_tui')"
        :initial-state="true"
      >
        <FormRowStack spacing="large">
          <FormRow
            v-if="learnCourseEditable"
            :label="$str('formimages_label_course', 'totara_tui')"
            :is-stacked="true"
          >
            <ImageUploadSetting
              :key="key"
              :metadata="fileData.learncourse"
              :aria-label-extension="
                $str(
                  'defaultimage',
                  'totara_tui',
                  $str('formimages_label_course', 'totara_tui')
                )
              "
              :aria-describedby="$id('formimages-course-details')"
              :context-id="contextId"
              :show-delete="showDelete(fileData.learncourse)"
              @update="saveImage"
              @delete="resetImage"
            />
            <FormRowDetails :id="$id('formimages-course-details')">
              {{ $str('formimages_details_course', 'totara_tui') }}
            </FormRowDetails>
          </FormRow>
          <FormRow
            v-if="fileData.learnprogram && learnProgramEditable"
            :label="$str('formimages_label_program', 'totara_tui')"
            :is-stacked="true"
          >
            <ImageUploadSetting
              :key="key"
              :metadata="fileData.learnprogram"
              :aria-label-extension="
                $str(
                  'defaultimage',
                  'totara_tui',
                  $str('formimages_label_program', 'totara_tui')
                )
              "
              :aria-describedby="$id('formimages-program-details')"
              :context-id="contextId"
              :show-delete="showDelete(fileData.learnprogram)"
              @update="saveImage"
              @delete="resetImage"
            />
            <FormRowDetails :id="$id('formimages-program-details')">
              {{ $str('formimages_details_program', 'totara_tui') }}
            </FormRowDetails>
          </FormRow>
          <FormRow
            v-if="fileData.learncert && learnCertEditable"
            :label="$str('formimages_label_cert', 'totara_tui')"
            :is-stacked="true"
          >
            <ImageUploadSetting
              :key="key"
              :metadata="fileData.learncert"
              :aria-label-extension="
                $str(
                  'defaultimage',
                  'totara_tui',
                  $str('formimages_label_cert', 'totara_tui')
                )
              "
              :aria-describedby="$id('formimages-cert-details')"
              :context-id="contextId"
              :show-delete="showDelete(fileData.learncert)"
              @update="saveImage"
              @delete="resetImage"
            />
            <FormRowDetails :id="$id('formimages-cert-details')">
              {{ $str('formimages_details_cert', 'totara_tui') }}
            </FormRowDetails>
          </FormRow>
        </FormRowStack>
      </Collapsible>

      <Collapsible
        v-if="canEditEngageImages"
        :label="$str('formimages_group_engage', 'totara_tui')"
        :initial-state="true"
      >
        <FormRowStack>
          <FormRow
            v-if="fileData.engageresource && engageResourceEditable"
            :label="$str('formimages_label_resource', 'totara_tui')"
            :is-stacked="true"
          >
            <ImageUploadSetting
              :key="key"
              :metadata="fileData.engageresource"
              :aria-label-extension="
                $str(
                  'defaultimage',
                  'totara_tui',
                  $str('formimages_label_resource', 'totara_tui')
                )
              "
              :aria-describedby="$id('formimages-resource-details')"
              :context-id="contextId"
              :show-delete="showDelete(fileData.engageresource)"
              @update="saveImage"
              @delete="resetImage"
            />
            <FormRowDetails :id="$id('formimages-resource-details')">
              {{ $str('formimages_details_resource', 'totara_tui') }}
            </FormRowDetails>
          </FormRow>
          <FormRow
            v-if="fileData.engageworkspace && engageWorkspaceEditable"
            :label="$str('formimages_label_workspace', 'totara_tui')"
            :is-stacked="true"
          >
            <ImageUploadSetting
              :key="key"
              :metadata="fileData.engageworkspace"
              :aria-label-extension="
                $str(
                  'defaultimage',
                  'totara_tui',
                  $str('formimages_label_workspace', 'totara_tui')
                )
              "
              :aria-describedby="$id('formimages-workspace-details')"
              :context-id="contextId"
              :show-delete="showDelete(fileData.engageworkspace)"
              @update="saveImage"
              @delete="resetImage"
            />
            <FormRowDetails :id="$id('formimages-workspace-details')">
              {{ $str('formimages_details_workspace', 'totara_tui') }}
            </FormRowDetails>
          </FormRow>
        </FormRowStack>
      </Collapsible>

      <FormRow>
        <ButtonGroup>
          <Button
            :styleclass="{ primary: 'true' }"
            :text="$str('save', 'totara_core')"
            :aria-label="
              $str(
                'saveextended',
                'totara_core',
                $str('tabimages', 'totara_tui') +
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
import Collapsible from 'tui/components/collapsible/Collapsible';
import {
  Uniform,
  FormRow,
  FormCheckbox,
  FormText,
} from 'tui/components/uniform';
import ImageUploadSetting from 'tui/components/theme_settings/ImageUploadSetting';
import FormRowDetails from 'tui/components/form/FormRowDetails';
import FormRowDefaults from 'tui/components/form/FormRowDefaults';
import FormRowStack from 'tui/components/form/FormRowStack';
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';

// Mixins
import FileMixin from 'tui/mixins/settings_form_file_mixin';

export default {
  components: {
    Collapsible,
    Uniform,
    FormRow,
    FormRowDetails,
    FormRowDefaults,
    FormCheckbox,
    FormText,
    ImageUploadSetting,
    FormRowStack,
    Button,
    ButtonGroup,
  },

  mixins: [FileMixin],

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
     * Object with keys present for each 'Flavour' of Totara possible on the
     * site, each key value is a Boolean representing whether that Flavour is
     * currently enabled. We use this to determine whether to show various
     * settings related to a given Flavour
     */
    flavoursData: {
      type: Object,
      default: function() {
        return {};
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
     * Context ID.
     */
    contextId: [Number, String],

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
        formimages_field_displaylogin: {
          value: true,
          type: 'boolean',
        },
        formimages_field_loginalttext: {
          value: '',
          type: 'text',
        },
      },
      fileData: {
        sitelogin: null,
        learncourse: null,
        learnprogram: null,
        learncert: null,
        engageresource: null,
        engageworkspace: null,
      },
      initialValuesSet: false,
      errorsForm: null,
      valuesForm: null,
      resultForm: null,
      theme_settings: theme_settings,
    };
  },

  computed: {
    loginEditable() {
      return this.canEditImage('sitelogin');
    },

    learnCourseEditable() {
      return this.canEditImage('learncourse');
    },

    learnProgramEditable() {
      return this.canEditImage('learnprogram');
    },

    learnCertEditable() {
      return this.canEditImage('learncert');
    },

    engageResourceEditable() {
      return this.canEditImage('engageresource');
    },

    engageWorkspaceEditable() {
      return this.canEditImage('engageworkspace');
    },

    canEditLearnImages() {
      return (
        this.flavoursData.learn &&
        (this.learnCourseEditable ||
          this.learnProgramEditable ||
          this.learnCertEditable)
      );
    },

    canEditEngageImages() {
      return (
        this.flavoursData.engage &&
        (this.fileData.engageresource || this.fileData.engageworkspace) &&
        (!this.selectedTenantId ||
          (this.selectedTenantId && this.engageResourceEditable) ||
          this.engageWorkspaceEditable)
      );
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
    this.$emit('mounted', { category: 'images', values: this.initialValues });
  },

  methods: {
    validate() {
      const errors = {};
      return errors;
    },

    handleChange(values) {
      this.valuesForm = values;
      if (this.errorsForm) {
        this.errorsForm = null;
      }
    },

    /**
     * Check whether the specific image can be customized
     * @param {String} key
     * @return {Boolean}
     */
    canEditImage(key) {
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
        form: 'images',
        fields: [],
        files: [],
      };

      // handle non-image upload form fields
      Object.keys(currentValues).forEach(field => {
        data.fields.push({
          name: field,
          type: currentValues[field].type,
          value: String(currentValues[field].value),
        });
      });

      // image upload form field data formatting as it is handled
      // differently to other form fields in our GraphQL mutation
      Object.keys(this.fileData).forEach(file => {
        if (this.fileData[file]) {
          data.files.push(this.fileData[file]);
        }
      });

      return data;
    },
  },
};
</script>

<lang-strings>
{
  "totara_tui": [
    "defaultimage",
    "form_details_default",
    "formimages_group_core",
    "formimages_label_displaylogin",
    "formimages_label_login",
    "formimages_details_login",
    "formimages_label_loginalttext",
    "formimages_details_loginalttext",
    "formimages_group_learn",
    "formimages_label_course",
    "formimages_details_course",
    "formimages_label_program",
    "formimages_details_program",
    "formimages_label_cert",
    "formimages_details_cert",
    "formimages_group_engage",
    "formimages_label_resource",
    "formimages_details_resource",
    "formimages_label_workspace",
    "formimages_details_workspace",
    "tabimages"
  ],
  "totara_core": [
    "save",
    "saveextended",
    "settings",
    "enabled"
  ]
}
</lang-strings>
