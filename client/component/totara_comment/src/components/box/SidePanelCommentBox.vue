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
  <div class="tui-sidePanelCommentBox">
    <h4 class="tui-sidePanelCommentBox__header">
      <span v-if="commentsLoading">{{
        $str('comments', 'totara_engage')
      }}</span>
      <span v-else>
        {{ $str('comments', 'totara_comment', totalComments) }}</span
      >
      <Loading v-if="submitting" />
    </h4>

    <CommentBox
      :instance-id="instanceId"
      :component="component"
      :area="area"
      :size="size"
      :submitting="submitting"
      :editor="{
        compact: true,
        variant: editorVariant,
        extraExtensions: extraExtensions,
        contextId: editorContextId,
      }"
      :show-comment-form="showCommentForm"
      :show-like-button="showLikeButton"
      :comment-able="reallyCommentAble"
      class="tui-sidePanelCommentBox__box"
      @fetch-comments="commentsLoading = $event"
      @update-total-comments="totalComments = $event"
      @update-submitting="submitting = $event"
      @create-comment="$emit('create-comment', $event)"
      @update-comment="$emit('update-comment', $event)"
      @delete-comment="$emit('delete-comment', $event)"
      @add-reply="$emit('add-reply', $event)"
      @update-react-status="$emit('update-react-status', $event)"
    />
  </div>
</template>

<script>
import CommentBox from 'totara_comment/components/box/CommentBox';
import { SIZE_SMALL } from 'totara_comment/size';
import Loading from 'tui/components/icons/Loading';

export default {
  components: {
    CommentBox,
    Loading,
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

    instanceId: {
      type: [String, Number],
      required: true,
    },

    editorVariant: {
      type: String,
      default: 'basic',
    },

    editorContextId: [String, Number],

    showCommentForm: {
      type: Boolean,
      default: true,
    },

    showComment: {
      type: Boolean,
      default: true,
    },

    showLikeButton: {
      type: Boolean,
      default: true,
    },

    extraExtensions: Array,

    /**
     * Deprecated - use commentAble instead
     *
     * @deprecated since 14.4
     */
    interactor: {
      type: Object,
    },
  },

  data() {
    return {
      size: SIZE_SMALL,
      totalComments: 0,
      submitting: false,
      commentsLoading: false,
    };
  },

  computed: {
    reallyCommentAble() {
      return Boolean(
        this.interactor ? this.interactor.can_comment : this.showComment
      );
    },
  },
};
</script>

<lang-strings>
  {
    "totara_comment": [
      "comments"
    ],
    "totara_engage": [
      "comments"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-sidePanelCommentBox {
  display: flex;
  flex-direction: column;
  height: 100%;

  // Overriding the comment box to make it fit with the sidepanel.
  &__header {
    margin: 0;
    margin-bottom: var(--gap-4);
    padding: 0;
    padding-bottom: var(--gap-2);
    font-size: var(--font-size-14);
    border-bottom: var(--border-width-normal) solid var(--color-neutral-5);
  }

  &__box {
    display: flex;
    flex-direction: column;
    overflow: hidden;

    .tui-commentBox {
      display: flex;
      flex-direction: column;
      flex-grow: 1;

      // Overriding the comment thread to make it fit with the sidepanel
      &__comments {
        position: relative;
        padding-right: var(--gap-2);
        overflow: auto;

        // This is not support by IE or Edge.
        scroll-behavior: smooth;

        .tui-commentThread {
          &__comment {
            &:not(:first-child) {
              padding-top: var(--gap-4);
              border-top: var(--border-width-thin) solid var(--color-neutral-5);
            }
          }
        }
      }

      &__commentForm {
        padding-top: var(--gap-4);
        border-top: var(--border-width-normal) solid var(--color-neutral-5);
      }
    }
  }
}
</style>
