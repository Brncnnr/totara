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

  @author Steve Barnett <steve.barnett@totaralearning.com>
  @module totara_engage
  @deprecated since Totara 16.0
  This component has been deprecated, please use ConfirmationModal.vue instead
-->
<template>
  <Modal
    :dismissable="dismissable"
    :aria-labelledby="$id('title')"
    class="tui-engageWarningModal"
  >
    <ModalContent
      :close-button="closeButton"
      :title-id="$id('title')"
      :title-visible="false"
      @dismiss="$emit('cancel')"
    >
      <template v-slot:title>
        {{ title }}
      </template>
      <div class="tui-engageWarningModal__container">
        <div class="tui-engageWarningModal__icon">
          <warning size="700" :custom-class="'tui-icon--warning'" />
        </div>
        <div class="tui-engageWarningModal__box">
          <p v-show="title" class="tui-engageWarningModal__title">
            {{ title }}
          </p>

          <p>
            {{ messageContent }}
          </p>
        </div>
      </div>
      <template v-slot:buttons>
        <ButtonGroup>
          <Button
            :styleclass="{ primary: true, small: true }"
            :text="$str('continue', 'core')"
            @click="$emit('confirm')"
          />

          <Button
            :styleclass="{ small: true }"
            :text="$str('cancel', 'core')"
            @click="$emit('cancel')"
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

    open: {
      type: Boolean,
    },

    size: {
      type: String,
      default: 'normal',
    },

    title: {
      type: String,
      default: '',
    },

    messageContent: {
      type: String,
      required: true,
    },
  },

  data() {
    return {
      dismissable: {
        overlayClose: false,
        esc: true,
        backdropClick: false,
      },
      id: this.$id(this.title),
    };
  },
};
</script>

<lang-strings>
  {
    "core": [
      "continue",
      "cancel"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageWarningModal {
  &__container {
    display: flex;
  }

  &__title {
    @include tui-font-heading-x-small();
    margin-bottom: var(--gap-2);
  }

  &__box {
    padding-left: var(--gap-4);
  }
}
</style>
