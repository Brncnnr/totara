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

  @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
  @author Dave Wallace <dave.wallace@totaralearning.com>
  @module tui
-->

<template>
  <div class="tui-themeSettings">
    <PageHeading :title="pageTitle" />
    <div class="tui-themeSettings__content">
      <Loader :loading="!allDataLoaded">
        <div
          v-if="dataIsReady && embeddedFormData.formFieldData.brand"
          class="tui-themeSettings__forms"
        >
          <Uniform
            v-if="initialValues && selectedTenantId"
            :initial-values="initialValues"
            :errors="errorsForm"
            :validate="validate"
            @change="autoSubmitTenantForm"
          >
            <FormRow
              v-slot="{ label }"
              :label="$str('formtenant_label_tenant', 'totara_tui')"
              :is-stacked="true"
            >
              <FormToggleSwitch
                :name="['formtenant_field_tenant', 'value']"
                :toggle-first="true"
                :aria-label="label"
                :aria-describedby="$id('tenant')"
              />
              <FormRowDetails :id="$id('tenant')">
                {{ $str('formtenant_details_tenant', 'totara_tui') }}
              </FormRowDetails>
            </FormRow>
          </Uniform>

          <Tabs
            v-show="
              !selectedTenantId || (selectedTenantId && tenantOverridesEnabled)
            "
            selected="themesettings-tab-0"
            content-spacing="large"
          >
            <Tab
              v-if="canEditCategory('brand')"
              :id="'themesettings-tab-0'"
              :name="$str('tabbrand', 'totara_tui')"
              :always-render="true"
            >
              <SettingsFormBrand
                v-if="embeddedFormData.formFieldData.brand"
                :saved-form-field-data="embeddedFormData.formFieldData.brand"
                :file-form-field-data="embeddedFormData.fileData"
                :is-saving="isSaving"
                :context-id="embeddedFormData.contextId"
                :selected-tenant-id="selectedTenantId"
                :customizable-tenant-settings="
                  customizableTenantCategorySettings('brand')
                "
                @mounted="setInitialTenantCategoryValues"
                @submit="submit"
              />
            </Tab>
            <Tab
              v-if="canEditCategory('colours')"
              :id="'themesettings-tab-1'"
              :name="$str('tabcolours', 'totara_tui')"
              :always-render="true"
            >
              <SettingsFormColours
                v-if="embeddedFormData.formFieldData.colours"
                :saved-form-field-data="embeddedFormData.formFieldData.colours"
                :merged-default-css-variable-data="
                  embeddedFormData.mergedDefaultCSSVariableData
                "
                :merged-processed-css-variable-data="
                  embeddedFormData.mergedProcessedCSSVariableData
                "
                :is-saving="isSaving"
                :customizable-tenant-settings="
                  customizableTenantCategorySettings('colours')
                "
                @mounted="setInitialTenantCategoryValues"
                @submit="submit"
              />
            </Tab>
            <Tab
              v-if="canEditCategory('images')"
              :id="'themesettings-tab-2'"
              :name="$str('tabimages', 'totara_tui')"
              :always-render="true"
            >
              <SettingsFormImages
                v-if="embeddedFormData.formFieldData.images"
                :saved-form-field-data="embeddedFormData.formFieldData.images"
                :flavours-data="embeddedFormData.flavours"
                :file-form-field-data="embeddedFormData.fileData"
                :is-saving="isSaving"
                :context-id="embeddedFormData.contextId"
                :selected-tenant-id="selectedTenantId"
                :customizable-tenant-settings="
                  customizableTenantCategorySettings('images')
                "
                @mounted="setInitialTenantCategoryValues"
                @submit="submit"
              />
            </Tab>
            <Tab
              v-if="canEditCategory('custom')"
              :id="'themesettings-tab-3'"
              :name="$str('tabcustom', 'totara_tui')"
              :always-render="true"
              :disabled="!customCSSEnabled"
            >
              <SettingsFormCustom
                v-if="embeddedFormData.formFieldData.custom"
                :saved-form-field-data="embeddedFormData.formFieldData.custom"
                :is-saving="isSaving"
                :selected-tenant-id="selectedTenantId"
                :customizable-tenant-settings="
                  customizableTenantCategorySettings('custom')
                "
                @mounted="setInitialTenantCategoryValues"
                @submit="submit"
              />
            </Tab>
          </Tabs>
        </div>
      </Loader>
    </div>
  </div>
