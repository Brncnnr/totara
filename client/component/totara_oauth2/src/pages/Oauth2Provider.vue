<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Qingyang Liu <qingyang.liu@totaralearning.com>
  @module totara_oauth2
-->

<template>
  <Layout
    class="tui-oauth2ProviderPage"
    :title="$str('oauth2providerdetails', 'totara_oauth2')"
    :loading="$apollo.loading"
  >
    <template v-slot:header-buttons>
      <Button
        :text="$str('add_provider', 'totara_oauth2')"
        @click.prevent="modalOpen = true"
      />
    </template>

    <template v-slot:modals>
      <ModalPresenter :open="modalOpen" @request-close="modalOpen = false">
        <Oauth2ProviderModal
          :title="$str('add_oauth2_provider', 'totara_oauth2')"
          :is-saving="isSaving"
          @submit="createProvider"
        />
      </ModalPresenter>

      <!-- Deletion modal  -->
      <DeleteConfirmationModal
        :open="deleteModalOpen"
        :title="$str('delete_modal_title', 'totara_oauth2')"
        :confirm-button-text="$str('continue', 'totara_oauth2')"
        :loading="deleting"
        :close-button="true"
        @confirm="deleteProvider"
        @cancel="deleteModalOpen = false"
      >
        <template>
          <p>{{ $str('delete_confirm_title', 'totara_oauth2') }}</p>
          <p
            class="tui-oauth2ProviderPage__deleteBody"
            v-html="
              $str('delete_confirm_body', 'totara_oauth2', targetProvider.name)
            "
          />
        </template>
      </DeleteConfirmationModal>
    </template>

    <template v-if="!$apollo.loading" v-slot:content>
      <template v-if="hasNoRecordError">
        <p class="tui-oauth2ProviderPage__errorTitle">
          {{ $str('no_record_found', 'totara_oauth2') }}
        </p>
      </template>

      <template v-else>
        <Collapsible
          v-for="provider in providers"
          :key="provider.id"
          :label="provider.name"
          class="tui-oauth2ProviderPage__provider"
          :value="expanded[provider.id]"
          @input="handleCollapsibleChange($event, provider.id)"
        >
          <template v-slot:collapsible-side-content>
            <Oauth2ProviderAction
              :provider-name="provider.name"
              @delete-provider="openDeleteModal(provider)"
            />
          </template>

          <Form class="tui-oauth2ProviderPage__form">
            <FormRow
              v-if="provider.description"
              :vertical="true"
              class="tui-oauth2ProviderPage__formDesc"
              v-html="provider.description"
            />
            <FormRow
              :label="$str('client_id', 'totara_oauth2')"
              :class="{
                'tui-oauth2ProviderPage__clientId': !provider.description,
              }"
            >
              <span class="tui-oauth2ProviderPage__monospaceFont">
                {{ provider.client_id }}
              </span>
            </FormRow>
            <FormRow
              v-slot="{ id, labelId }"
              :label="$str('client_secret', 'totara_oauth2')"
            >
              <InputGroup :aria-labelledby="labelId">
                <template>
                  <InputGroupInput
                    :id="id"
                    :monospace="true"
                    readonly
                    :type="showSecret[provider.id] ? 'text' : 'password'"
                    :value="provider.client_secret"
                  />
                  <InputGroupButton
                    class="tui-oauth2ProviderPage__inputGroupBtn"
                    :aria-controls="id"
                    :aria-label="showHideAriaLabel(provider)"
                    :text="showHideText(provider.id)"
                    @click="toggleSecretVisibility(provider.id)"
                  />
                </template>
              </InputGroup>
            </FormRow>
            <FormRow :label="$str('scopes', 'totara_oauth2')">
              {{ provider.detail_scope }}
            </FormRow>
          </Form>
        </Collapsible>
        <Oauth2ProviderContent />
      </template>
    </template>
  </Layout>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import Collapsible from 'tui/components/collapsible/Collapsible';
import DeleteConfirmationModal from 'tui/components/modal/ConfirmationModal';
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import Layout from 'tui/components/layouts/LayoutOneColumn';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import Oauth2ProviderContent from 'totara_oauth2/components/Oauth2ProviderContent';
import Oauth2ProviderModal from 'totara_oauth2/components/modal/Oauth2ProviderModal';
import Oauth2ProviderAction from 'totara_oauth2/components/action/Oauth2ProviderAction';
import InputGroup from 'tui/components/form/InputGroup';
import InputGroupInput from 'tui/components/form/InputGroupInput';
import InputGroupButton from 'tui/components/form/InputGroupButton';

import { notify } from 'tui/notifications';

// GraphQL
import clientProvidersQuery from 'totara_oauth2/graphql/client_providers';
import createProviderMutation from 'totara_oauth2/graphql/create_provider';
import deleteProviderMutation from 'totara_oauth2/graphql/delete_provider';

