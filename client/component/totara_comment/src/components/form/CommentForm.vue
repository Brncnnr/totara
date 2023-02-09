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
  <ResponseBox class="tui-commentForm">
    <Form class="tui-commentForm__form">
      <UnsavedChangesWarning
        v-if="!content.isEmpty && !submitting"
        :value="content"
      />
      <!--
        The editor has to be constructed once the editor options have been finished fetching.
        So that it can be constructed with the provided editor options.
       -->
      <div class="tui-commentForm__input">
        <Weka
          v-if="draftId"
          :key="editorKey"
          v-model="content"
          :data-key="editorKey"
          :usage-identifier="{
            component: 'totara_comment',
            area: 'comment',
          }"
          :variant="editor.variant"
          :compact="editor.compact"
          :extra-extensions="editor.extraExtensions"
          :file-item-id="draftId"
          :placeholder="$str('entercomment', 'totara_comment')"
          :data-file-item-id="draftId"
          :context-id="editor.contextId"
          class="tui-commentForm__editor"
          @ready="$emit('form-ready')"
        />
      </div>
      <ButtonGroup class="tui-commentForm__buttonGroup">
        <Button
          :text="submitButtonText"
          :aria-label="submitButtonText"
          :disabled="content.isEmpty || submitting"
          :styleclass="{ primary: true, small: isSmall }"
          class="tui-commentForm__button"
          @click="submit"
        />
      </ButtonGroup>
    </Form>
  </ResponseBox>
</template>

<script>
import Weka from 'editor_weka/components/Weka';
import WekaValue from 'editor_weka/WekaValue';
import Form from 'tui/components/form/Form';
import { uniqueId } from 'tui/util';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import Button from 'tui/components/buttons/Button';
import { FORMAT_JSON_EDITOR } from 'tui/format';
import { SIZE_SMALL, isValid } from 'totara_comment/size';
import ResponseBox from 'totara_comment/components/form/box/ResponseBox';

import UnsavedChangesWarning from 'totara_engage/components/form/UnsavedChangesWarning';

// GraphQL queries
import fileDraftId from 'core/graphql/file_unused_draft_item_id';

export default {
  components: {
    Weka,
    Form,
    ButtonGroup,
    Button,
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
     * This prop had been deprecated and no longer used.
     * @deprecated since Totara 13.3
     */
    component: String,
    /**
     * This prop had been deprecated and no longer used.
     * @deprecated since Totara 13.3
     */
    area: String,
    /**
     * This prop had been deprecated and no longer used.
     * @deprecated since Totara 13.3
     */
    instanceId: [String, Number],
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
    submitButtonText: {
      type: String,
      default() {
        return this.$str('post', 'totara_comment');
      },
    },
  },

  data() {
    return {
      editorKey: `totara_comment_editor_weka_${uniqueId()}`,
      /**
       * This data attribute had been deprecated and no longer used.
       * @deprecated since Totara 13.3
       */
      editorOption: null,
      draftId: null,
      content: WekaValue.empty(),
    };
  },

  computed: {
    isSmall() {
      return SIZE_SMALL === this.size;
    },
  },

  watch: {
    editorOption() {
      console.warn(
        "The attribute 'editorOption' had been deprecated, please do not use it"
      );
    },

    async submitting(newVal) {
      if (newVal === false && !this.hasError) {
        this.content = WekaValue.empty();

        // Changing the key so that the editor can be re-constructed
        await this.$_loadDraftId();

        this.editorKey = `totara_comment_editor_weka_${uniqueId()}`;
        this.$forceUpdate();
      }
    },
  },

  async mounted() {
    if (this.component) {
      console.warn(
        "The prop 'component' had been deprecated and no longer used, please update all call"
      );
    }

    if (this.area) {
      console.warn(
        "The prop 'area' had been deprecated and no longer used, please update all call"
      );
    }

    if (this.instanceId) {
      console.warn(
        "The prop 'instanceId' had been deprecated and no longer used, please update all call"
      );
    }

    await this.$_loadDraftId();
  },

  methods: {
    async $_loadDraftId() {
      const {
        data: { item_id },
      } = await this.$apollo.mutate({ mutation: fileDraftId });
      this.draftId = item_id;
    },

    /**
     * Submitting the content to the server.
     */
    submit() {
      if (this.submitting) {
        return;
      }

      this.$emit('submit', {
        content: JSON.stringify(this.content.getDoc()),
        format: FORMAT_JSON_EDITOR,
        itemId: this.draftId,
      });
    },
  },
};
</script>

<lang-strings>
  {
    "totara_comment": [
      "post",
      "entercomment"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-commentForm {
  &__form {
    flex: 1;
    max-width: 100%;
  }

  &__editor {
    flex: 1;
    max-width: 100%;

    .tui-weka__editorHost {
      max-height: 200px;
    }
  }

  &__buttonGroup {
    display: flex;
    justify-content: flex-end;
    margin-top: var(--gap-4);
  }
}
</style>
