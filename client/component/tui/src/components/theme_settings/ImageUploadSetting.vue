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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module tui
-->

<template>
  <ImageUpload
    :item-id="metadata.file_area.draft_id"
    :repository-id="metadata.file_area.repository_id"
    :href="metadata.file_area.url"
    :current-url="metadata.current_url"
    :default-url="metadata.default_url"
    :accepted-types="metadata.type.valid_extensions"
    :aria-describedby="ariaDescribedby"
    :aria-label-extension="ariaLabelExtension"
    :context-id="parseInt(contextId)"
    :show-delete="showDelete"
    @update="updateImage"
    @delete="deleteImage"
  />
</template>

<script>
import ImageUpload from 'tui/components/form/ImageUpload';

export default {
  components: {
    ImageUpload,
  },

  props: {
    metadata: Object,
    ariaDescribedby: String,
    contextId: [Number, String],
    showDelete: Boolean,
    ariaLabelExtension: String,
  },

  methods: {
    /**
     * New image has been uploaded and we need to save it.
     *
     * @param {String} url
     */
    updateImage({ url }) {
      this.$emit('update', {
        ui_key: this.metadata.ui_key,
        url: url,
      });
    },

    /**
     * Delete image from form if draft or emit delete event.
     *
     * @param {Boolean} draft
     */
    deleteImage({ draft }) {
      this.$emit('delete', {
        ui_key: this.metadata.ui_key,
        draft: draft,
      });
    },
  },
};
</script>
