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
  <div class="tui-commentReplyBox">
    <div
      v-if="showLoadRepliesLink || (loadRepliesStatus && hasMoreReplies)"
      class="tui-commentReplyBox__replyLinkBox"
      :class="{
        'tui-commentReplyBox__replyLinkBox--withSeparator': showLineSeparator,
        'tui-commentReplyBox__replyLinkBox--withoutSeparator': !showLineSeparator,
      }"
    >
      <a
        href="#"
        role="button"
        class="tui-commentReplyBox__replyLinkBox-link"
        :class="{
          'tui-commentReplyBox__replyLinkBox-link--disabled':
            $apollo.queries.replies.loading,
        }"
        @click.prevent="loadReplies"
      >
        <span>{{ $str('viewreplies', 'totara_comment') }}</span>
        <Loading v-if="$apollo.queries.replies.loading" />
      </a>
    </div>

    <template v-if="0 < replies.length">
      <template v-for="(reply, index) in replies">
        <Reply
          :key="reply.id"
          :data-counter="index"
          :reply-id="reply.id"
          :content="reply.content"
          :comment-id="reply.commentid"
          :component="component"
          :time-description="reply.timedescription"
          :user-full-name="reply.user.fullname"
          :user-profile-image-url="reply.user.profileimageurl"
          :user-profile-image-alt="
            reply.user.profileimagealt || reply.user.fullname
          "
          :user-id="reply.user.id"
          :update-able="reply.interactor.can_update"
          :delete-able="reply.interactor.can_delete"
          :report-able="reply.interactor.can_report"
          :reply-able="reply.interactor.can_follow_reply"
          :react-able="reply.interactor.can_react"
          :can-view-author="reply.interactor.can_view_author"
          :total-reactions="reply.totalreactions"
          :reacted="reply.interactor.reacted"
          :edited="reply.edited"
          :deleted="reply.deleted"
          :reason-deleted="reply.reasondeleted"
          :size="size"
          :submitting="innerSubmitting"
          :show-reply-button-text="showReplyButtonText"
          :show-like-button-text="showLikeButtonText"
          :show-like-button="showLikeButton"
          :inline-head="replyHeadInline"
          :editor="editor"
          class="tui-commentReplyBox__reply"
          :class="{
            'tui-commentReplyBox__reply--large': isLarge,
          }"
          @update-submitting="$emit('update-submitting', $event)"
          @update-reply="updateRepliesCache"
          @delete-reply="updateRepliesCache"
          @reply-to="handleReplyTo"
          @update-react-status="updateReactStatus"
        />
      </template>
    </template>

    <template v-if="replyAble">
      <ReplyForm
        v-if="innerShowReplyForm"
        ref="reply-form"
        :comment-id="commentId"
        :size="size"
        :editor="editor"
        :reply-to="innerReplyTo"
        :has-error="hasError"
        class="tui-commentReplyBox__replyForm"
        @cancel="$emit('update-show-reply-form', false)"
        @submit="createReply"
        @form-ready="handleFormReady"
      />
    </template>
  </div>
</template>

<script>
import ReplyForm from 'totara_comment/components/form/ReplyForm';
import { isValid, SIZE_SMALL, SIZE_LARGE } from 'totara_comment/size';
import Loading from 'tui/components/icons/Loading';
import Reply from 'totara_comment/components/reply/Reply';
import apolloClient from 'tui/apollo_client';

// GraphQL queries
import getReplies from 'totara_comment/graphql/get_replies';
import createReply from 'totara_comment/graphql/create_reply';

