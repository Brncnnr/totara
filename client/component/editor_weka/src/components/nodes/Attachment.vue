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
  <div class="tui-wekaAttachment">
    <template v-if="!$apollo.loading">
      <ImageAttachment
        v-if="isImage"
        :file-size="file ? file.file_size : 0"
        :draft-id="itemId"
        :filename="filename"
        :alt-text="option.alttext || null"
        :enable-convert="hasImageNode"
        :has-download-url="true"
        :disabled="editorDisabled"
        @convert-to-embedded-media="toImage"
        @update-alt-text="updateAltText"
        @delete="removeNode"
        @download="download"
      />

      <VideoAttachment
        v-else-if="isVideo"
        :file-size="file ? file.file_size : 0"
        :draft-id="itemId"
        :filename="filename"
        :enable-convert="hasVideoNode"
        :has-download-url="true"
        :disabled="editorDisabled"
        @convert-to-embedded-media="toVideo"
        @delete="removeNode"
        @download="download"
      />

      <AudioAttachment
        v-else-if="isAudio"
        :file-size="file ? file.file_size : 0"
        :draft-id="itemId"
        :filename="filename"
        :enable-convert="hasAudioNode"
        :has-download-url="true"
        :disabled="editorDisabled"
        @convert-to-embedded-media="toAudio"
        @delete="removeNode"
        @download="download"
      />

      <FileAttachment
        v-else
        :file-size="file ? file.file_size : 0"
        :filename="filename"
        :draft-id="itemId"
        :has-download-url="true"
        :disabled="editorDisabled"
        @delete="removeNode"
        @download="download"
      />
    </template>
  </div>
</template>

<script>
import BaseNode from 'editor_weka/components/nodes/BaseNode';
import getDraftfile from 'editor_weka/graphql/get_draft_file';
import { IMAGE, VIDEO, AUDIO } from '../../js/helpers/media';
import ImageAttachment from 'editor_weka/components/attachments/ImageAttachment';
import FileAttachment from 'editor_weka/components/attachments/FileAttachment';
import VideoAttachment from 'editor_weka/components/attachments/VideoAttachment';
import AudioAttachment from 'editor_weka/components/attachments/AudioAttachment';

export default {
  components: {
    ImageAttachment,
    FileAttachment,
    VideoAttachment,
    AudioAttachment,
  },

  extends: BaseNode,
  apollo: {
    file: {
      query: getDraftfile,
      variables() {
        return {
          item_id: this.itemId,
          filename: this.filename,
        };
      },
    },
  },

  data() {
    return {
      file: {},
    };
  },

  computed: {
    isImage() {
      return this.file && this.file.media_type === IMAGE;
    },

    isVideo() {
      return this.file && this.file.media_type === VIDEO;
    },

    isAudio() {
      return this.file && this.file.media_type === AUDIO;
    },

    /**
     * @return {Number}
     */
    itemId() {
      return this.context.getItemId();
    },

    filename() {
      const attrs = this.attrs;

      if (attrs.filename) {
        return attrs.filename;
      }

      return this.$str('unknown_attachment', 'editor_weka');
    },

    option() {
      return this.attrs.option;
    },

    hasImageNode() {
      return this.context.hasImageNode();
    },

    hasVideoNode() {
      return this.context.hasVideoNode();
    },

    hasAudioNode() {
      return this.context.hasAudioNode();
    },

    /** @deprecated since Totara 13.3 */
    downloadUrl() {
      return null;
    },
  },

  methods: {
    toImage() {
      const params = {
        filename: this.filename,
        alttext: this.option.alttext || null,
      };

      this.context.convertToImage(this.getRange, params);
    },

    /**
     *
     * @param {String} altText
     */
    updateAltText(altText) {
      this.$_updateNode({
        filename: this.filename,
        size: this.file ? this.file.file_size : this.attrs.size,
        option: {
          alttext: altText,
        },
      });
    },

    /**
     *
     * @param {Object} params
     */
    $_updateNode(params) {
      this.context.updateNode(this.getRange, params);
    },

    toVideo() {
      const params = {
        filename: this.filename,
        mimeType: this.file.mime_type,
        subtitle: this.option.subtitle || null,
      };

      this.context.convertToVideo(this.getRange, params);
    },

    toAudio() {
      const params = {
        filename: this.filename,
        mimeType: this.file.mime_type,
        transcript: this.option.transcript || null,
      };

      this.context.convertToAudio(this.getRange, params);
    },

    removeNode() {
      this.context.removeNode(this.getRange);
    },

    async download() {
      // Use window.open instead of setting window.location to avoid triggering
      // unsaved changes prompt
      window.open(await this.context.getDownloadUrl(this.filename));
    },
  },
};
</script>

<lang-strings>
  {
    "editor_weka": [
      "unknown_attachment"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-wekaAttachment {
  @media (min-width: 491px) {
    flex-basis: 20%;
    min-width: 235px;
  }

  @media (max-width: 490px) {
    width: 100%;
  }
}
</style>
