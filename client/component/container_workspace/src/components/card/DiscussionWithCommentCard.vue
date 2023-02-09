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
  <article
    class="tui-workspaceDiscussionWithCommentCard"
    :data-id="discussionId"
  >
    <DiscussionCard
      :creator-fullname="creatorFullname"
      :creator-image-alt="creatorImageAlt || creatorFullname"
      :creator-image-src="creatorImageSrc"
      :creator-id="creatorId"
      :discussion-content="discussionContent"
      :time-description="timeDescription"
      :total-comments="totalComments"
      :total-reactions="totalReactions"
      :discussion-id="discussionId"
      :pinned="pinned"
      :react-able="reactAble"
      :delete-able="deleteAble"
      :update-able="updateAble"
      :comment-able="commentAble"
      :report-able="reportAble"
      :reacted="reacted"
      :removed="removed"
      :edited="edited"
      :label-id="labelId"
      class="tui-workspaceDiscussionWithCommentCard__discussionCard"
      @trigger-comment="triggerComment"
      @update-react-status="$emit('update-discussion-react-status', $event)"
      @update-discussion="$emit('update-discussion', $event)"
      @delete-discussion="$emit('delete-discussion', $event)"
    />

    <CommentBox
      v-if="showComments || showCommentForm"
      ref="comment-box"
      :instance-id="discussionId"
      component="container_workspace"
      area="discussion"
      :size="size"
      :show-reply-button-text="true"
      :show-like-button-text="true"
      :comment-able="commentAble"
      :submit-form-button-text="$str('comment', 'container_workspace')"
      :with-border="true"
      :show-comment-form="showCommentForm"
      :first-cursor="firstCommentCursor"
      :comment-inline-head="true"
      :editor="{
        variant: 'standard',
        compact: false,
        contextId: workspaceContextId,
        extraExtensions: ['hashtag', 'mention'],
      }"
      class="tui-workspaceDiscussionWithCommentCard__comments"
      @form-ready="setFormElement"
      @create-comment="$emit('add-new-comment', discussionId)"
    />
  </article>
</template>

<script>
import DiscussionCard from 'container_workspace/components/card/DiscussionCard';
import CommentBox from 'totara_comment/components/box/CommentBox';
import { SIZE_LARGE } from 'totara_comment/size';

export default {
  components: {
    DiscussionCard,
    CommentBox,
  },

  props: {
    firstCommentCursor: {
      type: String,
      default: null,
    },
    creatorImageSrc: {
      type: String,
      required: true,
    },

    creatorImageAlt: {
      type: String,
      default: '',
    },

    /**
     * The discussion's creator' id - in short user's id of this discussion's creator.
     */
    creatorId: {
      type: [String, Number],
      required: true,
    },

    creatorFullname: {
      type: String,
      required: true,
    },

    discussionContent: {
      type: String,
      required: true,
    },

    timeDescription: {
      type: String,
      required: true,
    },

    pinned: Boolean,
    reportAble: Boolean,
    reacted: Boolean,
    removed: Boolean,

    totalComments: {
      type: [String, Number],
      required: true,
    },

    totalReactions: {
      type: [String, Number],
      required: true,
    },

    discussionId: {
      type: [String, Number],
      required: true,
    },

    reactAble: {
      type: Boolean,
      default: true,
    },

    updateAble: {
      type: Boolean,
      default: true,
    },

    deleteAble: {
      type: Boolean,
      default: true,
    },

    commentAble: {
      type: Boolean,
      default: true,
    },

    edited: Boolean,

    labelId: String,
    workspaceContextId: [String, Number],
  },

  data() {
    return {
      showCommentForm: false,
      size: SIZE_LARGE,

      // We need to track the form element, in order to allow us scroll into view easily.
      formElement: null,
    };
  },

  computed: {
    /**
     * @return {Boolean}
     */
    showComments() {
      const total = parseInt(this.totalComments, 10);
      return total > 0;
    },
  },

  methods: {
    /**
     * Set the form element into this component, so that we can scroll to its view easily.
     *
     * @param {HTMLElement} element
     */
    setFormElement(element) {
      if (null !== this.formElement) {
        return;
      }

      this.formElement = element;
    },

    async triggerComment() {
      this.showCommentForm = true;

      // Waiting for this component is being rendered, then start checking for the state of the comment box.
      await this.$nextTick();

      if (this.formElement) {
        // Now we can scrolling.
        this.formElement.scrollIntoView(false);
      }
    },
  },
};
</script>

<lang-strings>
  {
    "container_workspace": [
      "comment"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-workspaceDiscussionWithCommentCard {
  display: flex;
  flex-direction: column;
  width: 100%;

  &__comments {
    width: 100%;
    &.tui-commentBox {
      &--withBorder {
        // Remove the border-top of the comment box.
        border-top: none;
      }
    }
  }
}
</style>
