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
  @module editor_weka
-->

<template>
  <Modal
    class="tui-wekaEditImageAltTextModal"
    :aria-labelledby="$id('title')"
    :dismissable="{ backdropClick: false }"
  >
    <ModalContent
      :title="modalTitle"
      class="tui-wekaEditImageAltTextModal__content"
      :title-id="$id('title')"
    >
      <Form input-width="full" @submit.prevent="confirm">
        <div class="tui-wekaEditImageAltTextModal__input">
          <InputText
            ref="altTextRef"
            v-model="innerValue"
            :maxlength="120"
            char-length="full"
            :autofocus="true"
          />

          <p class="tui-wekaEditImageAltTextModal__input-helpText">
            {{ $str('image_alt_help', 'editor_weka') }}
          </p>
        </div>

        <ButtonGroup class="tui-wekaEditImageAltTextModal__buttonGroup">
          <Button
            :disabled="!innerValue"
            :styleclass="{ primary: 'true' }"
            :text="$str('done', 'editor_weka')"
            @click="confirm"
          />

          <ButtonCancel @click.prevent="$emit('request-close')" />
        </ButtonGroup>
      </Form>
    </ModalContent>
  </Modal>
</template>

<script>
import Form from 'tui/components/form/Form';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import InputText from 'tui/components/form/InputText';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import Button from 'tui/components/buttons/Button';
import ButtonCancel from 'tui/components/buttons/Cancel';

export default {
  components: {
    Modal,
    ModalContent,
    Form,
    InputText,
    ButtonGroup,
    Button,
    ButtonCancel,
  },

  props: {
    value: String,
  },

  data() {
    return {
      innerValue: this.value,
    };
  },

  computed: {
    modalTitle() {
      if (this.value) {
        return this.$str('edit_image_alt_text', 'editor_weka');
      }

      return this.$str('add_image_alt_text', 'editor_weka');
    },
  },

  mounted() {
    this.$nextTick(() => {
      if (this.$refs.altTextRef && this.$refs.altTextRef.$el) {
        this.$refs.altTextRef.$el.focus();
      }
    });
  },

  methods: {
    confirm() {
      this.$emit('change', this.innerValue);
    },
  },
};
</script>

<lang-strings>
  {
    "editor_weka": [
      "add_image_alt_text",
      "edit_image_alt_text",
      "done",
      "image_alt_help"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-wekaEditImageAltTextModal {
  &__content {
    .tui-modalContent__title {
      padding-bottom: var(--gap-2);
    }
  }

  &__input {
    display: flex;
    flex-direction: column;
    margin: 0;

    &-helpText {
      margin: 0;
      margin-top: var(--gap-1);
      color: var(--color-neutral-6);
      font-size: var(--font-size-13);
    }
  }

  &__buttonGroup {
    display: flex;
    justify-content: flex-end;
    margin-top: var(--gap-8);
  }
}
</style>
