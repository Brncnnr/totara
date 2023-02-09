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

  @author Michael Ivanov <michael.ivanov@totaralearning.com>
  @author Jack Humphrey <jack.humphrey@totaralearning.com>
  @module totara_api
-->

<template>
  <Layout
    :title="$str('add_client', 'totara_api')"
    class="tui-totara_api-addClient"
  >
    <template v-slot:content>
      <Uniform
        ref="form"
        :initial-values="initialValues"
        @change="formValues = $event"
        @submit="createClient"
      >
        <FormRow aria-hidden="true" :vertical="true">
          <span class="tui-totara_api-addClient__requiredStar"> * </span>
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
            :validations="v => [v.required()]"
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
              :text="$str('add', 'core')"
              :loading="isAdding"
              type="submit"
            />
            <ActionLink
              :disabled="isAdding"
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

//GraphQL
import createApiClientMutation from 'totara_api/graphql/create_client';
import searchUsers from 'totara_api/graphql/search_users';

export default {
  components: {
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
    tenantId: Number,
    tenantSuspended: Boolean,
  },

  apollo: {
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

  data() {
    return {
      initialValues: {
        name: '',
        description: '',
        serviceAccount: null,
        status: !this.tenantSuspended,
      },
      formValues: null,
      isAdding: false,
      searchPattern: '',
      availableUsers: [],
    };
  },

  computed: {
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

  methods: {
    /**
     *
     * @param {Object} formValue
     */
    async createClient(formValue) {
      this.isAdding = true;
      try {
        const {
          data: { client },
        } = await this.$apollo.mutate({
          mutation: createApiClientMutation,
          variables: {
            input: {
              name: formValue.name,
              description: formValue.description,
              tenant_id: this.tenantId,
              status: formValue.status,
              user_id: formValue.serviceAccount.id,
            },
          },
        });

        document.location.href = this.getClientsPageUrl({
          add_success: 1,
          expand_client: client.id,
        });
        window.addEventListener('pageshow', this.resetIsAdding);
      } catch (e) {
        this.isAdding = false;
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
     * Reset isAdding on navigation
     * @param {event} event
     */
    resetIsAdding(event) {
      if (event.persisted) {
        window.removeEventListener('pageshow', this.resetIsAdding);
        this.isAdding = false;
      }
    },
  },
};
</script>
<lang-strings>
  {
    "totara_api": [
      "add_client",
      "description",
      "required_fields",
      "status",
      "status_enabled",
      "service_account",
      "service_account_help",
      "service_account_placeholder"
    ],
    "core": [
      "add",
      "cancel",
      "name"
    ]
  }
</lang-strings>
<style lang="scss">
.tui-totara_api-addClient {
  &__requiredStar {
    color: var(--color-prompt-alert);
    font-weight: bold;
  }
}
</style>
