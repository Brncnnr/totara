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
    class="tui-workspaceEditModal"
    size="large"
    :dismissable="{
      esc: true,
      backdropClick: false,
      overlayClose: false,
    }"
  >
    <ModalContent
      :close-button="false"
      :title="$str('edit_space', 'container_workspace')"
      :title-id="$id('edit-space')"
      class="tui-workspaceEditModal__content"
    >
      <!--
        Several rules for moving between settings.
        When the workspace is public - it cannot go down to either private nor hidden.
        When the workspace is private - it can go up to public, but not going down to hidden.
        When the workspace is hidden - it can go up to private or public.
      -->
      <WorkspaceForm
        v-if="!$apollo.loading"
        :submitting="submitting"
        :workspace-id="workspaceId"
        :workspace-name="workspace.name"
        :workspace-description="workspace.description"
        :workspace-description-format="workspace.description_format"
        :submit-button-label="$str('save_changes', 'container_workspace')"
        :show-private-box="false"
        :show-hidden-check-box="false"
        :show-unhidden-check-box="isHidden && canUnhide"
        :workspace-private="isPrivate || isHidden"
        :workspace-hidden="isHidden"
        class="tui-workspaceEditModal__form"
        @submit="updateWorkspace"
        @cancel="$emit('request-close')"
      />
    </ModalContent>
  </Modal>
</template>

<script>
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import WorkspaceForm from 'container_workspace/components/form/WorkspaceForm';
import { notify } from 'tui/notifications';
import { isPublic, isPrivate, isHidden } from 'container_workspace/index';
import { config } from 'tui/config';

// GraphQL queries
import getWorkspaceRaw from 'container_workspace/graphql/workspace_raw';
import updateWorkspace from 'container_workspace/graphql/update_workspace';
import myWorkspaceUrls from 'container_workspace/graphql/my_workspace_urls';
import getCategoryInteractor from 'container_workspace/graphql/workspace_category_interactor';

export default {
  components: {
    Modal,
    ModalContent,
    WorkspaceForm,
  },

  props: {
    workspaceId: {
      type: [String, Number],
      required: true,
    },
  },

  apollo: {
    workspace: {
      query: getWorkspaceRaw,
      fetchPolicy: 'network-only',
      variables() {
        return {
          id: this.workspaceId,
        };
      },
    },

    categoryInteractor: {
      query: getCategoryInteractor,
      variables() {
        return {
          workspace_id: this.workspaceId,
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
      workspace: {},
      categoryInteractor: {},
    };
  },

  computed: {
    /**
     * @return {Boolean}
     */
    isPublic() {
      return isPublic(this.workspace.access);
    },

    /**
     * @return {Boolean}
     */
    isPrivate() {
      return isPrivate(this.workspace.access);
    },

    /**
     * @return {Boolean}
     */
    isHidden() {
      return isHidden(this.workspace.access);
    },

    /**
     * @return {Boolean}
     */
    canUnhide() {
      return this.categoryInteractor.can_create_hidden;
    },
  },

  methods: {
    /**
     *
     * @param {String}  name
     * @param {String}  description
     * @param {Number}  descriptionFormat
     * @param {Number}  draftId
     * @param {Boolean} isPrivate
     * @param {Boolean} isHidden
     *
     * @return {Promise<void>}
     */
    async updateWorkspace({
      name,
      description,
      descriptionFormat,
      draftId,
      isHidden,
      isPrivate,
    }) {
      if (this.submitting) {
        return;
      }

      this.submitting = true;
      try {
        // Changing from hidden to no hidden then we will pop the notification.
        const moveToNoHidden = !isHidden && this.isHidden;

        const {
          data: { workspace },
        } = await this.$apollo.mutate({
          mutation: updateWorkspace,
          refetchAll: false,
          refetchQueries: [{ query: myWorkspaceUrls }],
          variables: {
            id: this.workspaceId,
            name: name,
            description: description,
            description_format: descriptionFormat,
            draft_id: draftId,
            private: isPrivate,
            hidden: isHidden,
            theme: config.theme.name,
          },
        });

        if (moveToNoHidden) {
          await notify({
            message: this.$str('updated_to_private', 'container_workspace'),
            type: 'success',
          });
        }

        this.$emit('update-workspace', workspace);
      } catch (e) {
        await notify({
          message: this.$str(
            'error:update',
            'container_workspace',
            this.workspace.name
          ),
          type: 'error',
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
    "edit_space",
    "save_changes",
    "error:update",
    "updated_to_private"
  ]
}
</lang-strings>

<style lang="scss">
.tui-workspaceEditModal {
  display: flex;
  flex-direction: column;

  &__content {
    display: flex;
    flex-direction: column;
  }

  &__form {
    flex-grow: 1;
    height: 100%;
  }
}
</style>
