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

  @author Simon Coggins <simon.coggins@totaralearning.com>
  @module totara_api
-->

<template>
  <Layout
    class="tui-totara_api-clients"
    :title="$str('client_details', 'totara_api')"
    :loading="$apollo.loading"
  >
    <template v-slot:header-buttons>
      <ActionLink
        :href="getAddClientPageUrl()"
        :text="$str('add_client', 'totara_api')"
      />
    </template>

    <template v-slot:modals>
      <!-- Deletion modal  -->
      <ConfirmationModal
        :open="deleteModalOpen"
        :title="$str('delete_modal_title', 'totara_api')"
        :confirm-button-text="$str('delete', 'core')"
        :loading="deleting"
        @confirm="deleteClient"
        @cancel="deleteModalOpen = false"
      >
        <template>
          <p>{{ $str('delete_confirm_title', 'totara_api') }}</p>
          <p class="tui-totara_api-clients__deleteBody">
            {{ $str('delete_confirm_body', 'totara_api', targetClient.name) }}
          </p>
        </template>
      </ConfirmationModal>
    </template>

    <template v-if="!$apollo.loading" v-slot:content>
      <template v-if="hasNoRecords">
        <p>{{ $str('no_record_found', 'totara_api') }}</p>
      </template>

      <template v-else>
        <Collapsible
          v-for="client in clients"
          :key="client.id"
          :label="
            client.status
              ? client.name
              : $str('client_name_disabled', 'totara_api', client.name)
          "
          class="tui-totara_api-clients__client"
          :value="expanded[client.id]"
          @input="handleCollapsibleChange($event, client.id)"
        >
          <template v-slot:collapsible-side-content>
            <Invalid
              v-if="!client.service_account.is_valid"
              :alt="$str('service_account_invalid', 'totara_api', client.name)"
              class="tui-totara_api-clients__client--invalid"
            />
            <ClientActions
              :client-name="client.name"
              :client-status-enabled="client.status"
              :client-id="client.id"
              :tenant-id="tenantId"
              :tenant-suspended="tenantSuspended"
              @delete-api-client="openDeleteModal(client)"
              @disable-api-client="setClientStatus(client, false)"
              @enable-api-client="setClientStatus(client, true)"
            />
          </template>

          <Form class="tui-totara_api-clients__form">
            <FormRow
              v-if="client.description"
              :vertical="true"
              class="tui-totara_api-clients__formDesc"
              v-html="client.description"
            />
            <!-- Currently it's only one oauth provider's client id and secret for one client, but it will removed -->
            <template v-if="client.oauth2_client_providers[0]">
              <FormRow :label="$str('client_id', 'totara_api')">
                <span class="tui-totara_api-clients__monospaceFont">
                  {{ client.oauth2_client_providers[0].client_id }}
                </span>
              </FormRow>
              <FormRow
                v-slot="{ id, labelId }"
                :label="$str('client_secret', 'totara_api')"
              >
                <InputGroup :aria-labelledby="labelId">
                  <template>
                    <InputGroupInput
                      :id="id"
                      :monospace="true"
                      readonly
                      :type="showSecret[client.id] ? 'text' : 'password'"
                      :value="client.oauth2_client_providers[0].client_secret"
                    />
                    <InputGroupButton
                      class="tui-totara_api-clients__inputGroupBtn"
                      :aria-controls="id"
                      :text="showHideText(client.id)"
                      @click="toggleSecretVisibility(client.id)"
                    />
                  </template>
                </InputGroup>
              </FormRow>
              <FormRow :label="$str('service_account', 'totara_api')">
                <a
                  v-if="client.service_account.user"
                  :href="getUserProfileUrl(client.service_account.user.id)"
                  class="tui-totara_api-clients__serviceaccount"
                >
                  {{ client.service_account.user.fullname }}
                </a>
                <Lozenge
                  v-if="!client.service_account.is_valid"
                  :text="$str('invalid_user', 'totara_api')"
                  type="warning"
                />
              </FormRow>
              <FormRow :label="$str('status', 'totara_api')">
                <span>
                  {{
                    client.status
                      ? $str('status_enabled', 'totara_api')
                      : $str('status_disabled', 'totara_api')
                  }}
                </span>
              </FormRow>
            </template>
          </Form>
        </Collapsible>
      </template>
    </template>
  </Layout>
</template>

<script>
import ActionLink from 'tui/components/links/ActionLink';
import Collapsible from 'tui/components/collapsible/Collapsible';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import Invalid from 'tui/components/icons/Invalid';
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import Layout from 'tui/components/layouts/LayoutOneColumn';
import ClientActions from 'totara_api/components/api_client/ClientActions';
import Lozenge from 'tui/components/lozenge/Lozenge';
import { notify } from 'tui/notifications';
import { produce } from 'tui/immutable';
import InputGroup from 'tui/components/form/InputGroup';
import InputGroupInput from 'tui/components/form/InputGroupInput';
import InputGroupButton from 'tui/components/form/InputGroupButton';

// GraphQL
import apiClientsQuery from 'totara_api/graphql/clients';
import deleteApiClientMutation from 'totara_api/graphql/delete_client';
import setApiClientStatus from 'totara_api/graphql/set_client_status';

