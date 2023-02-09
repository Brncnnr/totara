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
      <FormRow
        v-if="logoEditable"
        :label="$str('formbrand_label_logo', 'totara_tui')"
        :is-stacked="true"
      >
        <ImageUploadSetting
          :key="key"
          :metadata="fileData.sitelogo"
          :aria-describedby="$id('formbrand-logo-details')"
          :aria-label-extension="$str('formbrand_label_logo', 'totara_tui')"
          :context-id="contextId"
          :show-delete="showDelete(fileData.sitelogo)"
          @update="saveImage"
          @delete="resetImage"
        />
        <FormRowDetails :id="$id('formbrand-logo-details')">
          {{ $str('formbrand_details_logo', 'totara_tui') }}
        </FormRowDetails>
      </FormRow>

      <!-- Not allowing alt text change if image can't be changed -->
      <FormRow
        v-if="logoEditable"
        :label="$str('formbrand_label_logoalttext', 'totara_tui')"
        :is-stacked="true"
        :aria-describedby="$id('formbrand-logoalttext-details')"
      >
        <FormText :name="['formbrand_field_logoalttext', 'value']" required />
        <FormRowDetails :id="$id('formbrand-logoalttext-details')">
          {{ $str('formbrand_details_logoalttext', 'totara_tui') }}
        </FormRowDetails>
      </FormRow>

      <FormRow
        v-if="faviconEditable"
        :label="$str('formbrand_label_favicon', 'totara_tui')"
        :is-stacked="true"
      >
        <ImageUploadSetting
          :key="key"
          :metadata="fileData.sitefavicon"
          :aria-describedby="$id('formbrand-favicon-details')"
          :aria-label-extension="$str('formbrand_label_favicon', 'totara_tui')"
          :context-id="contextId"
          :show-delete="showDelete(fileData.sitefavicon)"
          @update="saveImage"
          @delete="resetImage"
        />
        <FormRowDetails :id="$id('formbrand-favicon-details')">
          {{ $str('formbrand_details_favicon', 'totara_tui') }}
        </FormRowDetails>
      </FormRow>
    </FormRowStack>
    <FormRowStack spacing="large">
      <Collapsible
        :label="$str('formbrand_group_notifications', 'totara_tui')"
        :initial-state="false"
      >
        <FormRowStack spacing="large">
          <FormRow
            v-slot="{ id }"
            :label="
              $str('formbrand_label_notificationshtmlheader', 'totara_tui')
            "
            :is-stacked="true"
          >
            <FormField
              v-slot="{ value, update, labelId }"
              :name="['formbrand_field_notificationshtmlheader', 'value']"
              char-length="full"
            >
              <Editor
                :id="id"
                :value="value"
                :default-format="htmlFormat"
                :context-id="contextIdNumber"
                :usage-identifier="{
                  component: 'totara_tui',
                  area: 'formbrand_notifications_htmlheader',
                }"
                :aria-describedby="
                  $id('formbrand-notifications-htmlheader-details')
                "
                :aria-labelledby="labelId"
                variant="standard"
                :lock-format="true"
                @input="update"
              />
            </FormField>
            <FormRowDetails
              :id="$id('formbrand-notifications-htmlheader-details')"
            >
              {{
                $str('formbrand_details_notificationshtmlheader', 'totara_tui')
              }}
            </FormRowDetails>
          </FormRow>

          <FormRow
            v-slot="{ id }"
            :label="
              $str('formbrand_label_notificationshtmlfooter', 'totara_tui')
            "
          >
            <FormField
              v-slot="{ value, update, labelId }"
              :name="['formbrand_field_notificationshtmlfooter', 'value']"
              char-length="full"
            >
              <Editor
                :id="id"
                :value="value"
                :default-format="htmlFormat"
                :context-id="contextIdNumber"
                :usage-identifier="{
                  component: 'totara_tui',
                  area: 'formbrand_notifications_htmlfooter',
                }"
                :aria-describedby="
                  $id('formbrand-notifications-htmlfooter-details')
                "
                :aria-labelledby="labelId"
                variant="standard"
                :lock-format="true"
                @input="update"
              />
            </FormField>
            <FormRowDetails
              :id="$id('formbrand-notifications-htmlfooter-details')"
            >
              {{
                $str('formbrand_details_notificationshtmlfooter', 'totara_tui')
              }}
            </FormRowDetails>
          </FormRow>

          <FormRow
            :label="
              $str('formbrand_label_notificationstextfooter', 'totara_tui')
            "
            :is-stacked="true"
            :aria-describedby="
              $id('formbrand-notifications-textfooter-details')
            "
            :aria-label="
              $str('formbrand_label_notificationstextfooter', 'totara_tui')
            "
          >
            <FormTextarea
              :name="['formbrand_field_notificationstextfooter', 'value']"
              spellcheck="false"
              :rows="rows('formbrand_field_notificationstextfooter', 8, 30)"
              char-length="full"
            />
            <FormRowDetails
              :id="$id('formbrand-notifications-textfooter-details')"
            >
              {{
                $str('formbrand_details_notificationstextfooter', 'totara_tui')
              }}
            </FormRowDetails>
          </FormRow>
          <FormRow>
            <InputSet>
              <Button
                :styleclass="{ primary: false }"
                :text="$str('test_email_notification', 'totara_core')"
                :disabled="isSending"
                @click="sendEmailNotification"
              />
              <InfoIconButton
                class="tui-settingsFormBrand__testEmailInfoButton"
                :is-help-for="$str('test_email_notification', 'totara_core')"
              >
                {{ $str('test_email_notification_help', 'totara_core') }}
              </InfoIconButton>
            </InputSet>
          </FormRow>
        </FormRowStack>
      </Collapsible>

      <FormRow>
        <ButtonGroup>
          <Button
            :styleclass="{ primary: true }"
            :text="$str('save', 'totara_core')"
            :aria-label="
              $str(
                'saveextended',
                'totara_core',
                $str('tabbrand', 'totara_tui')
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
  FormText,
  FormField,
  FormTextarea,
} from 'tui/components/uniform';
import ImageUploadSetting from 'tui/components/theme_settings/ImageUploadSetting';
import FormRowDetails from 'tui/components/form/FormRowDetails';
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import Editor from 'tui/components/editor/Editor';
import { Format } from 'tui/editor';
import InfoIconButton from 'tui/components/buttons/InfoIconButton';
import Collapsible from 'tui/components/collapsible/Collapsible';
import { notify } from 'tui/notifications';
import InputSet from 'tui/components/form/InputSet';

