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

  @author Simon Tegg <simon.tegg@totaralearning.com>
  @module mod_approval
-->
<template>
  <Modal>
    <ModalContent :title="$str('reject_application', 'mod_approval')">
      <Form
        class="tui-mod_approval-rejectApplicationModal__form"
        :vertical="true"
      >
        <FormRow
          class="tui-mod_approval-rejectApplicationModal__formRow"
          :required="true"
          :label="$str('rejection_reason', 'mod_approval')"
          :is-stacked="false"
        >
          <Editor
            ref="editor"
            v-model="rejectionComment"
            class="tui-mod_approval-rejectApplicationModal__editor"
            variant="simple"
            :extra-extensions="['mention']"
            :compact="true"
            :context-id="contextId"
            @ready="handleReady"
          />
        </FormRow>
      </Form>
      <template v-slot:buttons>
        <ButtonGroup>
          <Button
            :styleclass="{ primary: true }"
            :loading="$matches('ready.actionsTab.confirmReject.submitting')"
            :disabled="!$context.hasRejectionComment"
            :text="$str('confirm', 'core')"
            @click="submit"
          />
          <CancelButton
            :disabled="$matches('ready.actionsTab.confirmReject.submitting')"
            @click="$send($e.CANCEL)"
          />
        </ButtonGroup>
      </template>
    </ModalContent>
  </Modal>
</template>

<script>
import { MOD_APPROVAL__APPLICATION_VIEW } from 'mod_approval/constants';
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import CancelButton from 'tui/components/buttons/Cancel';
import Editor from 'tui/components/editor/Editor';
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';

export default {
  name: 'RejectApplicationModal',

  components: {
    ButtonGroup,
    Button,
    CancelButton,
    Modal,
    ModalContent,
    Editor,
    Form,
    FormRow,
  },

  props: {
    contextId: {
      type: Number,
      required: true,
    },
  },

  /*
    XState cannot handle Weka/Editor values.
    RejectApplicationModal has to maintain the value outside of the machine,
    and update on watched changes
  */
  data() {
    return { rejectionComment: null };
  },

  watch: {
    rejectionComment(editorValue) {
      if (!editorValue) {
        this.$send({
          type: this.$e.SET_HAS_REJECTION_COMMENT,
          hasRejectionComment: false,
        });
      } else {
        this.$send({
          type: this.$e.SET_HAS_REJECTION_COMMENT,
          hasRejectionComment: !editorValue.isEmpty,
        });
      }
    },
  },

  created() {
    this.$send({
      type: this.$e.SET_HAS_REJECTION_COMMENT,
      hasRejectionComment: false,
    });
  },

  xState: {
    machineId: MOD_APPROVAL__APPLICATION_VIEW,
  },

  methods: {
    async handleReady() {
      await this.$nextTick();
      // Eww hack, find the first editable element (presumably in weka) and manually give it focus.
      const parent = this.$refs.editor.$el;
      if (parent) {
        const kid = parent.querySelector('[contenteditable="true"]');
        if (kid) {
          kid.focus();
        }
      }
    },

    submit() {
      const createCommentVariables = {
        component: 'mod_approval',
        area: 'comment',
        content: this.rejectionComment.getContent(),
        format: this.rejectionComment.format,
        instanceid: this.$selectors.getApplicationId(this.$context),
      };

      this.$send({ type: this.$e.CONFIRM, createCommentVariables });
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "confirm"
  ],
  "mod_approval": [
    "reject_application",
    "rejection_reason"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-rejectApplicationModal {
  &__form,
  &__formRow,
  &__editor {
    height: 100%;
  }
  // Eww, magic numbers taken from Modal.vue
  $tui-modal-normalSize: 560px;
  @media (min-width: ($tui-modal-normalSize + 75px)) {
    &__editor {
      height: 30vh;
    }
  }
  &__formRow {
    // Eww, taken from ArticleForm.vue to override tui-formRow styles
    .tui-formRow__action {
      // Expand the box.
      flex-grow: 1;
      max-width: none;
      min-height: 0;
    }
  }
}
</style>