export default {
  components: {
    ActionLink,
    Collapsible,
    ConfirmationModal,
    FormRow,
    Form,
    Layout,
    ClientActions,
    Lozenge,
    Invalid,
    InputGroup,
    InputGroupInput,
    InputGroupButton,
  },
  props: {
    tenantId: Number,
    tenantSuspended: Boolean,
  },

  data() {
    return {
      clients: [],
      deleteModalOpen: false,
      deleting: false,
      expanded: {},
      targetClient: {},
      urlParams: {},
      showSecret: {},
    };
  },

  computed: {
    hasNoRecords() {
      return this.clients.length === 0;
    },
  },

  apollo: {
    clients: {
      query: apiClientsQuery,
      variables() {
        return {
          input: {
            tenant_id: this.tenantId,
          },
        };
      },
      update({ clients: { items } }) {
        return items;
      },
    },
  },

  mounted() {
    const queryString = window.location.search;
    this.urlParams = new URLSearchParams(queryString);
    const expand_client_id = this.urlParams.get('expand_client');
    if (!expand_client_id) {
      return;
    }
    this.expanded[expand_client_id] = true;
    if (this.urlParams.get('add_success')) {
      notify({ message: this.$str('client_added', 'totara_api') });
      this.urlParams.delete('add_success');
    } else if (this.urlParams.get('edit_success')) {
      notify({ message: this.$str('changes_saved', 'totara_api') });
      this.urlParams.delete('edit_success');
    }
    this.clearURLParams();
  },

  methods: {
    /**
     * @param {number} id
     * @param {boolean} value
     */
    handleCollapsibleChange(value, id) {
      this.expanded = { ...this.expanded, [id]: value };
    },

    /**
     *
     * @param {object} client
     */
    openDeleteModal(client) {
      this.targetClient = client;
      this.deleteModalOpen = true;
    },

    async deleteClient() {
      if (!this.deleteModalOpen || !this.targetClient) {
        return;
      }
      try {
        this.deleting = true;

        const {
          data: { result },
        } = await this.$apollo.mutate({
          mutation: deleteApiClientMutation,
          variables: {
            id: this.targetClient.id,
          },
          update: proxy => {
            const variables = {
              input: {
                tenant_id: this.tenantId,
              },
            };
            let data = proxy.readQuery({
              query: apiClientsQuery,
              variables,
            });

            data = produce(data, draft => {
              // clone the array here as produce() does not intercept sort()
              const items = [...draft.clients.items];
              draft.clients.items = items.filter(
                c => c.id !== this.targetClient.id
              );
            });
            proxy.writeQuery({
              query: apiClientsQuery,
              variables,
              data,
            });
          },
        });

        if (result) {
          notify({
            type: 'success',
            message: this.$str('delete_success', 'totara_api'),
          });
        }
      } finally {
        this.deleteModalOpen = false;
        this.deleting = false;
      }
    },

    /**
     *
     * @param {object} client
     * @param {boolean} status
     */
    async setClientStatus(client, status) {
      const {
        data: { result },
      } = await this.$apollo.mutate({
        mutation: setApiClientStatus,
        variables: {
          id: client.id,
          status: status,
        },
      });

      if (result) {
        notify({
          type: 'success',
          message: this.$str(
            status ? 'enable_client_success' : 'disable_client_success',
            'totara_api'
          ),
        });
      }
    },

    /**
     * Remove one-off parameters from the URL like expand_client and add_success
     */
    clearURLParams() {
      const params = this.tenantId ? { tenant_id: this.tenantId } : {};
      window.history.replaceState(
        null,
        null,
        this.$url('/totara/api/client/', params)
      );
    },

    /**
     * Get the URL for add client page
     * @returns {string}
     */
    getAddClientPageUrl() {
      let params = {};
      if (this.tenantId) {
        params.tenant_id = this.tenantId;
      }
      return this.$url('/totara/api/client/add.php', params);
    },

    /**
     * @param {int} userId
     * @returns {string}
     */
    getUserProfileUrl(userId) {
      return this.$url('/user/profile.php', { id: userId });
    },

    /**
     * @param {number} id
     */
    toggleSecretVisibility(id) {
      this.$set(this.showSecret, id, !this.showSecret[id]);
    },

    /**
     * @param {number} id
     */
    showHideText(id) {
      return this.showSecret[id]
        ? this.$str('hide', 'totara_api')
        : this.$str('show', 'totara_api');
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "delete"
    ],
    "totara_api": [
      "add_client",
      "changes_saved",
      "client_added",
      "client_details",
      "client_id",
      "client_secret",
      "client_name_disabled",
      "delete_confirm_body",
      "delete_confirm_title",
      "delete_modal_title",
      "delete_success",
      "disable_client_error",
      "disable_client_success",
      "enable_client_error",
      "enable_client_success",
      "hide",
      "invalid_user",
      "no_record_found",
      "show",
      "status",
      "status_enabled",
      "status_disabled",
      "service_account",
      "service_account_invalid"
    ]
  }
</lang-strings>
<style lang="scss">
.tui-totara_api-clients {
  &__client {
    margin-bottom: 2px;
    &--invalid {
      margin-top: 2px;
      color: var(--color-prompt-warning);
    }
  }
  &__form {
    @include tui-wordbreak--hard();
    margin-top: var(--gap-4);
    margin-bottom: var(--gap-6);
  }
  &__formDesc {
    margin-top: var(--gap-6);
  }
  &__monospaceFont {
    @include tui-font-monospace();
  }
  &__deleteBody {
    @include tui-wordbreak--hard();
  }
  &__serviceaccount {
    @include tui-font-link();
    margin-right: var(--gap-2);
  }
  &__inputGroupBtn {
    min-width: 6rem;
  }
}
</style>
