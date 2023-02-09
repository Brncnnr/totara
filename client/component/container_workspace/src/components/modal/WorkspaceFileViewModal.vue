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

  @author Qingyang Liu <qingyang liu@totaralearning.com>
  @module container_workspace
-->
<template>
  <Modal
    :dismissable="{
      overlayClose: false,
      esc: true,
      backdropClick: true,
    }"
    class="tui-workspaceFileViewModal"
  >
    <ModalContent
      class="tui-workspaceFileViewModal__content"
      :close-button="true"
    >
      <div class="tui-workspaceFileViewModal__header">
        <p class="tui-workspaceFileViewModal__title">{{ fileName }}</p>
        <div class="tui-workspaceFileViewModal__links">
          <a :href="contextUrl" class="tui-workspaceFileViewModal__discussion">
            {{ $str('view_discussion', 'container_workspace') }}
          </a>
          <a :href="downloadUrl" class="tui-workspaceFileViewModal__link">
            {{ $str('download', 'core') }}
          </a>
        </div>
      </div>

      <template v-if="fileType === 'IMAGE'">
        <img
          :src="fileUrl"
          :alt="altText"
          class="tui-workspaceFileViewModal__media"
        />
      </template>
      <template v-else-if="fileType === 'VIDEO'">
        <CoreVideoBlock
          :url="fileUrl"
          :filename="fileName"
          :mimetype="mimeType"
          class="tui-workspaceFileViewModal__media"
        />
      </template>
      <template v-else-if="fileType === 'AUDIO'">
        <audio controls class="tui-workspaceFileViewModal__audio">
          <source :src="fileUrl" :type="mimeType" />
        </audio>
      </template>
      <div v-else class="tui-workspaceFileViewModal__area">
        <div class="tui-workspaceFileViewModal__area-icon">
          <FileIcon :filename="fileName" />
        </div>
        <p>
          {{ $str('file_preview_empty', 'container_workspace') }}
        </p>
      </div>
    </ModalContent>
  </Modal>
</template>

<script>
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import FileIcon from 'tui/components/icons/files/compute/FileIcon';
import CoreVideoBlock from 'tui/components/json_editor/nodes/VideoBlock';

export default {
  components: {
    Modal,
    ModalContent,
    FileIcon,
    CoreVideoBlock,
  },

  props: {
    fileName: {
      type: String,
      required: true,
    },

    downloadUrl: {
      type: String,
      required: true,
    },

    contextUrl: {
      type: String,
      required: true,
    },

    fileUrl: {
      type: String,
      required: true,
    },

    extension: {
      type: String,
      required: true,
    },

    mimeType: {
      type: String,
      required: true,
    },

    fileType: {
      type: String,
      required: true,
    },

    altText: {
      type: String,
      required: true,
    },
  },
};
</script>
<lang-strings>
  {
    "container_workspace": [
      "view_discussion",
      "file_preview_empty",
      "file_image",
      "file_video",
      "file_audio"
    ],

    "core": [
      "download"
    ]
  }
</lang-strings>

<style lang="scss">
:root {
  --workspaceFileViewModal-minHeight: 240px;
  --workspaceFileViewModal-maxHeight: 500px;
}
.tui-workspaceFileViewModal {
  display: flex;
  flex-direction: column;

  &__header {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    width: 100%;
    margin-bottom: var(--gap-4);
  }

  &__title {
    @include tui-font-heading-label();
    width: 60%;
    padding-right: var(--gap-4);
  }

  &__links {
    @include tui-font-body();
    display: flex;
    justify-content: space-between;
    width: 40%;
  }

  &__link {
    padding-right: var(--gap-2);
  }

  &__area {
    @include tui-font-body();
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: var(--workspaceFileViewModal-minHeight);
    border: 1px solid var(--filter-bar-border-color);

    &-icon {
      margin-bottom: var(--gap-8);
      color: var(--color-neutral-6);
      font-size: 60px;
    }
  }

  &__media {
    display: inline-block;
    width: 100%;
    height: auto;
    min-height: var(--workspaceFileViewModal-minHeight);
    max-height: var(--workspaceFileViewModal-maxHeight);
  }

  &__media.tui-videoBlock {
    margin: 0;
  }

  &__audio {
    display: inline-block;
    width: 100%;

    &:focus {
      outline: none;
    }
  }
}
</style>
