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
  <Layout class="tui-workspaceDiscussionPage">
    <template v-if="!$apollo.loading" v-slot:left="{ direction }">
      <SidePanel
        v-if="direction === 'horizontal'"
        :sticky="false"
        :initially-open="true"
        :show-button-control="false"
      >
        <WorkspaceMenu
          :selected-workspace-id="discussion.workspace_id"
          @create-workspace="redirectToWorkspace"
        />
      </SidePanel>
    </template>

    <template v-if="!$apollo.loading" v-slot:right="{ direction, units }">
      <WorkspaceContentLayout
        :grid-direction="direction"
        :max-units="units"
        class="tui-workspaceDiscussionPage__column"
      >
        <div slot="content">
          <a
            :href="
              $url('/container/type/workspace/workspace.php', {
                id: discussion.workspace_id,
              })
            "
            class="tui-workspaceDiscussionPage__backButton"
          >
            <BackArrow size="200" />
            <span> {{ $str('discussions', 'container_workspace') }} </span>
          </a>

          <DiscussionCard
            :discussion-id="discussion.id"
            :total-reactions="discussion.total_reactions"
            :total-comments="discussion.total_comments"
            :time-description="discussion.time_description"
            :reacted="discussion.discussion_interactor.reacted"
            :removed="discussion.discussion_interactor.removed"
            :comment-able="discussion.discussion_interactor.can_comment"
            :update-able="discussion.discussion_interactor.can_update"
            :delete-able="discussion.discussion_interactor.can_delete"
            :react-able="discussion.discussion_interactor.can_react"
            :report-able="discussion.discussion_interactor.can_report"
            :creator-id="discussion.owner.id"
            :creator-fullname="discussion.owner.fullname"
            :creator-image-alt="
              discussion.owner.profileimagealt || discussion.owner.fullname
            "
            :creator-image-src="discussion.owner.profileimageurl"
            :discussion-content="discussion.content"
            :show-discussion-link="false"
            :edited="discussion.edited"
            class="tui-workspaceDiscussionPage__discussion"
            @update-react-status="updateReactStatus"
            @update-discussion="updateDiscussion"
            @trigger-comment="scrollToCommentForm"
          />

          <h3 class="tui-workspaceDiscussionPage__title">
            <span>
              {{
                $str('comments', 'totara_comment', discussion.total_comments)
              }}
            </span>

            <Loading v-if="submitting" size="300" />
          </h3>

          <CommentBox
            area="discussion"
            component="container_workspace"
            :instance-id="discussion.id"
            :editor="{
              variant: 'standard',
              compact: false,
              contextId: discussion.workspace_context_id,
              extraExtensions: ['hashtag', 'mention'],
            }"
            :size="size"
            :submit-form-button-text="$str('comment', 'container_workspace')"
            :show-like-button-text="true"
            :show-reply-button-text="true"
            :with-border="true"
            :submitting="submitting"
            :comment-able="discussion.discussion_interactor.can_comment"
            :comment-inline-head="true"
            class="tui-workspaceDiscussionPage__commentBox"
            @update-submitting="submitting = $event"
            @create-comment="updateDiscussionComments"
            @form-ready="setFormElement"
          />
        </div>
      </WorkspaceContentLayout>
    </template>
  </Layout>
</template>

<script>
import Layout from 'tui/components/layouts/LayoutTwoColumn';
import WorkspaceMenu from 'container_workspace/components/sidepanel/WorkspaceMenu';
import DiscussionCard from 'container_workspace/components/card/DiscussionCard';
import BackArrow from 'tui/components/icons/BackArrow';
import CommentBox from 'totara_comment/components/box/CommentBox';
import apolloClient from 'tui/apollo_client';
import Loading from 'tui/components/icons/Loading';
import { SIZE_LARGE } from 'totara_comment/size';
import SidePanel from 'tui/components/sidepanel/SidePanel';
import WorkspaceContentLayout from 'container_workspace/components/content/WorkspaceContentLayout';

// GraphQL queries
import getDiscussion from 'container_workspace/graphql/get_discussion';

export default {
  components: {
    Layout,
    WorkspaceMenu,
    DiscussionCard,
    BackArrow,
    CommentBox,
    Loading,
    SidePanel,
    WorkspaceContentLayout,
  },

  props: {
    discussionId: {
      type: [Number, String],
      required: true,
    },
  },

  apollo: {
    discussion: {
      query: getDiscussion,
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
      submitting: false,

      // We need to keep track of the form within the comment box so that we can scroll easily.
      formElement: null,
    };
  },

  computed: {
    size() {
      return SIZE_LARGE;
    },
  },

  methods: {
    /**
     * Only scroll to the form element if it is being set.
     */
    scrollToCommentForm() {
      if (this.formElement) {
        this.formElement.scrollIntoView(false);
      }
    },

    /**
     * @param {HTMLElement} element
     */
    setFormElement(element) {
      this.formElement = element;
    },

    /**
     * @param {Object} discussion
     */
    updateDiscussion(discussion) {
      apolloClient.writeQuery({
        query: getDiscussion,
        variables: {
          id: this.discussionId,
        },

        data: {
          discussion: discussion,
        },
      });
    },

    updateDiscussionComments() {
      let { discussion } = apolloClient.readQuery({
        query: getDiscussion,
        variables: {
          id: this.discussionId,
        },
      });

      discussion = Object.assign({}, discussion);
      discussion.total_comments += 1;

      apolloClient.writeQuery({
        query: getDiscussion,
        variables: {
          id: this.discussionId,
        },
        data: {
          discussion: discussion,
        },
      });
    },

    /**
     *
     * @param {Boolean} status
     */
    updateReactStatus({ status }) {
      let { discussion } = apolloClient.readQuery({
        query: getDiscussion,
        variables: {
          id: this.discussionId,
        },
      });

      discussion = Object.assign({}, discussion);

      if (status) {
        discussion.total_reactions += 1;
      } else if (0 != discussion.total_reactions) {
        discussion.total_reactions -= 1;
      }

      discussion.discussion_interactor = Object.assign(
        {},
        discussion.discussion_interactor
      );
      discussion.discussion_interactor.reacted = status;

      apolloClient.writeQuery({
        query: getDiscussion,
        variables: {
          id: this.discussionId,
        },
        data: {
          discussion: discussion,
        },
      });
    },

    /**
     * Redirect to a newly created workspace.
     * @param {Number} id
     */
    redirectToWorkspace({ id }) {
      document.location.href = this.$url(
        '/container/type/workspace/workspace.php',
        { id }
      );
    },
  },
};
</script>

<lang-strings>
  {
    "container_workspace": [
      "discussions",
      "back_to_discussions",
      "comment"
    ],

    "totara_comment": [
      "comments"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-workspaceDiscussionPage {
  &__column {
    margin-top: var(--gap-4);
  }

  &__backButton {
    @include tui-font-link();
    &:hover,
    &:focus {
      text-decoration: none;
    }
  }

  &__discussion {
    margin: var(--gap-8) 0;
  }

  &__title {
    @include tui-font-heading-x-small();
    margin: 0;
    margin-bottom: var(--gap-4);
  }

  &__commentBox {
    position: relative;
  }
}
</style>
