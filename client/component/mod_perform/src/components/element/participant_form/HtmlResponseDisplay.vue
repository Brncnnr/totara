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

  @author Jaron Steenson <jaron.steenson@totaralearning.com>
  @module mod_perform
-->
<template>
  <div class="tui-participantFormHtmlResponseDisplay">
    <div
      v-if="responseData && responseData.length > 0"
      ref="content"
      v-html="responseData"
    />
    <NoResponseSubmitted v-else />
  </div>
</template>

<script>
import NoResponseSubmitted from 'mod_perform/components/element/participant_form/NoResponseSubmitted';

export default {
  components: { NoResponseSubmitted },
  props: {
    data: Array,
  },

  computed: {
    /**
     * Parse the stringified response markup
     *
     * @return {HTML}
     */
    responseData() {
      if (!this.data || !this.data[0]) {
        return '';
      }

      return JSON.parse(this.data[0]);
    },
  },

  mounted() {
    this.$_scan();
  },

  updated() {
    this.$_scan();
  },

  methods: {
    /**
     * Required to handle Weka HTML.
     */
    $_scan() {
      this.$nextTick().then(() => {
        let content = this.$refs.content;
        if (!content) {
          return;
        }

        tui.scan(content);
      });
    },
  },
};
</script>