</template>

<script>
import Loader from 'tui/components/loading/Loader';
import Tab from 'tui/components/tabs/Tab';
import Tabs from 'tui/components/tabs/Tabs';
import { Uniform, FormRow, FormToggleSwitch } from 'tui/components/uniform';
import FormRowDetails from 'tui/components/form/FormRowDetails';
import PageHeading from 'tui/components/layouts/PageHeading';
import SettingsFormBrand from 'tui/components/theme_settings/SettingsFormBrand';
import SettingsFormColours from 'tui/components/theme_settings/SettingsFormColours';
import SettingsFormImages from 'tui/components/theme_settings/SettingsFormImages';
import SettingsFormCustom from 'tui/components/theme_settings/SettingsFormCustom';
import tuiQueryThemesWithVariables from 'totara_tui/graphql/themes_with_variables';
import tuiQueryThemeSettings from 'core/graphql/get_theme_settings';
import tuiUpdateThemeSettings from 'core/graphql/update_theme_settings';
import { notify } from 'tui/notifications';
import theme_settings from 'tui/lib/theme_settings';

export default {
  components: {
    Loader,
    Tabs,
    Tab,
    Uniform,
    FormRow,
    FormRowDetails,
    FormToggleSwitch,
    PageHeading,
    SettingsFormBrand,
    SettingsFormColours,
    SettingsFormImages,
    SettingsFormCustom,
  },

  props: {
    /**
     * Theme to change settings for.
     */
    theme: {
      type: String,
      required: true,
    },

    themeName: String,

    /**
     * Tenant ID or null if global/multi-tenancy not enabled.
     */
    selectedTenantId: Number,

    /**
     * Tenant Name or null if global/multi-tenancy not enabled.
     */
    selectedTenantName: String,

    /**
     * Customizable tenant settings
     */
    customizableTenantSettings: Object,
  },

  data() {
    return {
      tenantOverridesEnabled: false,
      query: tuiQueryThemeSettings,
      theme_settings: theme_settings,
      initialValues: {
        formtenant_field_tenant: {
          value: false,
          type: 'boolean',
        },
      },
      embeddedFormData: {
        flavours: null,
        formFieldData: {
          brand: [],
          colours: [],
          images: [],
          custom: [],
          tenant: [],
        },
        mergedDefaultCSSVariableData: [],
        fileData: [],
      },
      customCSSEnabled: true,
      errorsForm: null,
      valuesForm: null,
      resultForm: null,
      // data is merged and resolved
      dataIsReady: false,
      // mutation has executed
      isSaving: false,
      // raw CSS variable data, this is handled via fetch, not GraphQL
      rawCSSVariableData: null,
      // Categories to add when tenant is enabled
      tenantCategories: {},
    };
  },

  computed: {
    pageTitle() {
      if (!this.selectedTenantName) {
        // Editing current theme
        return this.$str(
          'edittheme',
          'totara_core',
          this.themeName || this.theme
        );
      } else {
        // Editing current Theme Settings, but for a Tenant
        return this.$str(
          'editthemetenant',
          'totara_core',
          this.selectedTenantName
        );
      }
    },
    allFetchesLoaded() {
      return !!this.rawCSSVariableData;
    },
    allQueriesLoaded() {
      return (
        !!this.core_get_theme_settings &&
        !!this.totara_tui_themes_with_variables
      );
    },
    allDataLoaded() {
      return (
        !!this.core_get_theme_settings &&
        !!this.totara_tui_themes_with_variables &&
        !!this.rawCSSVariableData
      );
    },
  },

  watch: {
    allQueriesLoaded(val) {
      if (val) {
        this.loadCSSVariableData();
      }
    },
    allDataLoaded() {
      this.transformQueryAndFetchData(
        this.core_get_theme_settings,
        this.rawCSSVariableData
      );
      this.setTenantFormValues();
    },
  },

  methods: {
    /**
     * Prepare data for consumption within this component's Uniform
     **/
    setTenantFormValues() {
      let mergedFormData = this.theme_settings.mergeFormData(
        this.initialValues,
        [this.embeddedFormData.formFieldData.tenant]
      );
      this.initialValues = this.theme_settings.getResolvedInitialValues(
        mergedFormData
      );

      // we some data properties to be reactive, based off query data or initial
      // form values
      this.tenantOverridesEnabled = this.initialValues.formtenant_field_tenant.value;
    },

    /**
     * Fetches JSON CSS Variable mappings from multiple TUI-based themes in the
     * inheritance chain.
     **/
    async loadCSSVariableData() {
      let result = await Promise.all(
        this.totara_tui_themes_with_variables.map(async theme => {
          const response = await fetch(
            this.$url('/totara/tui/json.php', {
              bundle: 'theme_' + theme,
              file: 'css_variables',
            })
          );
          const data = await response.json();
          if (!data) {
            console.error(
              `[ThemeSettings] Configuration error: 'css_variables' file is missing for the selected theme.` +
                `Please follow the TUI build instructions in order to generate the required file.`
            );
            return {};
          }
          return data.vars;
        })
      );
      this.rawCSSVariableData = result.filter(response => response !== null);
    },

    /**
     * Performs several transforms on data coming from multiple sources, this
     * transformed data is then passed as props to embedded Uniform components
     *
     * @param {object} queryData // GraphQL query data
     * @param {array} fetchData // JSON fetch CSS variable data from TUI Themes
     **/
    transformQueryAndFetchData(queryData, fetchData) {
      // embedded Forms need to know what "Flavours" of Totara are enabled, to
      // deteermine whether we render settings for those Flavours
      this.embeddedFormData.flavours = queryData.flavours;

      // separate file upload data needs merging into the generic form field
      // Object mapping, GraphQL query needed to be structured differently
      // from basic form field data. We'll pass this into Forms that need file
      // uploading
      this.embeddedFormData.fileData = queryData.files;

      // file uploading needs to know the context of the user otherwise access
      // will be blocked when trying to access system context when not admin.
      this.embeddedFormData.contextId = queryData.context_id;

      // previously saved, non-default Form data to be used as initialValues
      // within each embedded Form
      const unresolvedFormData = queryData.categories;
      for (let i = 0; i < unresolvedFormData.length; i++) {
        this.embeddedFormData.formFieldData[unresolvedFormData[i].name] =
          unresolvedFormData[i].properties;
      }

      // merge all theme CSS variable data in the theme inheritance chain
      if (fetchData) {
        let mergedDefaultThemeVariableData = this.theme_settings.mergeCSSVariableData(
          fetchData
        );

        let mergedProcessedCSSVariableData = this.theme_settings.processCSSVariableData(
          mergedDefaultThemeVariableData
        );

        // finally set the merged and value-resolved CSS Variable data, ready for
        // passing as a prop to Forms that need it
        this.embeddedFormData.mergedDefaultCSSVariableData = mergedDefaultThemeVariableData;
        this.embeddedFormData.mergedProcessedCSSVariableData = mergedProcessedCSSVariableData;
      }

      // we're ready to go
      this.dataIsReady = true;

      return;
    },

    validate() {
      const errors = {};
      return errors;
    },

    /**
     * Check whether the specific category can be customized
     *
     * @param {String} category
     * @return {Boolean}
     */
    canEditCategory(category) {
      return (
        !this.selectedTenantId ||
        (this.customizableTenantSettings &&
          !!this.customizableTenantSettings[category])
      );
    },

    /**
     * Return customizable settings in a specific category
     *
     * @param {String} category
     * @return {String|Object|null}
     */
    customizableTenantCategorySettings(category) {
      if (
        !this.customizableTenantSettings ||
        !this.customizableTenantSettings[category]
      ) {
        return null;
      }

      return this.customizableTenantSettings[category];
    },

    /**
     * Takes Form field data and formats it to meet GraphQL mutation expectations
     *
     * @param {Object} currentValues The submitted form data.
     * @return {Object}
     */
    formatDataForMutation(currentValues) {
      let data = {
        form: 'tenant',
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

      return data;
    },

    /**
     * Handle immediate submission of the single-field Tenant Form in this
     * component.
     *
     * @param {Object} currentValues The submitted form data.
     */
    autoSubmitTenantForm(currentValues) {
      this.valuesForm = currentValues;
      if (this.errorsForm) {
        this.errorsForm = null;
      }
      this.resultForm = currentValues;

      // update form-based reactive toggles
      if (typeof currentValues.formtenant_field_tenant !== 'undefined') {
        this.tenantOverridesEnabled =
          currentValues.formtenant_field_tenant.value;
      }

      // If enabling custom tenant override, we also pass brand and colours
      let dataToMutate = this.formatDataForMutation(currentValues);
      this.submit(
        dataToMutate,
        this.selectedTenantId && this.tenantOverridesEnabled
      );
    },

    /**
     * Set initial tenant category data to add when custom configuration is enabled
     *
     * @param {Object} categoryData
     */
    setInitialTenantCategoryValues(categoryData) {
      let category = categoryData.category;

      if (
        this.customizableTenantSettings &&
        !!this.customizableTenantSettings[category]
      ) {
        this.tenantCategories[category] = categoryData.values;
      }
    },

    /**
     * Handle submission of either top level or embedded forms. Each submission
     * sends in only its related form data, to replace only that chunk during
     * the mutation.
     *
     * @param {Object} payload The submitted form data expressed in full data
     *                          structure expected by mutation.
     * @param {Boolean} addTenantDefaults
     */
    async submit(payload, addTenantDefaults) {
      let categoryData = [
        {
          name: payload.form,
          properties: payload.fields,
        },
      ];

      if (addTenantDefaults) {
        Object.keys(this.tenantCategories).forEach(function(category) {
          if (this.tenantCategories[category].form) {
            categoryData.push({
              name: this.tenantCategories[category].form,
              properties: this.tenantCategories[category].fields,
            });
          }
        }, this);
      }

      let fileData = [];
      if (payload.files) {
        fileData = payload.files.map(file => {
          return {
            ui_key: file.ui_key,
            draft_id: file.file_area.draft_id,
            action: file.action || 'SAVE',
          };
        });
      }

      this.isSaving = true;

      try {
        await this.$apollo.mutate({
          mutation: tuiUpdateThemeSettings,
          variables: {
            theme: this.theme,
            tenant_id: this.selectedTenantId,
            categories: categoryData,
            files: fileData,
          },
          update: (cache, { data }) => {
            cache.writeQuery({
              query: tuiQueryThemeSettings,
              variables: {
                theme: this.theme,
                tenant_id: this.selectedTenantId,
              },
              data: {
                core_get_theme_settings: data.core_update_theme_settings,
              },
            });
            this.transformQueryAndFetchData(
              data.core_update_theme_settings,
              null
            );
          },
        });

        notify({
          message: this.$str('settings_success_save', 'totara_tui'),
          type: 'success',
        });
      } catch (e) {
        notify({
          message: this.$str('settings_error_save', 'totara_tui'),
          type: 'error',
        });
        console.error(e);
      }

      this.isSaving = false;
    },
  },

  apollo: {
    core_get_theme_settings: {
      query: tuiQueryThemeSettings,
      variables() {
        return {
          theme: this.theme,
          tenant_id: this.selectedTenantId,
        };
      },
    },
    totara_tui_themes_with_variables: {
      query: tuiQueryThemesWithVariables,
      variables() {
        return {
          theme: this.theme,
        };
      },
    },
  },
};
</script>

<lang-strings>
{
  "totara_tui": [
    "formtenant_label_tenant",
    "formtenant_details_tenant",
    "settings_error_save",
    "settings_success_save",
    "tabbrand",
    "tabcolours",
    "tabimages",
    "tabcustom"
  ],
  "totara_core": [
    "edittheme",
    "editthemetenant"
  ]
}
</lang-strings>

<style lang="scss">
.tui-themeSettings {
  &__content {
    margin-top: var(--gap-8);
  }

  &__forms {
    @include tui-stack-vertical(var(--gap-8));
  }
}
</style>
