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
  <div ref="comments" class="tui-commentThread">
    <CommentActionLink
      :size="size"
      :show-load-more="hasMoreComments"
      :loading="$apollo.queries.thread.loading"
      class="tui-commentThread__actionLink"
      :class="{
        'tui-commentThread__actionLink--withBorder': withBorder,
      }"
      @load-more="loadMore"
    />

    <div class="tui-commentThread__comments">
      <div
        v-if="$apollo.queries.thread.loading"
        class="tui-commentThread__loading"
      >
        <Loading />
      </div>

      <template v-for="(comment, index) in thread.comments">
        <!-- The only we do not want to use index as the key here is that because  -->
        <Comment
          :key="comment.id"
          :data-counter="index"
          :content="comment.content"
          :comment-id="comment.id"
          :user-full-name="comment.user.fullname"
          :user-id="comment.user.id"
          :user-profile-image-url="comment.user.profileimageurl"
          :user-profile-image-alt="comment.user.profileimagealt"
          :time-description="comment.timedescription"
          :total-replies="comment.totalreplies"
          :delete-able="comment.interactor.can_delete"
          :update-able="comment.interactor.can_update"
          :report-able="comment.interactor.can_report"
          :reply-able="comment.interactor.can_reply"
          :react-able="comment.interactor.can_react"
          :edited="comment.edited"
          :deleted="comment.deleted"
          :component="component"
          :area="area"
          :size="size"
          :instance-id="instanceId"
          :submitting="submitting"
          :total-reactions="comment.totalreactions"
          :reacted="comment.interactor.reacted"
          :show-like-button="showLikeButton"
          :show-like-button-text="showLikeButtonText"
          :show-reply-button-text="showReplyButtonText"
          :inline-head="commentInlineHead"
          :can-view-author="comment.interactor.can_view_author"
          :editor="editor"
          class="tui-commentThread__comment"
          :class="{
            'tui-commentThread__comment--large': isLarge,
            'tui-commentThread__comment--withBorderBottom': withBorder,
          }"
          @update-submitting="$emit('update-submitting', $event)"
          @update-comment="$emit('update-comment', $event)"
          @delete-comment="$emit('delete-comment', $event)"
          @add-reply="addReplyToComment"
          @update-react-status="updateReactStatus"
        />
      </template>
    </div>
  </div>
</template>

<script>
import Comment from 'totara_comment/components/comment/Comment';
import Loading from 'tui/components/icons/Loading';
import { isValid, SIZE_LARGE } from 'totara_comment/size';
import CommentActionLink from 'totara_comment/components/action/CommentActionLink';
import apolloClient from 'tui/apollo_client';

// GraphQL queries
import getComments from 'totara_comment/graphql/get_comments';

