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
  <div class="tui-commentReplyContent">
    <div
      v-if="!onEdit"
      ref="content"
      class="tui-commentReplyContent__content"
      :class="{
        'tui-commentReplyContent__content--reply': isReply,
        'tui-commentReplyContent__content--deleted': deleted,
      }"
      v-html="content"
    />

    <EditForm
      v-else
      :item-id="itemId"
      :size="size"
      :editor="editor"
      :has-error="hasError"
      class="tui-commentReplyContent__editForm"
      @cancel="$emit('cancel-editing')"
      @update-item="$emit('update-item', $event)"
    />
  </div>
</template>

<script>
import tui from 'tui/tui';
import EditForm from 'totara_comment/components/form/EditCommentReplyForm';
import { SIZE_SMALL, isValid } from 'totara_comment/size';

export default {
  components: {
    EditForm,
  },

  props: {
    content: {
      type: String,
      required: true,
    },

    onEdit: {
      type: Boolean,
      default: false,
    },

    itemId: {
      type: [Number, String],
      required: true,
      validator(prop) {
        return !isNaN(prop);
      },
    },

    isReply: {
      type: Boolean,
      default: false,
    },

    deleted: {
      type: Boolean,
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

    hasError: Boolean,
  },

  mounted() {
    this.$_scan();
  },

  updated() {
    this.$_scan();
  },

  methods: {
    $_scan() {
      let content = this.$refs.content;
      if (!content) {
        return;
      }

      tui.scan(content);
    },
  },
};
</script>

<style lang="scss">
.tui-commentReplyContent {
  margin: 0;

  &__content {
    max-width: 100%;

    .tui-rendered {
      p {
        @include tui-font-body();
        // Reset margin of paragraph in tui-rendered content.
        margin: 0;
      }
    }

    &--deleted {
      // Deleted comment should not have any .tui-rendered element
      @include tui-font-body-small();
      font-style: italic;
    }

    &--reply {
      @include tui-font-body-small();

      .tui-rendered {
        p {
          @include tui-font-body-small();
        }
      }
    }
  }
}
</style>
