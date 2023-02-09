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
  @deprecated since Totara 16.0
  This component has been deprecated, please use ConfirmationModal.vue instead  
-->
<template>
  <Modal
    class="tui-confirmDeleteCommentReplyModal"
    size="small"
    :aria-labelledby="id"
  >
    <ModalContent
      class="tui-confirmDeleteCommentReplyModal__content"
      :close-button="true"
      :title="title"
      :title-id="id"
      @dismiss="$emit('request-close')"
    >
      <div class="tui-confirmDeleteCommentReplyModal__textBox">
        <Warning
          :size="700"
          :custom-class="[
            'tui-icon--warning',
            'tui-confirmDeleteCommentReplyModal__icon',
          ]"
        />

        <p class="tui-confirmDeleteCommentReplyModal__textBox-text">
          {{ $str('deletecommentconfirm', 'totara_comment') }}
        </p>
      </div>

      <ButtonGroup
        slot="buttons"
        class="tui-confirmDeleteCommentReplyModal__buttons"
      >
        <Button
          :text="$str('confirm', 'core')"
          :styleclass="{ primary: true, small: true }"
          @click.prevent="$emit('confirm-delete')"
        />
        <Button
          :text="$str('cancel', 'core')"
          :styleclass="{ small: true }"
          @click.prevent="$emit('request-close')"
        />
      </ButtonGroup>
    </ModalContent>
  </Modal>
</template>

<script>
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import Button from 'tui/components/buttons/Button';
import Warning from 'tui/components/icons/Warning';

export default {
  components: {
    Modal,
    ModalContent,
    ButtonGroup,
    Button,
    Warning,
  },

  props: {
    title: {
      type: String,
      default: '',
    },
  },

  computed: {
    id() {
      return this.$id(this.title);
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "cancel",
      "confirm"
    ],

    "totara_comment": [
      "deletecommentconfirm"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-confirmDeleteCommentReplyModal {
  &__buttons {
    display: flex;
    justify-content: flex-end;
  }

  &__textBox {
    display: flex;
    align-items: center;
    justify-content: flex-start;

    &-text {
      @include tui-font-body();
      margin: 0;
      margin-left: var(--gap-4);
    }
  }

  &__content {
    .tui-modalContent {
      &__title {
        padding: 0;
      }
    }
  }
}
</style>
