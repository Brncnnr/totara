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
  <div>
    <input
      v-show="false"
      ref="fileInput"
      type="file"
      :multiple="multiple"
      @change="$_pickFile"
    />
  </div>
</template>

<script>
export default {
  props: {
    autoTrigger: {
      // Prop to tell the component whether the trigger file picker when its created.
      type: Boolean,
      default: false,
    },

    maxFiles: {
      // Default to one file per pick.
      type: [String, Number],
    },
  },

  computed: {
    multiple() {
      // If maxfiles is one then we can only have one file per pick.
      return this.maxFiles != 1;
    },
  },

  mounted() {
    // This should be happened after mounted. IF it is not meant for auto trigger, then we skip it.
    if (!this.autoTrigger) {
      return;
    }

    this.$_click();
    this.$window.document.addEventListener('keyup', this.$_dismiss);
  },

  beforeDestroy() {
    this.$window.document.removeEventListener('keyup', this.$_dismiss);
  },

  methods: {
    /**
     *
     * @param {Event} event
     */
    $_pickFile(event) {
      let fileList = [];
      if (event && event.target && event.target.files) {
        fileList = event.target.files;
      }

      this.$emit('picked-files', fileList);
      this.$emit('dismiss');
    },

    $_click() {
      if (!this.$refs.fileInput) {
        return;
      }

      this.$refs.fileInput.click();
    },

    /**
     *
     * @param {KeyboardEvent} event
     */
    $_dismiss(event) {
      if (!event) {
        return;
      }

      if (event.key === 'Escape') {
        this.$emit('dismiss');
      }
    },
  },
};
</script>