// GraphQL
import tuiSendEmailNotification from 'core/graphql/theme_settings_send_email_notification';

// Mixins
import FileMixin from 'tui/mixins/settings_form_file_mixin';

export default {
  components: {
    Uniform,
    FormRow,
    FormRowStack,
    FormRowDetails,
    FormText,
    ImageUploadSetting,
    Button,
    ButtonGroup,
    FormTextarea,
    Editor,
    InfoIconButton,
    FormField,
    Collapsible,
    InputSet,
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
     *  Saving state, controlled by parent component GraphQl mutation handling
     */
    isSaving: {
      type: Boolean,
      default: function() {
        return false;
      },
    },

    /**
     *  Context ID.
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
        formbrand_field_logoalttext: {
          value: null,
          type: 'text',
        },
        formbrand_field_notificationshtmlheader: {
          value: '',
          type: 'html',
        },
        formbrand_field_notificationshtmlfooter: {
          value: '',
          type: 'html',
        },
        formbrand_field_notificationstextfooter: {
          value: '',
          type: 'text',
        },
      },
      editorFields: {
        formbrand_field_notificationshtmlheader: {
          format: Format.HTML,
        },
        formbrand_field_notificationshtmlfooter: {
          format: Format.HTML,
        },
      },
      fileData: {
        sitefavicon: null,
        sitelogo: null,
      },
      initialValuesSet: false,
      errorsForm: null,
      valuesForm: null,
      resultForm: null,
      theme_settings: theme_settings,
      isSending: false,
      htmlFormat: Format.HTML,
      contextIdNumber: parseInt(this.contextId),
    };
  },

  computed: {
    logoEditable() {
      return this.canEditImage('sitelogo');
    },
    faviconEditable() {
      return this.canEditImage('sitefavicon');
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
    this.initialValues = this.theme_settings.resolveEditorContentFields(
      this.initialValues,
      this.editorFields
    );
    this.initialValuesSet = true;
    this.$emit('mounted', {
      category: 'brand',
      values: this.formatDataForMutation(this.initialValues),
    });
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
        form: 'brand',
        fields: [],
        files: [],
      };

      // handle non-image upload form fields
      Object.keys(currentValues).forEach(field => {
        let value;
        if (
          Object.keys(this.editorFields).find(
            editorField => editorField === field
          )
        ) {
          value = currentValues[field].value.getContent();
          value = value ? value : '';
        } else {
          value = String(currentValues[field].value);
        }
        data.fields.push({
          name: field,
          type: currentValues[field].type,
          value: value,
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

    async sendEmailNotification() {
      this.isSending = true;
      const values = this.valuesForm || this.initialValues;

      try {
        const { data } = await this.$apollo.mutate({
          mutation: tuiSendEmailNotification,
          variables: {
            html_header: values[
              'formbrand_field_notificationshtmlheader'
            ].value.getContent(),
            html_footer: values[
              'formbrand_field_notificationshtmlfooter'
            ].value.getContent(),
            text_footer:
              values['formbrand_field_notificationstextfooter'].value,
            tenant_id: this.selectedTenantId,
          },
        });

        if (data['core_theme_settings_send_email_notification']) {
          notify({
            message: this.$str('settings_email_send_success', 'totara_tui'),
            type: 'success',
          });
        } else {
          notify({
            message: this.$str('settings_email_send_error', 'totara_tui'),
            type: 'error',
          });
        }
      } catch (e) {
        notify({
          message: this.$str('settings_email_send_error', 'totara_tui'),
          type: 'error',
        });
      }
      this.isSending = false;
    },
  },
};
</script>

<lang-strings>
{
  "totara_tui": [
    "form_details_default",
    "formbrand_label_logo",
    "formbrand_details_logo",
    "formbrand_label_logoalttext",
    "formbrand_details_logoalttext",
    "formbrand_label_favicon",
    "formbrand_details_favicon",
    "formbrand_group_notifications",
    "formbrand_label_notificationshtmlheader",
    "formbrand_label_notificationshtmlfooter",
    "formbrand_label_notificationstextfooter",
    "formbrand_details_notificationshtmlheader",
    "formbrand_details_notificationshtmlfooter",
    "formbrand_details_notificationstextfooter",
    "tabbrand",
    "settings_email_send_success",
    "settings_email_send_error"
  ],
  "totara_core": [
    "save",
    "saveextended",
    "settings",
    "enabled",
    "test_email_notification",
    "test_email_notification_help"
  ]
}
</lang-strings>

<style lang="scss">
.tui-settingsFormBrand__testEmailInfoButton {
  align-self: center;
}
</style>
