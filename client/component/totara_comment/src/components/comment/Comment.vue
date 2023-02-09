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
  @module totara_comment
-->
<template>
  <CommentCard class="tui-comment" :data-id="commentId">
    <Avatar
      slot="profile-picture"
      :image-src="userProfileImageUrl"
      :image-alt="userProfileImageAlt || userFullName"
      :profile-url="profileUrl"
    />

    <CommentHeader
      slot="body-header"
      :size="size"
      :profile-url="profileUrl"
      :user-full-name="userFullName"
      :time-description="timeDescription"
      :update-able="updateAble"
      :delete-able="deleteAble"
      :report-able="reportAble"
      :edited="edited"
      :inline-head="inlineHead"
      :submitting="innerSubmitting"
      :open-modal="deleteModalOpen"
      @close-complete="innerSubmitting = false"
      @open-delete-modal="deleteModalOpen = true"
      @close-delete-modal="deleteModalOpen = false"
      @click-edit="showForm.comment = true"
      @confirm-delete="deleteComment"
      @click-report-content="reportComment"
    />

    <CommentContent
      slot="body-content"
      :deleted="deleted"
      :item-id="commentId"
      :content="content"
      :on-edit="showForm.comment"
      :is-reply="false"
      :size="size"
      :editor="editor"
      :has-error="updateError"
      @cancel-editing="showForm.comment = false"
      @update-item="updateComment"
    />

    <CommentAction
      v-if="canInteract"
      slot="body-footer"
      :total-replies="totalReplies"
      :size="size"
      :comment-id="commentId"
      :total-reactions="totalReactions"
      :reacted="reacted"
      :react-able="reactAble"
      :reply-able="replyAble"
      :show-like-button="showLikeButton"
      :show-like-button-text="showLikeButtonText"
      :show-reply-button-text="showReplyButtonText"
      area="comment"
      class="tui-comment__footer"
      @click-reply="handleReply"
      @update-react-status="updateReactStatus"
    />

    <ReplyBox
      ref="reply-box"
      slot="reply-box"
      :reply-able="replyAble"
      :comment-id="commentId"
      :area="area"
      :component="component"
      :show-reply-form="showForm.reply"
      :total-replies="totalReplies"
      :submitting="innerSubmitting"
      :size="size"
      :show-like-button="showLikeButton"
      :show-like-button-text="showLikeButtonText"
      :show-reply-button-text="showReplyButtonText"
      :reply-to="replyTo"
      :reply-head-inline="inlineHead"
      :editor="editor"
      class="tui-comment__replyBox"
      :open-modal="deleteModalOpen"
      @close-complete="innerSubmitting = false"
      @open-delete-modal="deleteModalOpen = true"
      @close-delete-modal="deleteModalOpen = false"
      @update-show-reply-form="showForm.reply = $event"
      @create-reply="$emit('add-reply', commentId)"
      @update-submitting="$emit('update-submitting', $event)"
      @form-ready="replyFormReady"
      @update-reply-to="replyTo = $event"
      @scroll-to-reply-form="scrollToReplyForm"
      @fetch-replies="fetchingReplies = $event"
    />
  </CommentCard>
</template>

<script>
import CommentCard from 'totara_comment/components/card/CommentCard';
import Avatar from 'totara_comment/components/profile/CommentAvatar';
import CommentAction from 'totara_comment/components/action/CommentAction';
import { isValid, SIZE_SMALL } from 'totara_comment/size';
import CommentHeader from 'totara_comment/components/card/CommentReplyHeader';
import CommentContent from 'totara_comment/components/content/CommentReplyContent';
import ReplyBox from 'totara_comment/components/box/ReplyBox';
import { notify } from 'tui/notifications';
import { shouldAnimate } from 'tui/dom/transitions';
import pending from 'tui/pending';

// GraphQL queries
import updateComment from 'totara_comment/graphql/update_comment';
import deleteComment from 'totara_comment/graphql/delete_comment';
import createReview from 'totara_reportedcontent/graphql/create_review';

