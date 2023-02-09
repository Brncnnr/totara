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
  <Form class="tui-editCommentReplyForm">
    <template v-if="!$apollo.loading">
      <UnsavedChangesWarning
        v-if="!content.isEmpty && !submitting"
        :value="content"
      />
      <Weka
        v-if="!$apollo.queries.item.loading"
        v-model="content"
        :file-item-id="item.file_draft_id"
        :usage-identifier="{
          component: 'totara_comment',
          area: commentArea,
          instanceId: itemId,
        }"
        :compact="editor.compact"
        :context-id="editor.contextId"
        :variant="editor.variant"
        :extra-extensions="editor.extraExtensions"
        :disabled="submitting"
        class="tui-editCommentReplyForm__editor"
      />

      <SubmitCancelButtonsGroup
        :submit-text="$str('done', 'totara_comment')"
        :disable-submit="content.isEmpty || submitting"
        :size="size"
        @click-submit="submit"
        @click-cancel="$emit('cancel')"
      />
    </template>
  </Form>
</template>

<script>
import Weka from 'editor_weka/components/Weka';
import WekaValue from 'editor_weka/WekaValue';
import Form from 'tui/components/form/Form';
import { SIZE_SMALL, isValid } from 'totara_comment/size';
import SubmitCancelButtonsGroup from 'totara_comment/components/form/group/SubmitCancelButtonsGroup';

import UnsavedChangesWarning from 'totara_engage/components/form/UnsavedChangesWarning';

// GraphQL query
import getDraftItem from 'totara_comment/graphql/get_draft_item';

export default {
  components: {
    Weka,
    Form,
    SubmitCancelButtonsGroup,
    UnsavedChangesWarning,
  },

  apollo: {
    item: {
      query: getDraftItem,
      fetchPolicy: 'network-only',
      variables() {
        return {
          id: this.itemId,
        };
      },

      result({ data: { draftitem } }) {
        this.content = WekaValue.fromDoc(JSON.parse(draftitem.content));
      },

      update({ draftitem }) {
        return draftitem;
      },
    },
  },

  props: {
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
     * Either comment's or reply's id.
     */
    itemId: {
      type: [String, Number],
      required: true,
    },

    editor: {
      type: Object,
      validator: prop => 'compact' in prop && 'variant' in prop,
      default() {
        return {
          contextId: undefined,
          variant: undefined,
          compact: true,
        };
      },
    },

    submitting: Boolean,
    hasError: Boolean,
  },

  data() {
    return {
      /**
       * This data attribute had been deprecated and no longer used.
       * @deprecated since Totara 13.3
       */
      editorOption: null,
      item: {},
      content: WekaValue.empty(),
    };
  },

  computed: {
    /**
     * Returning the comment's area either it is 'comment' or 'reply'
     * @return {String}
     */
    commentArea() {
      if (!this.item.comment_area) {
        // Default to comment
        return 'comment';
      }

      return this.item.comment_area.toLowerCase();
    },
  },

  watch: {
    editorOption() {
      console.warn(
        "The data attribute 'editorOption' had been deprecated and no longer used"
      );
    },
  },

  methods: {
    submit() {
      const params = {
        id: this.itemId,
        content: JSON.stringify(this.content.getDoc()),
        format: this.item.format,
        itemId: this.content.fileStorageItemId,
      };

      this.$emit('update-item', params);
    },
  },
};
</script>

<lang-strings>
  {
    "totara_comment": [
      "done"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-editCommentReplyForm {
  display: flex;
  flex-direction: column;
  max-width: 100%;

  &__editor {
    max-width: 100%;
  }
}
</style>
