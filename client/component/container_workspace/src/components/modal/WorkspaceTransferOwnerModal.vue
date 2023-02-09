<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module container_workspace
-->
<template>
  <Modal
    :dismissable="{
      esc: false,
      backdropClick: false,
    }"
    class="tui-workspaceTransferOwnerModal"
  >
    <ModalContent
      :title="$str('transfer_ownership_of_workspace', 'container_workspace')"
      :close-button="false"
      class="tui-workspaceTransferOwnerModal__modalContent"
    >
      <!-- Do not display the current owner if the actor is an owner of this workspace -->
      <WorkspaceTransferOwnerForm
        v-if="!$apollo.loading"
        :workspace-id="workspaceId"
        :display-current-owner="!workspace.interactor.own"
        :current-owner-fullname="workspaceOwnerFullname"
        :submitting="submitting"
        class="tui-workspaceTransferOwnerModal__form"
        @submit="updatePrimaryOwner"
        @cancel.prevent="$emit('request-close', $event)"
      />
    </ModalContent>
  </Modal>
</template>

<script>
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import WorkspaceTransferOwnerForm from 'container_workspace/components/form/WorkspaceTransferOwnerForm';
import { notify } from 'tui/notifications';
import { config } from 'tui/config';

// GraphQL queries.
import getWorkspace from 'container_workspace/graphql/get_workspace';
import transferOwner from 'container_workspace/graphql/transfer_owner';

export default {
  components: {
    Modal,
    ModalContent,
    WorkspaceTransferOwnerForm,
  },

  props: {
    workspaceId: {
      type: [String, Number],
      required: true,
    },
  },

  apollo: {
    workspace: {
      query: getWorkspace,
      // This is to prevent concurrent actions
      fetchPolicy: 'network-only',
      variables() {
        return {
          id: this.workspaceId,
          theme: config.theme.name,
        };
      },
    },
  },

  data() {
    return {
      workspace: null,
      submitting: false,
    };
  },

  computed: {
    /**
     * @return {String}
     */
    workspaceOwnerFullname() {
      if (
        this.workspace &&
        this.workspace.owner &&
        this.workspace.owner.suspended
      ) {
        return this.$str(
          'current_owner_suspended',
          'container_workspace',
          this.workspace.owner.fullname
        );
      } else if (this.workspace && this.workspace.owner) {
        return this.workspace.owner.fullname;
      }

      return this.$str('none', 'core');
    },
  },

  methods: {
    /**
     *
     * @param {Number} userId
     * @return {Promise<void>}
     */
    async updatePrimaryOwner({ userId }) {
      if (this.submitting) {
        return;
      }

      this.submitting = true;
      try {
        const {
          data: { member },
        } = await this.$apollo.mutate({
          mutation: transferOwner,
          variables: {
            workspace_id: this.workspaceId,
            user_id: userId,
          },
          refetchQueries: [
            'container_workspace_get_workspace',
            'container_workspace_find_members',
            'container_workspace_workspace_interactor',
            'container_workspace_get_discussions',
          ],
          awaitRefetchQueries: true,
        });

        this.$emit('transfered-owner');

        await notify({
          message: this.$str(
            'transfer_ownership_success_alert',
            'container_workspace',
            {
              user: member.user.fullname,
              workspace: this.workspace.name,
            }
          ),
          type: 'success',
        });
      } finally {
        this.submitting = false;
      }
    },
  },
};
</script>

<lang-strings>
  {
    "container_workspace": [
      "current_owner_suspended",
      "transfer_ownership_of_workspace",
      "transfer_ownership_success_alert"
    ],
    "core": [
      "none"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-workspaceTransferOwnerModal {
  &__modalContent {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
  }

  &__form {
    flex-grow: 1;
  }
}
</style>
