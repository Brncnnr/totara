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
  <div
    class="tui-commentBox"
    :class="{
      'tui-commentBox--withBorder': withBorder,
      'tui-commentBox--noForm': !commentAble || !showCommentForm,
    }"
  >
    <CommentThread
      ref="comment-thread"
      :size="size"
      :component="component"
      :area="area"
      :instance-id="instanceId"
      :submitting="innerSubmitting"
      :show-like-button="showLikeButton"
      :show-like-button-text="showLikeButtonText"
      :show-reply-button-text="showReplyButtonText"
      :with-border="withBorder"
      :first-cursor="firstCursor"
      :comment-inline-head="commentInlineHead"
      :editor="editor"
      class="tui-commentBox__comments"
      @update-submitting="$emit('update-submitting', $event)"
      @fetch-comments="$emit('fetch-comments', $event)"
      @update-comment="$emit('update-comment', $event)"
      @delete-comment="$emit('delete-comment', $event)"
      @add-reply="$emit('add-reply', $event)"
      @update-react-status="$emit('update-react-status', $event)"
    />

    <!-- The comment form will be available if the actor is able to add the comment. -->
    <template v-if="commentAble">
      <!-- Only start constructing the form after all the comments are loaded. -->
      <CommentForm
        v-if="!$apollo.queries.totalComments.loading && showCommentForm"
        ref="comment-form"
        :size="size"
        :editor="editor"
        :has-error="hasError"
        :submitting="innerSubmitting"
        :submit-button-text="submitFormButtonText"
        class="tui-commentBox__commentForm"
        :class="{
          'tui-commentBox__commentForm--withBorder': withBorder,
        }"
        @form-ready="formReady"
        @submit="createComment"
      />
    </template>
  </div>
</template>

<script>
import CommentForm from 'totara_comment/components/form/CommentForm';
import { isValid } from 'totara_comment/size';
import CommentThread from 'totara_comment/components/box/CommentThread';

// GraphQL queries
import getComments from 'totara_comment/graphql/get_comments';
import createComment from 'totara_comment/graphql/create_comment';

export default {
  components: {
    CommentThread,
    CommentForm,
  },

  apollo: {
    totalComments: {
      query: getComments,
      context: { batch: true },
      /**
       * A callback that will be used for emitting an event up to the parent.
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

      update({ cursor: { total } }) {
        return parseInt(total, 10);
      },

      result({
        data: {
          cursor: { total },
        },
      }) {
        this.$emit('update-total-comments', total);
      },
    },
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

    submitting: Boolean,
    showLikeButtonText: Boolean,
    showReplyButtonText: Boolean,
    submitFormButtonText: String,
    withBorder: Boolean,
    showCommentForm: {
      type: Boolean,
      default: true,
    },
    showLikeButton: {
      type: Boolean,
      default: true,
    },
    commentAble: {
      type: Boolean,
      default: true,
    },
    commentInlineHead: Boolean,
  },

  data() {
    return {
      totalComments: 0,
      innerSubmitting: this.Boolean,
      commentsLoaded: false,
      hasError: false,
    };
  },

  watch: {
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
    innerSubmitting(value) {
      if (value === this.submitting) {
        return;
      }

      this.$emit('update-submitting', value);
    },
  },

  methods: {
    /**
     * Scrolling the comments box to the bottom.
     */
    $_scrollCollectionToBottom() {
      this.$nextTick().then(() => {
        let component = this.$refs['comment-thread'];
        if (!component) {
          return;
        }

        component.$el.scrollTop = component.$el.scrollHeight;
      });
    },

    async formReady() {
      await this.$nextTick();
      let form = this.$refs['comment-form'];

      if (!form) {
        return;
      }

      if (form.$el) {
        form = form.$el;
      }

      this.$emit('form-ready', form);
    },

    /**
     *
     * @param {String} content
     * @param {Number} format
     * @param {Number} itemId
     */
    async createComment({ content, format, itemId }) {
      if (this.innerSubmitting) {
        return;
      }

      this.innerSubmitting = true;

      try {
        let {
          data: { comment },
        } = await this.$apollo.mutate({
          mutation: createComment,
          refetchAll: false,
          variables: {
            content: content,
            format: format,
            component: this.component,
            area: this.area,
            instanceid: this.instanceId,
            draft_id: itemId,
          },

          update: (proxy, { data: { comment } }) => {
            const variables = {
              component: this.component,
              area: this.area,
              instanceid: this.instanceId,
              cursor: this.firstCursor,
            };

            let { cursor, comments } = proxy.readQuery({
              query: getComments,
              variables: variables,
            });

            cursor = Object.assign({}, cursor);
            cursor.total += 1;

            proxy.writeQuery({
              query: getComments,
              variables: variables,
              data: {
                cursor: cursor,
                comments: Array.prototype.concat.call(comments, [comment]),
              },
            });
          },
        });

        this.$_scrollCollectionToBottom();
        this.$emit('create-comment', comment);
        this.hasError = false;
      } catch (e) {
        this.hasError = true;
        throw e;
      } finally {
        this.innerSubmitting = false;
      }
    },
  },
};
</script>

<lang-strings>
  {
    "totara_comment": [
      "comments"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-commentBox {
  height: 100%;

  // The parent that using this box should be able to override its padding.
  padding: 0;

  &--withBorder {
    background-color: var(--color-neutral-3);
    border: var(--border-width-thin) solid var(--color-neutral-5);

    &.tui-commentBox--noForm {
      // When the form is not available for the comment box, it will leave us a trailing border. Therefore, this
      // modifier is in place in order to prevent that traing border.
      border-bottom: none;
    }
  }

  &__comments {
    flex-grow: 1;
  }

  &__commentForm {
    flex-grow: 0;
    flex-shrink: 0;
    margin-top: var(--gap-4);

    &--withBorder {
      padding: var(--gap-4);
      padding-top: 0;
    }
  }
}
</style>
