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

  @author Alvin Smith <alvin.smith@totaralearning.com>
  @module container_workspace
-->

<template>
  <div class="tui-workspacePostDiscussionForm">
    <PostAvatar
      :profile-url="avatarImageUrl"
      :image-alt="avatarImageAlt"
      :image-src="avatarImageSrc"
      class="tui-workspacePostDiscussionForm__avatar"
    />

    <WorkspaceDiscussionForm
      v-if="!$apollo.loading"
      :submitting="submitting"
      :has-error="hasError"
      :draft-id="draftId"
      :show-cancel-button="false"
      :workspace-context-id="workspaceContextId"
      class="tui-workspacePostDiscussionForm__form"
      @submit="$emit('submit', $event)"
    />
  </div>
</template>

<script>
import PostAvatar from 'container_workspace/components/profile/PostAvatar';
import WorkspaceDiscussionForm from 'container_workspace/components/form/WorkspaceDiscussionForm';

// GraphQL queries
import discussionDraftId from 'container_workspace/graphql/discussion_draft_id';

export default {
  components: {
    PostAvatar,
    WorkspaceDiscussionForm,
  },

  props: {
    avatarImageUrl: {
      type: String,
      required: true,
    },

    avatarImageAlt: {
      type: String,
      required: true,
    },

    avatarImageSrc: {
      type: String,
      required: true,
    },

    hasError: Boolean,

    /**
     * Requiring a workspace's context id when we are creating a new discussion
     * within a workspace.
     */
    workspaceContextId: {
      type: [Number, String],
      required: true,
    },

    submitting: Boolean,
  },

  apollo: {
    draftId: {
      query: discussionDraftId,
      fetchPolicy: 'network-only',
      variables() {
        return {
          id: this.discussionId,
        };
      },

      update({ draft_id }) {
        return draft_id;
      },
    },
  },
};
</script>

<style lang="scss">
.tui-workspacePostDiscussionForm {
  display: flex;

  &__avatar {
    display: none;
  }

  &__form {
    flex-grow: 1;
    width: 100%;
  }
}

@media (min-width: $tui-screen-sm) {
  .tui-workspacePostDiscussionForm {
    &__avatar {
      display: block;
    }

    &__form {
      padding-left: var(--gap-2);
    }
  }
}
</style>