export default {
  components: {
    Comment,
    CommentActionLink,
    Loading,
  },

  props: {
    /**
     * This prop is to help the thread to restrict how many comments to be loaded for the
     * first time.
     */
    firstCursor: {
      type: String,
      default: null,
    },

    size: {
      type: String,
      required: true,
      validator(prop) {
        return isValid(prop);
      },
    },

    component: {
      type: String,
      required: true,
    },

    area: {
      type: String,
      required: true,
    },

    instanceId: {
      type: [String, Number],
      required: true,
    },

    submitting: Boolean,
    showLikeButtonText: Boolean,
    showReplyButtonText: Boolean,
    withBorder: Boolean,
    commentInlineHead: Boolean,
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
  },

  apollo: {
    thread: {
      query: getComments,
      context: { batch: true },
      /**
       * A callback to let the parent know that this component is fetching the comments.
       * Note that if the parent is already fetching this query, then this callback will never be triggered.
       *
       * @param {Boolean} isLoading
       */
      watchLoading(isLoading) {
        this.$emit('fetch-comments', isLoading);
      },
      variables() {
        return {
          component: this.component,
          area: this.area,
          instanceid: this.instanceId,
          cursor: this.firstCursor,
        };
      },

      /**
       * @param {Number} count
       * @param {Array} comments
       */
      update({ cursor, comments }) {
        return {
          cursor: cursor,
          comments: comments,
        };
      },

      /**
       *
       * @param {Object} cursor
       * @param {Array}  comments
       */
      result({ data: { comments, cursor } }) {
        // Emit the event up to the parent to tell how many comments had been loaded.
        this.hasMoreComments = comments.length < cursor.total;
        this.$emit('update-total-comments', cursor.total);
      },
    },
  },

  data() {
    return {
      loading: 0,
      hasMoreComments: false,
      thread: {
        comments: [],
        cursor: null,
      },
    };
  },

  computed: {
    isLarge() {
      return SIZE_LARGE === this.size;
    },
  },

  methods: {
    async loadMore() {
      await this.$apollo.queries.thread.fetchMore({
        variables: {
          component: this.component,
          area: this.area,
          instanceid: this.instanceId,
          cursor: this.thread.cursor.next,
        },

        updateQuery: (previous, { fetchMoreResult: { comments, cursor } }) => {
          let allComments = Array.prototype.slice.call(previous.comments);

          // Filtering out all the comments that had been loaded duplicated.
          // Back-end code should be taken care of this. This is another layer to save us from
          // displaying duplicated comments on the screen.
          comments = Array.prototype.filter.call(comments, comment => {
            let existingComment = Array.prototype.find.call(
              allComments,
              innerComment => {
                return comment.id == innerComment.id;
              }
            );

            return 'undefined' === typeof existingComment;
          });

          comments = Array.prototype.concat.call(comments, allComments);
          this.hasMoreComments = comments.length < cursor.total;

          return {
            comments: comments,
            cursor: cursor,
          };
        },
      });
    },

    /**
     *
     * @param {Number} commentId
     */
    addReplyToComment(commentId) {
      // TODO: TL-33188 cache updates superfluous
      const variables = {
        instanceid: this.instanceId,
        component: this.component,
        area: this.area,
        cursor: this.firstCursor,
      };

      let { comments, cursor } = apolloClient.readQuery({
        query: getComments,
        variables: variables,
      });

      apolloClient.writeQuery({
        query: getComments,
        variables: variables,
        data: {
          cursor: cursor,
          comments: Array.prototype.map.call(comments, comment => {
            if (commentId == comment.id) {
              let innerComment = Object.assign({}, comment);
              innerComment.totalreplies += 1;

              return innerComment;
            }

            return comment;
          }),
        },
      });

      this.$emit('add-reply', commentId);
    },

    /**
     *
     * @param {Number}  id      => The comment's id
     * @param {Boolean} status
     */
    updateReactStatus({ id, status }) {
      // TODO: TL-33188 cache updates superfluous
      const variables = {
        instanceid: this.instanceId,
        component: this.component,
        area: this.area,
        cursor: this.firstCursor,
      };

      let { comments, cursor } = apolloClient.readQuery({
        query: getComments,
        variables: variables,
      });

      apolloClient.writeQuery({
        query: getComments,
        variables: variables,
        data: {
          cursor: cursor,
          comments: Array.prototype.map.call(comments, comment => {
            if (comment.id == id) {
              let innerComment = Object.assign({}, comment),
                interactor = Object.assign({}, innerComment.interactor);

              interactor.reacted = status;
              innerComment.interactor = interactor;

              if (status) {
                innerComment.totalreactions += 1;
              } else if (0 != innerComment.totalreactions) {
                innerComment.totalreactions -= 1;
              }

              return innerComment;
            }

            return comment;
          }),
        },
      });

      this.$emit('update-react-status', { id, status });
    },
  },
};
</script>

<style lang="scss">
.tui-commentThread {
  &__actionLink {
    &--withBorder {
      margin-top: var(--gap-4);
      margin-left: var(--gap-4);
    }
  }

  &__comment {
    &--large {
      padding: 0 var(--gap-4);
      padding-bottom: var(--gap-4);
    }

    &--withBorderBottom {
      border-bottom: var(--border-width-thin) solid var(--color-neutral-5);
    }
  }

  &__loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--gap-4);
    color: var(--color-neutral-6);
  }
}
</style>
