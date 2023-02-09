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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module container_workspace
-->
<template>
  <Modal
    class="tui-workspaceModal"
    size="large"
    :dismissable="{
      esc: true,
      backdropClick: false,
      overlayClose: false,
    }"
  >
    <ModalContent
      :close-button="false"
      :title="$str('create_space', 'container_workspace')"
      :title-id="$id('create-space')"
    >
      <WorkspaceForm
        class="tui-workspaceModal__form"
        :context-id="categoryInteractor ? categoryInteractor.context_id : null"
        :submitting="submitting"
        :can-set-public="canPublic"
        :can-set-private="canPrivate"
        :show-private-box="canPrivate"
        :show-hidden-check-box="canPrivate && canHidden"
        @submit="createWorkspace"
        @cancel="$emit('request-close')"
      />
    </ModalContent>
  </Modal>
</template>

<script>
import Modal from 'tui/components/modal/Modal';
import WorkspaceForm from 'container_workspace/components/form/WorkspaceForm';
import ModalContent from 'tui/components/modal/ModalContent';
import { notify } from 'tui/notifications';
import { config } from 'tui/config';

// GraphQL queries
import createWorkspace from 'container_workspace/graphql/create_workspace';
import getCategoryInteractor from 'container_workspace/graphql/workspace_category_interactor';

export default {
  components: {
    Modal,
    ModalContent,
    WorkspaceForm,
  },

  apollo: {
    categoryInteractor: {
      query: getCategoryInteractor,
      variables() {
        return {
          workspace_id: null,
        };
      },
      update({ category_interactor }) {
        return category_interactor;
      },
    },
  },

  data() {
    return {
      submitting: false,
      categoryInteractor: {},
    };
  },

  computed: {
    /**
     * Can create private workspaces
     */
    canPrivate() {
      return this.categoryInteractor.can_create_private;
    },

    /**
     * Can create hidden workspaces
     */
    canHidden() {
      return this.categoryInteractor.can_create_hidden;
    },

    /**
     * Can create public workspaces
     */
    canPublic() {
      return this.categoryInteractor.can_create_public;
    },
  },

  methods: {
    /**
     *
     * @param {String}    name
     * @param {String}    description
     * @param {Number}    draftId
     * @param {Number}    descriptionFormat
     * @param {Boolean}   isHidden
     * @param {Boolean}   isPrivate
     */
    async createWorkspace({
      name,
      description,
      draftId,
      descriptionFormat,
      isPrivate,
      isHidden,
    }) {
      if (this.submitting) {
        return;
      }

      // This is cleared by page load or component destruction
      this.submitting = true;

      try {
        const {
          data: { workspace },
        } = await this.$apollo.mutate({
          mutation: createWorkspace,
          refetchAll: false,
          variables: {
            name: name,
            description: description,
            draft_id: draftId,
            description_format: descriptionFormat,
            private: isPrivate,
            hidden: isHidden,
            theme: config.theme.name,
          },
        });

        this.$emit('create-workspace', workspace);
      } catch (e) {
        await notify({
          message: this.$str('error:create', 'container_workspace'),
          type: 'error',
        });
      }
    },
  },
};
</script>

<lang-strings>
  {
    "container_workspace": [
      "create_space",
      "error:create"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-workspaceModal {
  &__form {
    flex-grow: 1;
    height: 100%;
  }
}
</style>