export default {
  components: {
    ReplyBox,
    CommentHeader,
    CommentAction,
    CommentCard,
    Avatar,
    CommentContent,
  },

  props: {
    commentId: {
      required: true,
      type: [String, Number],
    },

    component: {
      type: String,
      required: true,
    },

    area: {
      type: String,
      required: true,
    },

    /**
     * This is the instance's id where the comment is being used.
     */
    instanceId: {
      type: [String, Number],
      required: true,
    },

    submitting: Boolean,

    totalReplies: {
      type: [Number, String],
      required: true,
    },

    totalReactions: [Number, String],
    reacted: Boolean,

    timeDescription: {
      type: String,
      required: true,
    },

    reportAble: {
      type: Boolean,
      required: true,
    },

    content: {
      type: String,
      required: true,
    },

    deleteAble: {
      type: Boolean,
      required: true,
    },

    updateAble: {
      type: Boolean,
      required: true,
    },

    replyAble: {
      type: Boolean,
      default: true,
    },

    reactAble: {
      type: Boolean,
      default: true,
    },

    userFullName: {
      type: String,
      required: true,
    },

    userId: {
      type: [String, Number],
      required: true,
    },

    userProfileImageUrl: {
      type: String,
      required: true,
    },

    userProfileImageAlt: {
      type: String,
    },

    size: {
      type: String,
      default() {
        return SIZE_SMALL;
      },
      validator(prop) {
        return isValid(prop);
      },
    },

    edited: {
      type: Boolean,
      required: true,
    },

    deleted: {
      type: Boolean,
      required: true,
    },

    showLikeButton: {
      type: Boolean,
      default: true,
    },

    /**
     * Editor setting, do not modify this object.
     */
    editor: {
      type: Object,
      validator: prop => 'compact' in prop && 'variant' in prop,
      default() {
        return {
          compact: true,
          variant: undefined,
          contextId: undefined,
        };
      },
    },

    showLikeButtonText: Boolean,
    showReplyButtonText: Boolean,
    inlineHead: Boolean,
    canViewAuthor: Boolean,
  },

  data() {
    return {
      showForm: {
        reply: false,
        comment: false,
      },
      // Caching the innerSubmitting.
      innerSubmitting: this.submitting,
      replyTo: null,

      // We need to control the form from the children up here, so that we can scroll to the element
      // as many times as we want and easily.
      replyForm: null,

      // A flag to tell whether the reply box is fetching the replies or not.
      fetchingReplies: false,
      deleteModalOpen: false,
      updateError: false,
    };
  },

  computed: {
    canInteract() {
      return this.replyAble || this.reactAble;
    },

    /**
     * @return {String|undefined}
     */
    profileUrl() {
      if (!this.canViewAuthor) {
        return undefined;
      }

      return this.$url('/user/profile.php', { id: this.userId });
    },
  },

  watch: {
    /**
     * @param {Boolean} value
     */
    innerSubmitting(value) {
      if (value === this.submitting) {
        return;
      }

      this.$emit('update-submitting', value);
    },

    /**
     * @param {Boolean} value
     */
    submitting(value) {
      this.innerSubmitting = value;
    },
  },

  methods: {
    async $_completeFetchingReplies() {
      if (!this.fetchingReplies) {
        // Replies are not fetching, so we can skip this one.
        return;
      }

      let complete = pending('totara_comment_fetching_replies'),
        callbackLoop = resolve => {
          setTimeout(() => {
            if (!this.fetchingReplies) {
              // Righty, not fetching anymore, we can just finish it here.
              complete();
              resolve();
            } else {
              // Otherwise keep going on until it is done.
              callbackLoop(resolve);
            }
          }, 100);
        };

      return new Promise(callbackLoop);
    },

    /**
     * @param {String} content
     * @param {Number} id       => Comment's id
     * @param {Number} format
     * @param {Number} itemId   => File storage draft's id.
     */
    async updateComment({ content, id, format, itemId }) {
      if (this.innerSubmitting) {
        return;
      }

      this.innerSubmitting = true;

      try {
        let {
          data: { comment },
        } = await this.$apollo.mutate({
          mutation: updateComment,
          refetchAll: false,
          variables: {
            id: id,
            content: content,
            format: format,
            draft_id: itemId,
          },
        });

        this.$emit('update-comment', comment);
        this.showForm.comment = false;
        this.updateError = false;
      } catch (e) {
        this.updateError = true;
        throw e;
      } finally {
        this.innerSubmitting = false;
      }
    },

    async deleteComment() {
      if (this.innerSubmitting) {
        return;
      }

      this.innerSubmitting = true;
      try {
        let {
          data: { comment },
        } = await this.$apollo.mutate({
          mutation: deleteComment,
          refetchAll: false,
          variables: {
            id: this.commentId,
          },
        });

        this.$emit('delete-comment', comment);
      } finally {
        this.deleteModalOpen = false;
      }
    },

    async reportComment() {
      if (this.innerSubmitting) {
        return;
      }

      this.innerSubmitting = true;
      try {
        let response = await this.$apollo
          .mutate({
            mutation: createReview,
            refetchAll: false,
            variables: {
              // Comments & replies are all handled in the same place
              component: this.component,
              area: 'comment',
              item_id: this.commentId,
              url: window.location.href,
            },
          })
          .then(response => response.data.review);

        if (response.success) {
          await notify({
            message: this.$str('reported', 'totara_reportedcontent'),
            type: 'success',
          });
        } else {
          await notify({
            message: this.$str('reported_failed', 'totara_reportedcontent'),
            type: 'error',
          });
        }
      } catch (e) {
        await notify({
          message: this.$str('error:reportcomment', 'totara_comment'),
          type: 'error',
        });
      } finally {
        this.innerSubmitting = false;
      }
    },

    /**
     *
     * @param {Boolean} status
     */
    updateReactStatus(status) {
      this.$emit('update-react-status', { status: status, id: this.commentId });
    },

    /**
     * Takes the user to the reply form once loaded
     *
     * @param {Object} event The reply-form event object
     */
    replyFormReady(event) {
      let replyBox = this.$refs['reply-box'];
      this.replyForm = event;

      replyBox.$el.lastChild.scrollIntoView({
        block: 'nearest',
        behavior: shouldAnimate() ? 'smooth' : 'auto',
      });
    },

    /**
     *
     * This function is only being called in here. Therefore, we will set the showForm to true
     * and reset the replyTo to null if it has value.
     */
    async handleReply() {
      this.showForm.reply = true;
      if (null !== this.replyTo) {
        this.replyTo = null;
      }

      await this.scrollToReplyForm();
    },

    /**
     * Making the browser to scroll to this reply form. However, we need to make sure that the reply box has
     * finished rendering with updated data so that the form can be scroll easily.
     */
    async scrollToReplyForm() {
      let replyBox = this.$refs['reply-box'];
      if (!replyBox) {
        return;
      }

      await replyBox.$nextTick();
      if (this.fetchingReplies) {
        // So the reply box is fetching the content. Time to check if the loading is complete then start checking
        // the rendering state and eventually we are able to scroll.
        await this.$_completeFetchingReplies();
        await replyBox.$nextTick();
      }

      replyBox.$el.lastChild.scrollIntoView({
        block: 'nearest',
        behavior: shouldAnimate() ? 'smooth' : 'auto',
      });
    },
  },
};
</script>

<lang-strings>
{
  "totara_comment": [
    "error:reportcomment"
  ],
  "totara_reportedcontent": [
    "reported",
    "reported_failed"
  ]
}
</lang-strings>

<style lang="scss">
.tui-comment {
  margin-top: var(--gap-4);

  &__replyBox {
    padding-left: var(--gap-8);
  }
}
</style>
