<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Qingyang Liu <qingyang.liu@totaralearning.com>
  @author Jack Humphrey <jack.humphrey@totaralearning.com>
  @module totara_api
-->

<template>
  <Layout
    :title="$str('edit_client_settings', 'totara_api')"
    class="tui-totara_api-editClientSettings"
  >
    <template
      v-if="clientRateLimitExceedsGlobalSettings"
      v-slot:feedback-banner
    >
      <NotificationBanner
        type="warning"
        :message="$str('warning_client_rate_limit', 'totara_api')"
      />
    </template>

    <template v-slot:content>
      <Loader v-if="loading" :loading="loading" />
      <Uniform
        v-else
        ref="form"
        :initial-values="initialValues"
        @change="formValues = $event"
        @submit="editClientSettings"
      >
        <FormRow
          aria-hidden="true"
          class="tui-totara_api-editClientSettings__required"
          :vertical="true"
        >
          <span class="tui-totara_api-editClientSettings__requiredStar">
            *
          </span>
          {{ $str('required_fields', 'totara_api') }}
        </FormRow>

        <FormRow
          :label="$str('client_rate_limit', 'totara_api')"
          required
          :aria-describedby="
            `${$id('client-rate-limit-default')} ` +
              $id('client-rate-limit-details')
          "
        >
          <FormNumber
            name="clientRateLimit"
            :min="1"
            :max="2147483647"
            :validations="v => [v.required(), v.min(1), v.max(2147483647)]"
          />
          <FormRowDetails
            :id="$id('client-rate-limit-default')"
            class="tui-totara_api-editClientSettings__defaultInfo"
          >
            {{
              $str(
                'client_rate_limit_at_site_level',
                'totara_api',
                settings.global_settings.client_rate_limit
              )
            }}
          </FormRowDetails>
          <FormRowDetails :id="$id('client-rate-limit-details')">
            {{ $str('client_rate_limit_desc', 'totara_api') }}
          </FormRowDetails>
        </FormRow>

        <FormRow
          :label="$str('token_expiration', 'totara_api')"
          required
          :aria-describedby="
            `${$id('token-expiration-default')} ` +
              `${$id('token-expiration-details-1')} ` +
              $id('token-expiration-details-2')
          "
        >
          <FormDuration
            name="tokenExpiration"
            :aria-label="$str('token_expiration', 'totara_api')"
            :validations="v => [v.required(), minDuration(), maxDuration()]"
          />
          <FormRowDefaults :id="$id('token-expiration-default')">
            {{
              $str(
                'token_expiration_default',
                'totara_api',
                getDefaultTokenExpirationString
              )
            }}
          </FormRowDefaults>
          <FormRowDetails :id="$id('token-expiration-details-1')">
            {{ $str('token_expiration_desc_1', 'totara_api') }}
          </FormRowDetails>
          <FormRowDetails :id="$id('token-expiration-details-2')">
            {{ $str('token_expiration_desc_2', 'totara_api') }}
          </FormRowDetails>
        </FormRow>

        <FormRow
          :label="$str('error_response', 'totara_api')"
          required
          :aria-describedby="
            `${$id('error-response-default')} ` +
              `${$id('error-response-details-1')} ` +
              $id('error-response-details-2')
          "
        >
          <FormSelect
            :aria-label="$str('error_response', 'totara_api')"
            name="errorResponse"
            :options="errorResponseLevelOptions"
          />
          <FormRowDefaults :id="$id('error-response-default')">
            {{ defaultErrorResponseLevelString }}
          </FormRowDefaults>
          <FormRowDetails :id="$id('error-response-details-1')">
            {{ $str('error_response_desc_1', 'totara_api') }}
          </FormRowDetails>
          <FormRowDetails
            :id="$id('error-response-details-2')"
            v-html="$str('error_response_desc_2', 'totara_api')"
          />
        </FormRow>

        <FormRow>
          <ButtonGroup>
            <Button
              :styleclass="{ primary: 'true' }"
              :text="$str('save', 'totara_core')"
              :loading="isSaving"
              type="submit"
            />
            <ActionLink
              :disabled="isSaving"
              :href="getClientsPageUrl()"
              :text="$str('cancel', 'core')"
            />
          </ButtonGroup>
        </FormRow>
      </Uniform>
    </template>
  </Layout>
</template>

<script>
import Loader from 'tui/components/loading/Loader';
import ActionLink from 'tui/components/links/ActionLink';
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import FormRow from 'tui/components/form/FormRow';
import FormRowDefaults from 'tui/components/form/FormRowDefaults';
import FormRowDetails from 'tui/components/form/FormRowDetails';
import Layout from 'tui/components/layouts/LayoutOneColumn';
import NotificationBanner from 'tui/components/notifications/NotificationBanner';
import { FormNumber, Uniform, FormSelect } from 'tui/components/uniform';
import FormDuration from 'totara_api/components/uniform/FormDuration';
import { Units, parseSeconds } from 'totara_api/duration';
import { ErrorResponseLevel } from 'totara_api/error_response';

// GraphQL
import apiClientSettingsQuery from 'totara_api/graphql/client_settings';
import updateApiClientSettingsMutation from 'totara_api/graphql/update_client_settings';

