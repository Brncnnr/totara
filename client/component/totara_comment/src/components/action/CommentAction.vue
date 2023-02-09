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
  <div class="tui-commentAction">
    <SimpleLike
      v-if="showLikeButton"
      class="tui-commentAction__likeButton"
      :instance-id="commentId"
      component="totara_comment"
      :area="area"
      :total-likes="totalReactions"
      :liked="reacted"
      :button-aria-label="likeButtonAriaLabel"
      :disabled="!reactAble"
      :show-text="showLikeButtonText"
      @update-like-status="$emit('update-react-status', $event)"
      @created-like="$emit('update-react-status', true)"
      @removed-like="$emit('update-react-status', false)"
    />

    <div class="tui-commentAction__replyBox">
      <ReplyButtonIcon
        :show-text="showReplyButtonText"
        :disabled="!replyAble"
        @click="$emit('click-reply')"
      />
      <span
        v-if="totalReplies"
        class="tui-commentAction__replyBox-text"
        :class="{
          'tui-commentAction__replyBox-text--small': isSmall,
        }"
      >
        {{ $str('bracketnumber', 'totara_comment', totalReplies) }}
      </span>
    </div>
  </div>
</template>

<script>
import ReplyButtonIcon from 'totara_comment/components/buttons/ReplyButtonIcon';
import { SIZE_SMALL, isValid } from 'totara_comment/size';
import SimpleLike from 'totara_reaction/components/SimpleLike';

export default {
  components: {
    ReplyButtonIcon,
    SimpleLike,
  },

  props: {
    commentId: {
      type: [Number, String],
      required: true,
    },

    area: {
      type: String,
      required: true,
      validator(prop) {
        return ['comment', 'reply'].includes(prop);
      },
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

    totalReplies: {
      type: [Number, String],
      default: 0,
    },

    totalReactions: [Number, String],
    reacted: Boolean,
    reactAble: {
      type: Boolean,
      default: true,
    },

    replyAble: {
      type: Boolean,
      default: true,
    },

    showLikeButton: {
      type: Boolean,
      default: true,
    },
    showLikeButtonText: Boolean,
    showReplyButtonText: Boolean,
  },

  computed: {
    isSmall() {
      return SIZE_SMALL === this.size;
    },

    likeButtonAriaLabel() {
      if (this.reacted) {
        return this.$str('removelikeforcomment', 'totara_comment');
      }

      return this.$str('likecomment', 'totara_comment');
    },
  },
};
</script>

<lang-strings>
  {
    "totara_comment": [
      "bracketnumber",
      "likecomment",
      "removelikeforcomment"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-commentAction {
  display: flex;
  justify-content: flex-start;

  &__replyBox {
    display: flex;

    &-text {
      &--small {
        @include tui-font-body-small();
      }
    }
  }

  &__likeButton + &__replyBox {
    margin-left: var(--gap-4);
  }
}
</style>
