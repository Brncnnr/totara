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

  @author Arshad Anwer <arshad.anwer@totaralearning.com>
  @module editor_weka
-->

<template>
  <!-- A modal to upload any extra files related -->
  <Modal v-if="repositoryData" class="tui-captionUploadModal">
    <ModalContent class="tui-captionUploadModal__content">
      <template slot="title">
        <div class="tui-captionUploadModal__title">
          {{ modalTitle }}
          <InfoIconButton
            v-if="modalTitleHelpText"
            :is-help-for="$str('transcript', 'editor')"
          >
            {{ modalTitleHelpText }}
          </InfoIconButton>
        </div>
      </template>
      <Form @submit.prevent="confirm">
        <template v-if="!uploadedFile">
          <Upload
            class="tui-captionUploadModal__upload"
            :repository-id="repositoryData.repositoryId"
            :item-id="itemId"
            :href="repositoryData.url"
            :accepted-types="acceptedFileTypes"
            :overwrite="true"
            :one-file="true"
            :context-id="contextId"
            @load="handleFileLoaded"
            @upload-finished="uploading = false"
            @progress="uploading = true"
            @error="handleError"
          >
            <div slot-scope="{ selectEvents, inputEvents }">
              <input
                v-show="false"
                ref="inputFile"
                :accept="acceptedFileTypes"
                type="file"
                v-on="inputEvents"
              />

              <Button
                v-if="!uploading"
                :aria-label="submitButtonText"
                :text="submitButtonText"
                v-on="selectEvents"
              />

              <ButtonIcon
                v-else
                :aria-label="submitButtonText"
                :text="submitButtonText"
                :disabled="true"
              >
                <Loading />
              </ButtonIcon>
            </div>
          </Upload>
          <FieldError
            v-if="uploadError"
            class="tui-captionUploadModal__errorHandler"
            :error="uploadError"
          />
        </template>

        <div v-else class="tui-captionUploadModal__file">
          <div class="tui-captionUploadModal__fileContent">
            <FileIcon
              :filename="uploadedFile.filename"
              :title="
                $str('filewithname', 'totara_core', uploadedFile.filename)
              "
            />
            {{ uploadedFile.filename }}
          </div>
          <ButtonIcon
            :aria-label="$str('delete', 'totara_core')"
            :styleclass="{
              transparentNoPadding: true,
            }"
            @click="deleteFile"
          >
            <DeleteIcon />
          </ButtonIcon>
        </div>

        <p class="tui-captionUploadModal__helpText">
          {{ modalHelpText }}
        </p>

        <ButtonGroup class="tui-captionUploadModal__buttonGroup">
          <Button
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
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import Button from 'tui/components/buttons/Button';
import ButtonCancel from 'tui/components/buttons/Cancel';
import FieldError from 'tui/components/form/FieldError';
import Upload from 'tui/components/form/Upload';
import FileIcon from 'tui/components/icons/files/compute/FileIcon';
import InfoIconButton from 'tui/components/buttons/InfoIconButton';
import DeleteIcon from 'tui/components/icons/Clear';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Loading from 'tui/components/icons/Loading';
import { getDraftFile } from 'editor_weka/api';

// GraphQL queries
import getRepositoryData from 'editor_weka/graphql/get_repository_data';
import getDraftFileQuery from 'editor_weka/graphql/get_draft_file';
import deleteDraftFile from 'totara_core/graphql/delete_draft_file';

export default {
  components: {
    Modal,
    ModalContent,
    Form,
    ButtonGroup,
    Button,
    ButtonCancel,
    FieldError,
    Upload,
    FileIcon,
    InfoIconButton,
    DeleteIcon,
    ButtonIcon,
    Loading,
  },

  props: {
    itemId: {
      type: Number,
      required: true,
    },

    contextId: {
      type: Number,
      required: true,
    },

    acceptedFileTypes: {
      type: Array,
      required: true,
    },

    /**
     * Given the file name will indicate that this modal had reserved for a file.
     */
    filename: String,

    /**
     * Upload help text string.
     */
    modalHelpText: {
      type: String,
      required: true,
    },

    /**
     * Upload help text string
     */
    modalTitle: {
      type: String,
      required: true,
    },

    modalTitleHelpText: String,

    submitButtonText: {
      type: String,
      required: true,
    },
  },

  data() {
    return {
      repositoryData: null,
      uploadError: '',
      uploadedFile: null,
      uploading: false,
    };
  },

  apollo: {
    uploadedFile: {
      query: getDraftFileQuery,
      variables() {
        return {
          item_id: this.itemId,
          filename: this.filename,
        };
      },
      skip() {
        // We are skipping this query if there is no filename provided.
        return !this.filename;
      },

      update({ file }) {
        return file;
      },
    },

    repositoryData: {
      query: getRepositoryData,
      variables() {
        return {
          context_id: this.contextId,
        };
      },
      update({ repository_data }) {
        return {
          repositoryId: repository_data.repository_id,
          url: repository_data.url,
        };
      },
    },
  },

  methods: {
    confirm() {
      this.$emit('change', this.uploadedFile);
    },

    async deleteFile() {
      if (!this.uploadedFile) {
        return;
      }

      const { filename } = this.uploadedFile;
      let {
        data: {
          result: { success },
        },
      } = await this.$apollo.mutate({
        mutation: deleteDraftFile,
        variables: {
          draftid: this.itemId,
          filename: filename,
        },
      });

      if (success) {
        this.uploadedFile = null;
      }
    },

    async handleFileLoaded({ file: { name } }) {
      this.uploadError = '';

      // Refetch metadata of the file after uploaded. This is happening because the metadata responsed from
      // uploading a file is different from what we want. Furthermore, we would want to have our internal uploaded
      // file sync up with the one provided from the external.
      this.uploadedFile = await getDraftFile({
        itemId: this.itemId,
        filename: name,
      });
    },

    handleError() {
      this.uploadError = this.$str('meta_upload_generic_error', 'editor_weka');
      this.uploading = false;
    },
  },
};
</script>

<lang-strings>
  {
    "totara_core": [
      "filewithname",
      "delete"
    ],
    "editor": [
      "transcript"
    ],
    "editor_weka": [
      "done",
      "meta_upload_generic_error"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-captionUploadModal {
  &__title {
    display: flex;
  }

  &__upload {
    margin-top: var(--gap-2);
  }

  &__errorHandler {
    margin-top: 0;
  }

  &__file {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--gap-2);
    background: var(--color-neutral-3);
  }

  &__helpText {
    margin: 0;
    margin-top: var(--gap-2);
    color: var(--color-neutral-6);
    font-size: var(--font-size-13);
  }

  &__buttonGroup {
    display: flex;
    justify-content: flex-end;
    margin-top: var(--gap-8);
  }
}
</style>