export default {
  components: {
    Loader,
    ActionLink,
    Button,
    ButtonGroup,
    FormDuration,
    FormRow,
    FormRowDefaults,
    FormRowDetails,
    FormSelect,
    Layout,
    FormNumber,
    Uniform,
    NotificationBanner,
  },
  props: {
    clientId: {
      required: true,
      type: Number,
    },
    tenantId: Number,
  },

  data() {
    return {
      formValues: null,
      settings: {},
      isSaving: false,
      initialValues: null,
    };
  },

  computed: {
    loading() {
      return Boolean(!this.initialValues);
    },

    clientRateLimitExceedsGlobalSettings() {
      if (this.loading) {
        return false;
      }
      return (
        this.initialValues.clientRateLimit >
        this.settings.global_settings.client_rate_limit
      );
    },

    errorResponseLevelOptions() {
      return [
        {
          id: null,
          label: this.defaultErrorResponseLevelString,
        },
        {
          id: ErrorResponseLevel.NONE,
          label: this.getErrorResponseLevelString(ErrorResponseLevel.NONE),
        },
        {
          id: ErrorResponseLevel.NORMAL,
          label: this.getErrorResponseLevelString(ErrorResponseLevel.NORMAL),
        },
        {
          id: ErrorResponseLevel.DEVELOPER,
          label: this.getErrorResponseLevelString(ErrorResponseLevel.DEVELOPER),
        },
      ];
    },

    /**
     * @param {string} id
     */
    getDefaultTokenExpirationString() {
      const defaultTokenExpiration = parseSeconds(
        this.settings.global_settings.default_token_expiry_time
      );
      let unitsString;
      switch (defaultTokenExpiration.units) {
        case Units.WEEKS:
          unitsString = this.$str('duration_weeks', 'totara_api');
          break;
        case Units.DAYS:
          unitsString = this.$str('duration_days', 'totara_api');
          break;
        case Units.HOURS:
          unitsString = this.$str('duration_hours', 'totara_api');
          break;
        case Units.MINUTES:
          unitsString = this.$str('duration_minutes', 'totara_api');
          break;
        case Units.SECONDS:
          unitsString = this.$str('duration_seconds', 'totara_api');
          break;
      }
      return { value: defaultTokenExpiration.value, units: unitsString };
    },

    defaultErrorResponseLevelString() {
      let defaultErrorResponseLevel = this.settings.global_settings
        .response_debug;
      return this.$str(
        'error_response_level_default',
        'totara_api',
        this.getErrorResponseLevelString(defaultErrorResponseLevel)
      );
    },
  },

  apollo: {
    settings: {
      query: apiClientSettingsQuery,
      variables() {
        return {
          client_id: this.clientId,
        };
      },
      update({ settings }) {
        return settings;
      },
      result({ data }) {
        this.initialValues = {
          clientRateLimit: data.settings.client_settings.client_rate_limit,
          tokenExpiration:
            data.settings.client_settings.default_token_expiry_time,
          errorResponse: data.settings.client_settings.response_debug,
        };
      },
    },
  },

  methods: {
    /**
     *
     * @param {object} formValue
     */
    async editClientSettings(formValue) {
      this.isSaving = true;
      try {
        await this.$apollo.mutate({
          mutation: updateApiClientSettingsMutation,
          variables: {
            input: {
              client_id: this.settings.client_settings.id,
              client_rate_limit: parseInt(formValue.clientRateLimit),
              default_token_expiry_time: parseInt(formValue.tokenExpiration),
              response_debug: formValue.errorResponse,
            },
          },
        });

        document.location.href = this.getClientsPageUrl({
          edit_success: 1,
          expand_client: this.settings.client_settings.id,
        });
        window.addEventListener('pageshow', this.resetIsSaving);
      } catch (e) {
        this.isSaving = false;
        throw e;
      }
    },

    /**
     * Get clients list page URL
     * @param {object} params
     * @returns {string}
     */
    getClientsPageUrl(params) {
      params = { ...params };
      if (this.tenantId) {
        params.tenant_id = this.tenantId;
      }
      return this.$url('/totara/api/client/', params);
    },

    /**
     * Reset isSaving on navigation
     * @param {event} event
     */
    resetIsSaving(event) {
      if (event.persisted) {
        window.removeEventListener('pageshow', this.resetisSaving);
        this.isSaving = false;
      }
    },

    maxDuration() {
      const maxValue = 2147483647;
      return {
        validate: val => val <= maxValue,
        message: () =>
          this.$str(
            'error_validate_max_input_duration',
            'totara_api',
            maxValue
          ),
      };
    },

    minDuration() {
      return {
        validate: val => val >= 1,
        message: () =>
          this.$str('setting:default_token_expiration_invalid', 'totara_api'),
      };
    },

    /**
     * @param {string} level
     * @returns {string}
     */
    getErrorResponseLevelString(level) {
      switch (level) {
        case ErrorResponseLevel.NONE:
          return this.$str('none', 'totara_api');
        case ErrorResponseLevel.NORMAL:
          return this.$str('normal', 'totara_api');
        case ErrorResponseLevel.DEVELOPER:
          return this.$str('developer', 'totara_api');
      }
    },
  },
};
</script>
<lang-strings>
  {
    "totara_api": [
      "edit_client_settings",
      "required_fields",
      "client_rate_limit",
      "client_rate_limit_at_site_level",
      "client_rate_limit_desc",
      "error_response_desc_1",
      "error_response_desc_2",
      "error_response_level_default",
      "none",
      "normal",
      "developer",
      "error_validate_max_input_duration",
      "token_expiration",
      "token_expiration_default",
      "token_expiration_desc_1",
      "token_expiration_desc_2",
      "warning_client_rate_limit",
      "duration_seconds",
      "duration_minutes",
      "duration_hours",
      "duration_days",
      "duration_weeks",
      "error_response",
      "setting:default_token_expiration_invalid"
    ],
    "core": [
      "cancel"
    ],
    "totara_core": [
      "save"
    ]
  }
</lang-strings>
<style lang="scss">
.tui-totara_api-editClientSettings {
  &__defaultInfo {
    color: var(--form-defaults-text-color);
    @include tui-font-body-x-small;
  }
  &__requiredStar {
    color: var(--color-prompt-alert);
    font-weight: bold;
  }
}
</style>
