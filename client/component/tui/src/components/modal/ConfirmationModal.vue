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

  @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
  @module tui
-->

<template>
  <ModalPresenter
    :open="open"
    @close-complete="$emit('close-complete')"
    @request-close="$emit('cancel')"
  >
    <Modal :size="size" :aria-labelledby="id">
      <ModalContent
        :close-button="closeButton"
        :title="title"
        :title-id="id"
        @dismiss="$emit('cancel')"
      >
        <slot />
        <template v-slot:buttons>
          <ButtonGroup>
            <Button
              :styleclass="{ primary: 'true' }"
              :loading="loading"
              :text="confirmButtonText"
              @click="$emit('confirm')"
            />
            <ButtonCancel :disabled="loading" @click="$emit('cancel')" />
          </ButtonGroup>
        </template>
      </ModalContent>
    </Modal>
  </ModalPresenter>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import ButtonCancel from 'tui/components/buttons/Cancel';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import ModalPresenter from 'tui/components/modal/ModalPresenter';

export default {
  components: {
    Button,
    ButtonCancel,
    ButtonGroup,
    Modal,
    ModalContent,
    ModalPresenter,
  },

  props: {
    loading: {
      type: Boolean,
    },
    confirmButtonText: {
      type: String,
      default() {
        return this.$str('ok', 'core');
      },
    },
    closeButton: {
      type: Boolean,
      default: false,
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
      "ok"
    ]
  }
</lang-strings>
