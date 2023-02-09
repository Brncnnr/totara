/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * Totara Enterprise Extensions is provided only to Totara
 * Learning Solutions LTD's customers and partners, pursuant to
 * the terms and conditions of a separate agreement with Totara
 * Learning Solutions LTD or its affiliate.
 *
 * If you do not have an agreement with Totara Learning Solutions
 * LTD, you may not access, use, modify, or distribute this software.
 * Please contact [licensing@totaralearning.com] for more information.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @module tui
 */

export default {
  props: {
    // Array of Objects, each describing the properties for specifically file
    // upload fields that are part of this Form.
    fileFormFieldData: {
      type: Array,
      default: function() {
        return [];
      },
    },
  },

  data() {
    return {
      key: 0,
      fileData: {},
    };
  },

  watch: {
    fileFormFieldData: {
      deep: true,
      immediate: true,
      handler() {
        this.setFileData();
        // When a user uploads an image, saves the form, deletes the image without
        // refreshing the page then ImageUpload component will have stale data that is
        // no longer relevant so this will force the component chain to re-render after
        // saving and these components will have the latest data to work with.
        ++this.key;
      },
    },
  },

  methods: {
    /**
     * Handle fileuploader setup independently of Uniform and initialValues
     * because file uploading doesn't really work in a way that Uniform can
     * fully support
     */
    setFileData() {
      for (let i = 0; i < this.fileFormFieldData.length; i++) {
        let fileData = this.fileFormFieldData[i];
        if (typeof this.fileData[fileData.ui_key] !== 'undefined') {
          this.fileData[fileData.ui_key] = fileData;
        }
      }
    },

    /**
     * Should the trash icon be displayed.
     */
    showDelete(fileData) {
      return (
        !!fileData.current_url && fileData.current_url !== fileData.default_url
      );
    },

    /**
     * Indicate that an image needs to be saved.
     *
     * @param {String} ui_key
     * @param {String} url
     */
    saveImage({ ui_key, url }) {
      if (this.fileData[ui_key] && url) {
        this.fileData[ui_key] = Object.assign({}, this.fileData[ui_key], {
          action: 'SAVE',
        });
      }
    },

    /**
     * Indicate that an image needs to be reset to the default image.
     *
     * @param {String} ui_key
     * @param {Boolean} draft
     */
    resetImage({ ui_key, draft }) {
      if (this.fileData[ui_key]) {
        // If this is not a draft then we want to reset the file back to the default when saving.
        // Also, if we have deleted the currently saved file but have not submitted the form yet
        // then for any file that we upload and delete again we want the action to still be RESET
        // until we submit the form.
        if (!draft || this.fileData[ui_key].deleted) {
          this.fileData[ui_key] = Object.assign({}, this.fileData[ui_key], {
            action: 'RESET',
            current_url: null,
            // Once the current image (saved image) has been deleted we should remember that when
            // we submit the form we ultimately want to reset the file when:
            //   1. no new image has been uploaded
            //   2. a new image has been uploaded and also deleted
            deleted: true,
          });
        }
      }
    },
  },
};
