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

  @author Qingyang liu <qingyang.liu@totaralearning.com>
  @module container_workspace
  @deprecated since Totara 16.0
  This component has been deprecated, please use ConfirmationModal.vue instead
-->
<template>
  <Modal :dismissable="dismissable" class="tui-workspaceWarningModal">
    <ModalContent
      :close-button="closeButton"
      :title="title"
      :title-visible="false"
      @dismiss="$emit('request-close')"
    >
      <div class="tui-workspaceWarningModal__container">
        <Warning size="700" custom-class="tui-icon--warning" />

        <div class="tui-workspaceWarningModal__box">
          <h4 class="tui-workspaceWarningModal__title">
            {{ title }}
          </h4>

          <p
            class="tui-workspaceWarningModal__content"
            v-html="messageContent"
          />
        </div>
      </div>
      <template v-slot:buttons>
        <ButtonGroup>
          <Button
            :styleclass="{ primary: true, small: true }"
            :text="confirmButtonText"
            @click="$emit('confirm')"
          />

          <Button
            :styleclass="{ small: true }"
            :text="cancelButtonText"
            @click.prevent="$emit('request-close')"
          />
        </ButtonGroup>
      </template>
    </ModalContent>
  </Modal>
</template>
<script>
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import Warning from 'tui/components/icons/Warning';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import Button from 'tui/components/buttons/Button';

export default {
  components: {
    Modal,
    ModalContent,
    Warning,
    ButtonGroup,
    Button,
  },

  props: {
    closeButton: {
      type: Boolean,
      default: true,
    },

    size: {
      type: String,
      default: 'normal',
    },

    title: {
      type: String,
      required: true,
    },

    messageContent: {
      type: String,
      required: true,
    },

    confirmButtonText: {
      type: String,
      default() {
        return this.$str('remove', 'core');
      },
    },

    cancelButtonText: {
      type: String,
      default() {
        return this.$str('cancel', 'core');
      },
    },
  },

  data() {
    return {
      dismissable: {
        overlayClose: false,
        esc: true,
        backdropClick: false,
      },
    };
  },
};
</script>
<lang-strings>
  {
    "core": [
      "cancel",
      "remove"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-workspaceWarningModal {
  .tui-modalContent {
    // Overriding the spacing of modal content.
    margin-top: 0;
  }

  &__container {
    display: flex;
  }

  &__box {
    margin-left: var(--gap-4);
  }

  &__title {
    @include tui-font-heading-x-small();
    margin-top: 0;
    margin-bottom: var(--gap-2);
  }

  &__content {
    @include tui-font-body();
  }
}
</style>
