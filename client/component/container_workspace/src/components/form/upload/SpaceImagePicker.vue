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
  <div
    class="tui-spaceImagePicker"
    :class="{
      'tui-spaceImagePicker--loading': $apollo.loading,
    }"
  >
    <Loading v-if="$apollo.loading" />

    <Upload
      v-else
      :repository-id="metadata.repositoryId"
      :item-id="metadata.itemId"
      :href="metadata.url"
      :accepted-types="metadata.acceptTypes"
      :overwrite="true"
      :one-file="true"
      :context-id="metadata.contextId"
      @load="handleFileLoaded"
      @error="handleError"
      @progress="progressing"
    >
      <div
        slot-scope="{ selectEvents, inputEvents }"
        class="tui-spaceImagePicker__image"
        :class="{
          'tui-spaceImagePicker__image--progressing': isUploadImageLoaded,
        }"
        :style="backgroundStyle"
      >
        <input v-show="false" ref="inputFile" type="file" v-on="inputEvents" />

        <div class="tui-spaceImagePicker__loader">
          <PageLoader :loading="isUploadImageLoaded" />
        </div>

        <Button
          v-show="!isUploadImageLoaded"
          :aria-label="$str('edit_image', 'container_workspace')"
          :text="$str('edit_image', 'container_workspace')"
          :styleclass="{ small: true }"
          class="tui-spaceImagePicker__editButton"
          v-on="selectEvents"
        />
      </div>
    </Upload>
  </div>
</template>

<script>
import Upload from 'tui/components/form/Upload';
import Button from 'tui/components/buttons/Button';
import Loading from 'tui/components/icons/Loading';
import PageLoader from 'tui/components/loading/Loader';
import { config } from 'tui/config';

// GraphQL queries
import getUploadMetadata from 'container_workspace/graphql/upload_metadata';
const has = Object.prototype.hasOwnProperty;

export default {
  components: {
    Upload,
    Button,
    Loading,
    PageLoader,
  },

  props: {
    draftId: [String, Number],
    workspaceId: [Number, String],
  },

  apollo: {
    metadata: {
      query: getUploadMetadata,
      fetchPolicy: 'network-only',
      skip() {
        if (
          !has.call(this.metadata, 'itemId') ||
          !has.call(this.metadata, 'workspaceId')
        ) {
          // No point to skip if metadata is empty.
          return false;
        }

        // Only refetch when the props are different.
        return (
          this.metadata.itemId == this.draftId ||
          this.metadata.workspaceId == this.workspaceId
        );
      },

      variables() {
        return {
          workspace_id: this.workspaceId,
          draft_id: this.draftId,
          theme: config.theme.name,
        };
      },

      update({
        file_area: { item_id, repository_id, url, accept_types, context_id },
        image_url,
      }) {
        let parameter = {
          itemId: item_id,
          repositoryId: repository_id,
          acceptTypes: accept_types,
          url: url,
          image: image_url,
          contextId: context_id,

          // Need to cache this field as well.
          workspaceId: this.workspaceId,
        };

        this.$emit('ready', parameter);
        return parameter;
      },
    },
  },

  data() {
    return {
      metadata: {},
      uploadedImage: null,
      isUploadImageLoaded: false,
    };
  },

  computed: {
    backgroundStyle() {
      if (this.$apollo.loading) {
        return {
          'background-image': null,
        };
      }

      if (this.uploadedImage) {
        return {
          'background-image': `url("${this.uploadedImage}")`,
        };
      }

      return {
        'background-image': `url("${this.metadata.image}")`,
      };
    },
  },

  methods: {
    /**
     *
     * @param {String} url
     */
    handleFileLoaded({ file: { url } }) {
      this.uploadedImage = url;

      // Reset progress
      this.isUploadImageLoaded = false;

      // Clear any error.
      this.$emit('clear-error');
    },

    handleError() {
      // Reset the uploaded image.
      this.uploadedImage = null;

      // Reset progress
      this.isUploadImageLoaded = false;

      this.$emit(
        'upload-error',
        this.$str('error:upload_image', 'container_workspace')
      );
    },

    progressing() {
      this.isUploadImageLoaded = true;
    },
  },
};
</script>

<lang-strings>
  {
    "container_workspace": [
      "edit_image",
      "error:upload_image"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-spaceImagePicker {
  display: flex;
  flex-direction: column;
  flex-grow: 1;
  width: 100%;
  height: 100%;

  padding: var(--gap-2);
  border: var(--border-width-thin) solid var(--color-neutral-5);

  &--loading {
    align-items: center;
    justify-content: center;
    border: none;
  }

  &__image {
    display: flex;
    flex: 1;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;

    width: 100%;
    background-repeat: no-repeat;
    background-position: center;
    background-size: cover;
    border-radius: var(--border-radius-normal);

    &--progressing {
      align-items: stretch;
    }
  }

  &__loader {
    display: flex;
    flex: 1;
    flex-direction: column;
    justify-content: center;
  }

  &__editButton {
    margin-bottom: var(--gap-4);
  }
}
</style>
