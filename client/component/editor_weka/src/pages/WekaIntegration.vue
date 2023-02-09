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
  @module editor_weka
-->

<template>
  <ErrorPageRender v-if="errorMessage" :message="errorMessage" :error="error" />
</template>

<script>
import ErrorPageRender from 'tui/components/errors/ErrorPageRender';
import { setupTextarea } from '../js/integration';
import { langString, loadLangStrings } from 'tui/i18n';

// stub component used for texteditor system integration
export default {
  components: {
    ErrorPageRender,
  },

  props: {
    params: Object,
  },

  data() {
    return {
      errorMessage: null,
      error: null,
    };
  },

  created() {
    try {
      setupTextarea(this.params);
    } catch (e) {
      let str;
      if (e.code === 'TEXTAREA_NOT_FOUND') {
        str = langString('error_textarea_not_found', 'editor_weka');
      } else {
        str = langString('error_failed_to_initialise', 'editor_weka');
      }
      loadLangStrings([str]).then(() => {
        this.errorMessage = str.toString();
        this.error = e;
      });
    }
  },
};
</script>