export default {
  components: {
    ReplyForm,
    Loading,
    Reply,
  },

  props: {
    component: {
      type: String,
      required: true,
    },

    area: {
      type: String,
      required: true,
    },

    commentId: {
      type: [Number, String],
      required: true,
    },

    totalReplies: {
      type: [Number, String],
      required: true,
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

    /**
     * A prop to allow the communication between children and parent to see whether this component is toggling the
     * reply form or not.
     */
    showReplyForm: {
      type: Boolean,
      required: true,
    },

    /**
     * This is a prop to tell we are going to enable the form or not.
     * Only enable the form when the actor is able reply, other than that the form will never be shown.
     */
    replyAble: {
      type: Boolean,
      required: true,
    },

    submitting: Boolean,
    showLikeButton: {
      type: Boolean,
      default: true,
    },
    showLikeButtonText: Boolean,
    showReplyButtonText: Boolean,
    replyTo: null,
    replyHeadInline: Boolean,
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
        };
      },
    },
  },

  apollo: {
    replies: {
      query: getReplies,

      /**
       * We need to emit an event up to the parent, to know that the replies are being fecthed.
       * So that the parent component can define whether it is time to make the scroll or not.
       *
       * @param {Boolean} isLoading
       */
      watchLoading(isLoading) {
        this.$emit('fetch-replies', isLoading);
      },

      variables() {
        return {
          commentid: this.commentId,
        };
      },

      result({ data: { replies } }) {
        this.hasMoreReplies = replies.length < this.totalReplies;
      },

      skip() {
        return !this.loadRepliesStatus;
      },
    },
  },

  data() {
    return {
      replies: [],
      page: 1,
      loadRepliesStatus: false,
      hasMoreReplies: false,
      innerSubmitting: this.submitting,
      innerShowReplyForm: this.showReplyForm,
      innerReplyTo: this.replyTo,
      hasError: false,
    };
  },

  computed: {
    /**
     * @return {Boolean}
     */
    isLarge() {
      return SIZE_LARGE === this.size;
    },

    /**
     *
     * @return {Boolean}
     */
    hasReplies() {
      return 0 < parseInt(this.totalReplies, 10);
    },

    /**
     * @return {Boolean}
     */
    showLoadRepliesLink() {
      if (this.$apollo.queries.replies.loading && 1 == this.page) {
        // When the replies is only being loaded for the first time, we still want
        // the link to be shown but disabled, untill it finishes loading.
        return true;
      }

      return this.hasReplies && !this.loadRepliesStatus;
    },

    /**
     * Either we are showing the replies link or there are more replies to loaded.
     *
     * @return {Boolean}
     */
    showLineSeparator() {
      return this.showLoadRepliesLink || this.hasMoreReplies;
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
      if (value === this.innerSubmitting) {
        return;
      }

      this.innerSubmitting = value;
    },

    /**
     * @param {Boolean} value
     */
    innerShowReplyForm(value) {
      if (value) {
        if (!this.loadRepliesStatus) {
          // Whenever showing the reply form, we need to load the replies first if it is not
          // being loaded. Then after that we will have to wait for the replies to be loaded and show the form.
          // However, Apollo needs a bit of time to actually trigger the loading.
          this.loadRepliesStatus = true;
        }
      }

      if (value !== this.showReplyForm) {
        // Update the status if they are not sync.
        this.$emit('update-show-reply-form', value);
      }

      if (value) {
        // If it is about showing the form, then we are going to wait for the page to render
        // Then $emit an event to make the parent scroll to here.
        this.$_scrollToForm();
      }
    },

    /**
     * @param {Boolean} value
     */
    showReplyForm(value) {
      if (value === this.innerShowReplyForm) {
        return;
      }

      this.innerShowReplyForm = value;

      // We need to update the replyTo everytime the parent is updating the prop that is
      // out-of-sync with this children props.
      this.innerReplyTo = null;
    },

    /**
     * @param {Object} item
     */
    replyTo(item) {
      this.innerReplyTo = item;
    },

    /**
     * @param {Object} item
     */
    innerReplyTo(item) {
      if (item === this.replyTo) {
        return;
      }

      this.$emit('update-reply-to', item);
    },
  },

  methods: {
    handleFormReady() {
      let form = this.$refs['reply-form'];

      if (!form) {
        return;
      } else if (form.$el) {
        form = form.$el;
      }

      this.$emit('form-ready', form);
    },

    /**
     * Emit an event to let the parent scroll to the form.
     */
    $_scrollToForm() {
      this.$emit('scroll-to-reply-form');
    },

    async loadReplies() {
      if (!this.loadRepliesStatus) {
        this.loadRepliesStatus = true;
        return;
      }

      // Start load more replies
      this.page += 1;
      await this.$apollo.queries.replies.fetchMore({
        variables: {
          page: this.page,
          commentid: this.commentId,
        },

        updateQuery: (previous, { fetchMoreResult: { replies } }) => {
          replies = Array.prototype.concat.call(replies, previous.replies);
          this.hasMoreReplies = replies.length < this.totalReplies;

          return {
            replies: replies,
          };
        },
      });
    },

    /**
     * @param {Object} reply
     */
    updateRepliesCache(reply) {
      const variables = {
        commentid: this.commentId,
      };

      let { replies } = apolloClient.readQuery({
        query: getReplies,
        variables: variables,
      });

      apolloClient.writeQuery({
        query: getReplies,
        variables: variables,
        data: {
          replies: Array.prototype.map.call(replies, innerReply => {
            if (innerReply.id == reply.id) {
              return reply;
            }

            return innerReply;
          }),
        },
      });

      this.$emit('update-reply', reply);
    },

    /**
     *
     * @param {String}  content
     * @param {Number}  format
     * @param {Number}  commentId
     * @param {Number}  itemId
     *
     * @return {Promise<void>}
     */
    async createReply({ content, format, commentId, itemId }) {
      if (this.innerSubmitting) {
        return;
      }

      this.innerSubmitting = true;

      try {
        let {
          data: { reply },
        } = await this.$apollo.mutate({
          mutation: createReply,
          refetchAll: false,
          variables: {
            commentid: commentId,
            content: content,
            format: format,
            draft_id: itemId,
          },

          update: (proxy, { data }) => {
            let { replies } = proxy.readQuery({
              query: getReplies,
              variables: { commentid: this.commentId },
            });

            proxy.writeQuery({
              query: getReplies,
              variables: { commentid: this.commentId },
              data: {
                replies: Array.prototype.concat.call(replies, [data.reply]),
              },
            });
          },
        });

        // Hide the reply form after we finish with the server request.
        this.innerShowReplyForm = false;
        this.$emit('create-reply', reply);
        this.hasError = false;
      } catch (e) {
        this.hasError = true;
        throw e;
      } finally {
        this.innerSubmitting = false;
      }
    },

    /**
     *
     * @param {{
     *   fullname: String,
     *   userId: [String, Number]
     * }} params
     */
    handleReplyTo(params) {
      this.innerReplyTo = params;

      if (this.innerShowReplyForm) {
        // The reply form has already been shown. So try to scroll to form.
        this.$_scrollToForm();
      }

      this.innerShowReplyForm = true;
    },

    /**
     *
     * @param {Number}  id      => The reply's id
     * @param {Boolean} status
     */
    updateReactStatus({ id, status }) {
      let { replies } = apolloClient.readQuery({
        query: getReplies,
        variables: { commentid: this.commentId },
      });

      apolloClient.writeQuery({
        query: getReplies,
        variables: { commentid: this.commentId },
        data: {
          replies: Array.prototype.map.call(replies, reply => {
            if (reply.id == id) {
              let innerReply = Object.assign({}, reply),
                interactor = Object.assign({}, reply.interactor);

              interactor.reacted = status;
              innerReply.interactor = interactor;

              if (status) {
                innerReply.totalreactions += 1;
              } else if (0 != innerReply.totalreactions) {
                innerReply.totalreactions -= 1;
              }

              return innerReply;
            }

            return reply;
          }),
        },
      });
    },
  },
};
</script>

<lang-strings>
  {
    "totara_comment": [
      "viewreplies"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-commentReplyBox {
  display: flex;
  flex-direction: column;

  &__replyLinkBox {
    display: flex;
    margin-top: var(--gap-2);
    padding-top: var(--gap-2);

    &--withSeparator {
      border-top: var(--border-width-thin) solid var(--color-neutral-4);
    }

    &--withoutSeparator {
      // Hide the line, but keep the position as same.
      border-top: var(--border-width-thin) solid transparent;
    }

    &-link {
      @include tui-font-link-small();

      &--disabled {
        color: var(--color-neutral-5);
        cursor: not-allowed;
        &:hover,
        &:focus {
          color: var(--color-neutral-5);
          text-decoration: none;
        }
      }
    }
  }

  &__reply {
    padding-top: var(--gap-4);
    border-top: var(--border-width-thin) solid var(--color-neutral-5);

    &--large {
      // Override the margin-top for the reply card.
      &.tui-commentReply {
        margin-top: var(--gap-6);
      }
    }
  }

  &__replyForm {
    width: 100%;
  }
}
</style>
