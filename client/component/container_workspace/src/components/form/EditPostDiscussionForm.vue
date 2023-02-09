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
  <WorkspaceDiscussionForm
    v-if="discussion"
    :content="discussion.draft_content"
    :content-format="discussion.content_format"
    :draft-id="discussion.draft_id"
    :submit-button-text="$str('done', 'container_workspace')"
    :submitting="submitting"
    :has-error="hasError"
    :discussion-id="discussionId"
    :workspace-context-id="discussion.workspace_context_id"
    class="tui-workspaceEditPostDiscussionForm"
    @submit="submitUpdate"
    @cancel="$emit('cancel')"
  />
</template>

<script>
import WorkspaceDiscussionForm from 'container_workspace/components/form/WorkspaceDiscussionForm';

// GraphQL queries
import getDiscussionDraftContent from 'container_workspace/graphql/get_discussion_draft_content';

export default {
  components: {
    WorkspaceDiscussionForm,
  },

  props: {
    discussionId: {
      type: [String, Number],
      required: true,
    },

    submitting: Boolean,
    hasError: Boolean,
  },

  apollo: {
    discussion: {
      query: getDiscussionDraftContent,
      // Note that we do not use network-only here because this persist query is using the same graphql
      // query as the discussion persist query.
      fetchPolicy: 'no-cache',
      variables() {
        return {
          id: this.discussionId,
        };
      },
    },
  },

  data() {
    return {
      discussion: null,
    };
  },

  methods: {
    /**
     *
     * @param {String} content
     * @param {Number} itemId
     */
    submitUpdate({ content, itemId }) {
      this.$emit('submit', {
        content: content,
        contentFormat: this.discussion.content_format,
        itemId: itemId,
      });
    },
  },
};
</script>

<lang-strings>
  {
    "container_workspace": [
      "done"
    ]
  }
</lang-strings>