export default {
  components: {
    Button,
    Collapsible,
    DeleteConfirmationModal,
    FormRow,
    Form,
    Layout,
    ModalPresenter,
    Oauth2ProviderModal,
    Oauth2ProviderContent,
    Oauth2ProviderAction,
    InputGroup,
    InputGroupInput,
    InputGroupButton,
  },

  data() {
    return {
      providers: [],
      modalOpen: false,
      deleteModalOpen: false,
      deleting: false,
      isSaving: false,
      expanded: {},
      targetProvider: {},
      showSecret: {},
    };
  },

  computed: {
    hasNoRecordError() {
      return this.providers.length === 0;
    },
  },

  apollo: {
    providers: {
      query: clientProvidersQuery,
      variables() {
        return {
          input: {},
        };
      },
      update({ providers: { items } }) {
        return items;
      },
    },
  },

  created() {
    this.providers.forEach(provider => (this.expanded[provider.id] = false));
  },

  methods: {
    /**
     *
     * @param {Object} formValue
     */
    async createProvider(formValue) {
      this.isSaving = true;

      try {
        const {
          data: { provider },
        } = await this.$apollo.mutate({
          mutation: createProviderMutation,
          variables: {
            input: {
              name: formValue.name,
              description: formValue.description,
              scope_type: formValue.xapi_write,
            },
          },
          update: (proxy, { data: { provider } }) => {
            const variables = { input: {} };
            const {
              providers: { items },
            } = proxy.readQuery({
              query: clientProvidersQuery,
              variables,
            });

            const innerProviders = [...items];

            if (provider) {
              innerProviders.push(provider);
            }

            proxy.writeQuery({
              query: clientProvidersQuery,
              variables,
              data: {
                providers: {
                  items: innerProviders.sort((p1, p2) =>
                    p1.name.localeCompare(p2.name)
                  ),
                },
              },
            });
          },
        });

        if (provider) {
          this.providers.forEach(p => (this.expanded[p.id] = false));
          this.modalOpen = false;
          this.expanded[provider.id] = true;

          await notify({
            message: this.$str('provider_added', 'totara_oauth2'),
            type: 'success',
          });
        }
      } finally {
        this.isSaving = false;
      }
    },

    /**
     * @param {Int} id
     * @param {Boolean} value
     */
    handleCollapsibleChange(value, id) {
      this.expanded = Object.assign({}, this.expanded, { [id]: value });
    },

    /**
     *
     * @param {Object} provider
     */
    openDeleteModal(provider) {
      this.targetProvider = provider;
      this.deleteModalOpen = true;
    },

    async deleteProvider() {
      if (!this.deleteModalOpen || !this.targetProvider) {
        return;
      }
      try {
        this.deleting = true;

        const {
          data: { result },
        } = await this.$apollo.mutate({
          mutation: deleteProviderMutation,
          variables: {
            id: this.targetProvider.id,
          },
          update: proxy => {
            const variables = { input: {} };
            const {
              providers: { items },
            } = proxy.readQuery({
              query: clientProvidersQuery,
              variables,
            });

            const innerProviders = [...items];

            proxy.writeQuery({
              query: clientProvidersQuery,
              variables,
              data: {
                providers: {
                  items: innerProviders.filter(
                    p => p.id !== this.targetProvider.id
                  ),
                },
              },
            });
          },
        });

        if (result) {
          notify({
            type: 'success',
            message: this.$str('delete_success', 'totara_oauth2'),
          });
        }
      } finally {
        this.deleteModalOpen = false;
        this.deleting = false;
      }
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
        ? this.$str('hide', 'totara_oauth2')
        : this.$str('show', 'totara_oauth2');
    },

    /**
     * @param {object} provider
     */
    showHideAriaLabel(provider) {
      return this.showSecret[provider.id]
        ? this.$str('hide_client_secret_aria', 'totara_oauth2', provider.name)
        : this.$str('show_client_secret_aria', 'totara_oauth2', provider.name);
    },
  },
};
</script>

<lang-strings>
  {
    "totara_oauth2": [
      "add_provider",
      "add_oauth2_provider",
      "client_provider_description",
      "client_id",
      "client_secret",
      "continue",
      "delete_confirm_body",
      "delete_confirm_title",
      "delete_modal_title",
      "delete_success",
      "scopes",
      "no_record_found",
      "oauth2providerdetails",
      "provider_added",
      "show",
      "show_client_secret_aria",
      "hide",
      "hide_client_secret_aria"
    ]
  }
</lang-strings>
<style lang="scss">
.tui-oauth2ProviderPage {
  &__provider {
    margin-bottom: 2px;
  }
  &__form {
    @include tui-wordbreak--hard();
    margin-bottom: var(--gap-6);
  }
  &__formDesc {
    margin-top: var(--gap-6);
  }
  &__clientId {
    margin-top: var(--gap-4);
  }
  &__monospaceFont {
    @include tui-font-monospace();
  }
  &__deleteBody {
    @include tui-wordbreak--hard();
  }
  &__inputGroupBtn {
    min-width: 6rem;
  }
}
</style>
