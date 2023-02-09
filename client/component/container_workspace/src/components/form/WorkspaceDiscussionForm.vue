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
  @module container_workspace
-->
<template>
  <Form :vertical="true" input-width="full" class="tui-workspaceDiscussionForm">
    <FormRow
      v-slot="{ id }"
      :hidden="true"
      :label="$str('discussion', 'container_workspace')"
      :full-width="true"
      :required="true"
    >
      <UnsavedChangesWarning
        v-if="!formContent.isEmpty && !submitting"
        :value="formContent"
      />
      <WekaEditor
        :id="id"
        :key="editorKey"
        v-model="formContent"
        variant="standard"
        :extra-extensions="['hashtag', 'mention']"
        :usage-identifier="{
          component: 'container_workspace',
          area: 'discussion',
          instanceId: discussionId,
        }"
        :file-item-id="draftId"
        :context-id="workspaceContextId"
        :placeholder="$str('start_discussion', 'container_workspace')"
        class="tui-workspaceDiscussionForm__editor"
      />
    </FormRow>
    <ButtonGroup class="tui-workspaceDiscussionForm__buttonGroup">
      <LoadingButton
        :loading="submitting"
        :disabled="disableSubmit"
        :aria-disabled="disableSubmit"
        :aria-label="submitButtonText"
        :text="submitButtonText"
        :primary="true"
        @click.prevent="submit"
      />
      <CancelButton
        v-if="showCancelButton"
        :disabled="submitting"
        @click.prevent="$emit('cancel')"
      />
    </ButtonGroup>
  </Form>
</template>

<script>
import FormRow from 'tui/components/form/FormRow';
import Form from 'tui/components/form/Form';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import CancelButton from 'tui/components/buttons/Cancel';

import LoadingButton from 'totara_engage/components/buttons/LoadingButton';
import UnsavedChangesWarning from 'totara_engage/components/form/UnsavedChangesWarning';

import WekaEditor from 'editor_weka/components/Weka';
import WekaValue from 'editor_weka/WekaValue';

import { FORMAT_JSON_EDITOR } from 'tui/format';
import { uniqueId } from 'tui/util';

export default {
  components: {
    FormRow,
    Form,
    ButtonGroup,
    CancelButton,
    LoadingButton,
    UnsavedChangesWarning,
    WekaEditor,
  },

  props: {
    submitting: Boolean,
    discussionId: {
      type: [String, Number],
      default: null,
    },
    /**
     * A fallback option when we are creating a new discussion.
     */
    workspaceContextId: [Number, String],
    content: {
      type: String,
      default: null,
    },
    contentFormat: {
      type: [String, Number],
      default() {
        return FORMAT_JSON_EDITOR;
      },
    },
    draftId: {
      type: [String, Number],
      default: null,
    },
    showCancelButton: {
      type: Boolean,
      default: true,
    },
    submitButtonText: {
      type: String,
      default() {
        return this.$str('post', 'core');
      },
    },
    hasError: Boolean,
  },

  data() {
    return {
      editorKey: `editor-weka-${uniqueId()}`,
      formContent: WekaValue.empty(),
    };
  },

  computed: {
    /**
     * @return {Boolean}
     */
    disableSubmit() {
      return this.formContent.isEmpty || this.submitting;
    },
  },

  watch: {
    content: {
      immediate: true,
      /**
       * @param {String} value
       */
      handler(value) {
        if (FORMAT_JSON_EDITOR == this.contentFormat) {
          if (!value) {
            this.formContent = WekaValue.empty();
            return;
          }

          this.formContent = WekaValue.fromDoc(JSON.parse(this.content));
        }
      },
    },

    submitting(newValue) {
      if (newValue === false && !this.hasError) {
        // Reset back
        this.formContent = WekaValue.empty();
      }
    },
  },

  methods: {
    submit() {
      this.$emit('submit', {
        content: JSON.stringify(this.formContent.getDoc()),
        itemId: this.draftId,
      });
    },
  },
};
</script>
<lang-strings>
  {
    "container_workspace": [
      "discussion",
      "start_discussion"
    ],
    "core": [
      "post"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-workspaceDiscussionForm {
  display: flex;
  flex-direction: column;

  &__buttonGroup {
    display: flex;
    justify-content: flex-end;
  }
}
</style>
