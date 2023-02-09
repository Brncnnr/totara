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
    :title="$str('edit_client_details', 'totara_api')"
    class="tui-totara_api-editClient"
  >
    <template v-slot:content>
      <Loader v-if="loading" :loading="loading" />
      <Uniform
        v-else
        ref="form"
        :initial-values="initialValues"
        @change="formValues = $event"
        @submit="editClient"
      >
        <FormRow aria-hidden="true" :vertical="true">
          <span class="tui-totara_api-editClient__requiredStar"> * </span>
          {{ $str('required_fields', 'totara_api') }}
        </FormRow>

        <FormRow :label="$str('name', 'core')" required>
          <FormText
            name="name"
            :validations="v => [v.required()]"
            :maxlength="75"
          />
        </FormRow>

        <FormRow :label="$str('description', 'totara_api')">
          <FormTextarea name="description" :maxlength="1024" :rows="8" />
        </FormRow>

        <FormRow
          :label="$str('service_account', 'totara_api')"
          required
          :helpmsg="$str('service_account_help', 'totara_api')"
        >
          <FormTagList
            name="serviceAccount"
            :validations="v => [isValid, v.required()]"
            :items="users"
            :filter="searchPattern"
            :input-placeholder="
              $str('service_account_placeholder', 'totara_api')
            "
            :label-name="$str('service_account', 'totara_api')"
            single-select
            close-on-click
            @filter="searchPattern = $event"
          >
            <template
              v-if="!$apollo.loading"
              v-slot:item="{ item: { card_display } }"
            >
              <MiniProfileCard
                :no-padding="true"
                :no-border="true"
                :display="card_display"
                :read-only="true"
              />
            </template>
          </FormTagList>
        </FormRow>

        <FormRow :label="$str('status', 'totara_api')">
          <FormCheckbox
            :id="$id('status')"
            name="status"
            :disabled="tenantSuspended"
          >
            {{ $str('status_enabled', 'totara_api') }}
          </FormCheckbox>
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
import Layout from 'tui/components/layouts/LayoutOneColumn';
import {
  FormText,
  Uniform,
  FormTextarea,
  FormCheckbox,
} from 'tui/components/uniform';
import FormTagList from 'tui/components/uniform/FormTagList';
import MiniProfileCard from 'tui/components/profile/MiniProfileCard';

// GraphQL
import apiClientQuery from 'totara_api/graphql/client_for_editing';
import updateApiClientMutation from 'totara_api/graphql/update_client';
import searchUsers from 'totara_api/graphql/search_users';
import { Status } from 'totara_api/service_account';

export default {
  components: {
    Loader,
    ActionLink,
    Button,
    ButtonGroup,
    FormRow,
    Layout,
    FormText,
    Uniform,
    FormTextarea,
    FormCheckbox,
    FormTagList,
    MiniProfileCard,
  },
  props: {
    clientId: {
      required: true,
      type: Number,
    },
    tenantSuspended: Boolean,
    tenantId: Number,
  },

  data() {
    return {
      formValues: null,
      client: {},
      isSaving: false,
      initialValues: null,
      searchPattern: '',
      availableUsers: [],
    };
  },

  computed: {
    loading() {
      return Boolean(!this.initialValues);
    },

    /**
     * Returns a list of users that filters out the selected user by id.
     * @return {Array}
     */
    users() {
      const users = this.availableUsers.map(
        ({ id, fullname, card_display }) => ({
          id,
          text: fullname,
          card_display,
          isValid: true,
        })
      );
      if (!this.formValues || !this.formValues.serviceAccount) {
        return users;
      }
      return users.filter(
        ({ id }) => parseInt(id) != this.formValues.serviceAccount.id
      );
    },
  },

  apollo: {
    client: {
      query: apiClientQuery,
      variables() {
        return {
          id: this.clientId,
        };
      },
      update({ result: { client } }) {
        return client;
      },
      result({ data }) {
        this.initialValues = {
          name: data.result.client.name,
          description: data.result.client.description,
          status: data.result.client.status,
          serviceAccount: data.result.client.service_account
            ? data.result.client.service_account.user
              ? {
                  id: data.result.client.service_account.user.id,
                  text: data.result.client.service_account.user.fullname,
                  isValid: data.result.client.service_account.is_valid,
                  status: data.result.client.service_account.status,
                }
              : {
                  id: -1,
                  text: this.$str('unknownuser', 'core'),
                  isValid: data.result.client.service_account.is_valid,
                  status: data.result.client.service_account.status,
                }
            : {
                id: -1,
                text: this.$str('unknownuser', 'core'),
                isValid: false,
                status: null,
              },
        };
        this.$nextTick(() => {
          this.$refs.form.touch('serviceAccount');
        });
      },
    },

    availableUsers: {
      query: searchUsers,
      fetchPolicy: 'network-only',
      variables() {
        return {
          input: {
            tenant_id: this.tenantId,
            pattern: this.searchPattern,
          },
        };
      },

      update({ result: { users } }) {
        return users;
      },
    },
  },

  methods: {
    /**
     *
     * @param {object} formValue
     */
    async editClient(formValue) {
      this.isSaving = true;
      try {
        await this.$apollo.mutate({
          mutation: updateApiClientMutation,
          variables: {
            input: {
              id: this.client.id,
              name: formValue.name,
              description: formValue.description,
              status: formValue.status,
              user_id: formValue.serviceAccount.id,
            },
          },
        });
        document.location.href = this.getClientsPageUrl({
          edit_success: 1,
          expand_client: this.client.id,
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

    /**
     * @param {object} val
     * @returns {object}
     */
    isValid(val) {
      if (val && !val.isValid) {
        return this.$_serviceAccountErrorString(val);
      }
    },

    /**
     * @param {object} val
     * @returns {string}
     */
    $_serviceAccountErrorString(val) {
      switch (val.status) {
        case Status.NO_USER:
          return this.$str('error_service_account_no_user', 'totara_api');
        case Status.DELETED:
          return this.$str('error_service_account_deleted', 'totara_api');
        case Status.SUSPENDED:
          return this.$str('error_service_account_suspended', 'totara_api');
        case Status.GUEST:
          return this.$str('error_service_account_guest', 'totara_api');
        case Status.ADMIN:
          return this.$str('error_service_account_admin', 'totara_api');
        case Status.WRONG_TENANT:
          return this.$str('error_service_account_wrong_tenant', 'totara_api');
        default:
          return this.$str('error_service_account_invalid', 'totara_api');
      }
    },
  },
};
</script>
<lang-strings>
  {
    "totara_api": [
      "description",
      "required_fields",
      "status",
      "status_enabled",
      "edit_client_details",
      "service_account",
      "service_account_help",
      "service_account_placeholder",
      "error_service_account_no_user",
      "error_service_account_deleted",
      "error_service_account_suspended",
      "error_service_account_guest",
      "error_service_account_admin",
      "error_service_account_wrong_tenant",
      "error_service_account_invalid"
    ],
    "core": [
      "name",
      "cancel",
      "error",
      "unknownuser"
    ],
    "totara_core": [
      "save"
    ]
  }
</lang-strings>
<style lang="scss">
.tui-totara_api-editClient {
  &__requiredStar {
    color: var(--color-prompt-alert);
    font-weight: bold;
  }
}
</style>
