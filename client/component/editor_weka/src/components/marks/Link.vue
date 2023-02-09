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
  <a
    ref="content"
    :href="attrs.href"
    :title="attrs.title"
    :target="attrs.open_in_new_window ? '_blank' : null"
    @click="showDropdown"
  />
</template>

<script>
import BaseNode from 'editor_weka/components/nodes/BaseNode';
import pending from 'tui/pending';

export default {
  extends: BaseNode,

  props: {
    ariaLabel: String,
  },

  data() {
    return {
      textContent: '',
      dropdownShown: false,
    };
  },

  mounted() {
    // There's no direct way to get a mark's content from a MarkView using
    // ProseMirror APIs, so we use a MutationObserver to update when it changes.
    this.observer = new MutationObserver(this.$_updateTextContent);
    this.observer.observe(this.$refs.content, {
      childList: true,
      subtree: true,
      characterData: true,
    });
    this.$_updateTextContent();
  },

  beforeDestroy() {
    this.observer.disconnect();
  },

  methods: {
    showDropdown() {
      this.context.showMarkDropdown(this.getRange).then(inst => {
        this.dropdownShown = true;
        inst.$on('dismiss', () => (this.dropdownShown = false));
      });
    },

    $_updateTextContent() {
      // need to wait otherwise (in rare cases) we cause an infinite loop with ProseMirror when pasting
      const done = pending();
      setTimeout(() => {
        done();
        this.textContent = this.$refs.content.textContent;
      }, 0);
    },
  },
};
</script>
