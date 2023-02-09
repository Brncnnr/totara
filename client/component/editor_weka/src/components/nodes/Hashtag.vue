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

  @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
  @module editor_weka
-->

<template>
  <Dropdown :disabled="editorDisabled" class="tui-wekaHashtag">
    <template v-slot:trigger="{ toggle, isOpen }">
      <Hashtag
        :url="url"
        :text="$str('hashtag', 'editor_weka', text)"
        @click.native.capture.prevent="toggle"
      />
    </template>
    <DropdownButton @click="open(url)">
      {{ $str('view_search_results', 'editor_weka') }}
    </DropdownButton>
  </Dropdown>
</template>

<script>
import BaseNode from 'editor_weka/components/nodes/BaseNode';
import Hashtag from 'tui/components/json_editor/nodes/Hashtag';
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownButton from 'tui/components/dropdown/DropdownButton';

export default {
  components: {
    Hashtag,
    Dropdown,
    DropdownButton,
  },

  extends: BaseNode,

  computed: {
    text() {
      return this.attrs.text;
    },

    url() {
      return this.$url('/totara/catalog/index.php', {
        catalog_fts: this.attrs.text,
      });
    },
  },

  methods: {
    open(url) {
      window.open(url);
    },
  },
};
</script>

<lang-strings>
  {
    "editor_weka": [
      "actions_menu_for",
      "view_search_results",
      "hashtag"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-wekaHashtag {
  display: inline-block;
  white-space: normal;

  &__text {
    color: var(--color-state);

    &:hover {
      // Hover state, for now we keep the same color.
      color: var(--color-state);
    }
  }

  .tui-dropdown__menu {
    width: auto;
  }
}
</style>
