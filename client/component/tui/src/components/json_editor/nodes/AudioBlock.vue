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
  @module totara_core
-->

<template>
  <div class="tui-audioBlock">
    <audio controls>
      <source :src="url" :type="mimeType" />
      {{ $str('audionotsupported', 'totara_core') }}
    </audio>

    <div class="tui-audioBlock__actionsWrapper">
      <Button
        v-if="transcriptUrl"
        class="tui-audioBlock__viewTranscript"
        :styleclass="{
          transparent: true,
        }"
        :aria-haspopup="true"
        :aria-label="
          $str('view_transcript_file', 'editor', filenameNoExtension)
        "
        :text="$str('view_transcript', 'editor')"
        @click="showModal"
      />

      <ModalPresenter :open="modal" @request-close="hideModal">
        <Modal :size="size">
          <ModalContent
            :title="$str('transcript', 'editor')"
            :title-visible="false"
            class="tui-audioBlock__transcriptModal"
            :close-button="true"
          >
            <!-- prettier-ignore -->
            <div class="tui-audioBlock__transcriptContent"> {{ transcriptContent }} </div>
          </ModalContent>
        </Modal>
      </ModalPresenter>

      <slot name="actions" />
    </div>
  </div>
</template>

<script>
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import Button from 'tui/components/buttons/Button';

export default {
  components: {
    ModalPresenter,
    Modal,
    ModalContent,
    Button,
  },
  inheritAttrs: false,

  props: {
    mimeType: {
      type: String,
      required: true,
    },

    filename: {
      type: String,
      required: true,
    },

    url: {
      type: String,
      required: true,
    },

    /**
     * For transcript url
     */
    transcriptUrl: String,
  },

  data() {
    return {
      transcriptContent: null,
      modal: false,
      size: 'large',
    };
  },

  computed: {
    filenameNoExtension() {
      if (!this.filename) {
        return '';
      }

      let parts = this.filename.split('.');
      parts.pop();

      return parts.join('.');
    },

    /** @deprecated since 13.3 */
    attributes: () => null,
  },

  methods: {
    async $_getDetails() {
      this.transcriptContent = await fetch(this.transcriptUrl).then(x =>
        x.text()
      );
    },

    async showModal() {
      this.modal = true;
      await this.$_getDetails();
    },

    hideModal() {
      this.modal = false;
    },
  },
};
</script>

<lang-strings>
  {
    "totara_core": [
      "audionotsupported"
    ],
    "editor": [
      "transcript",
      "view_transcript",
      "view_transcript_file"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-audioBlock {
  margin: var(--gap-8) 0;

  &__actionsWrapper {
    display: flex;
    justify-content: flex-end;
    margin-top: var(--gap-2);
  }

  &__viewTranscript {
    margin-right: auto;
  }

  &__transcriptContent {
    height: 60rem;
    white-space: pre-line;
  }
}
</style>
