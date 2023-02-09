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
  <ResponseBox class="tui-commentReplyForm">
    <Form class="tui-commentReplyForm__form">
      <UnsavedChangesWarning
        v-if="!content.isEmpty && !submitting"
        :value="content"
      />
      <Weka
        v-if="draftId"
        v-model="content"
        :compact="editor.compact"
        :variant="editor.variant"
        :extra-extensions="editor.extraExtensions"
        :usage-identifier="{
          component: 'totara_comment',
          area: 'reply',
        }"
        :placeholder="$str('enterreply', 'totara_comment')"
        :file-item-id="draftId"
        :data-file-item-id="draftId"
        :context-id="editor.contextId"
        :disabled="submitting"
        class="tui-commentReplyForm__editor"
        @ready="$emit('form-ready')"
      />

      <SubmitCancelButtonsGroup
        :submit-text="$str('reply', 'totara_comment')"
        :size="size"
        :disable-submit="content.isEmpty || submitting || !isMounted"
        :disable-cancel="submitting"
        @click-submit="submit"
        @click-cancel="$emit('cancel')"
      />
    </Form>
  </ResponseBox>
</template>

<script>
import Weka from 'editor_weka/components/Weka';
import WekaValue from 'editor_weka/WekaValue';
import Form from 'tui/components/form/Form';
import SubmitCancelButtonsGroup from 'totara_comment/components/form/group/SubmitCancelButtonsGroup';
import { FORMAT_JSON_EDITOR } from 'tui/format';
import { createMentionContent } from 'editor_weka/helpers/mention';
import { isValid, SIZE_SMALL } from 'totara_comment/size';
import ResponseBox from 'totara_comment/components/form/box/ResponseBox';

import UnsavedChangesWarning from 'totara_engage/components/form/UnsavedChangesWarning';

// GraphQL queries
import fileDraftId from 'core/graphql/file_unused_draft_item_id';

export default {
  components: {
    Weka,
    Form,
    SubmitCancelButtonsGroup,
    ResponseBox,
    UnsavedChangesWarning,
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
     * This prop has been deprecated, please do not use.
     * @deprecated
     */
    component: String,

    /**
     * This prop has been deprecated, please do not use.
     * @deprecated
     */
    area: String,

    commentId: {
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
    hasError: Boolean,

    replyTo: {
      type: Object,
      default() {
        return null;
      },
    },
  },

  data() {
    return {
      draftId: null,
      /**
       * This data attribute had been deprecated and no longer used.
       * @deprecated since Totara 13.3
       */
      editorOption: null,
      content: WekaValue.empty(),
      isMounted: false,
    };
  },

  computed: {
    isSmall() {
      return SIZE_SMALL === this.size;
    },
  },

  watch: {
    /**
     * @param {Object} value
     */
    replyTo: {
      deep: true,
      immediate: true,
      handler(value) {
        if (!value) {
          this.content = WekaValue.empty();
          return;
        }

        this.content = WekaValue.fromDoc(createMentionContent(value));
      },
    },

    editorOption() {
      console.warn(
        "The data attribute 'editorOption' had been deprecated and no longer used, please update all calls"
      );
    },

    submitting(newVal) {
      if (newVal === false && !this.hasError) {
        this.content = WekaValue.empty();

        this.$_loadDraftId();
      }
    },
  },

  async mounted() {
    await this.$_loadDraftId();

    if (this.component) {
      console.warn(
        'The prop "component" has been deprecated, please do not use.'
      );
    }

    if (this.area) {
      console.warn('The prop "area" has been deprecated, please do not use.');
    }

    this.isMounted = true;
  },

  methods: {
    async $_loadDraftId() {
      const {
        data: { item_id },
      } = await this.$apollo.mutate({ mutation: fileDraftId });
      this.draftId = item_id;
    },

    submit() {
      if (this.submitting) {
        return;
      }

      this.$emit('submit', {
        content: JSON.stringify(this.content.getDoc()),
        format: FORMAT_JSON_EDITOR,
        commentId: this.commentId,
        itemId: this.draftId,
      });
    },
  },
};
</script>

<lang-strings>
  {
    "totara_comment": [
      "enterreply",
      "reply"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-commentReplyForm {
  margin-top: var(--gap-4);
  padding-right: var(--gap-2);

  &__form {
    flex: 1;
    max-width: 100%;
  }

  &__editor {
    flex: 1;
    max-width: 100%;
  }
}
</style>
