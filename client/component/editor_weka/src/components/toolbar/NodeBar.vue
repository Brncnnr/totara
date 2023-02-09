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
  <div v-weka-node-ui class="tui-wekaNodeBar" contenteditable="false">
    <ActionDropdown position="bottom-right" :actions="actions">
      <template v-slot:trigger="{ toggle, isOpen }">
        <ButtonIcon
          :styleclass="{ small: true, transparent: true }"
          :aria-expanded="isOpen ? 'true' : 'false'"
          :aria-label="
            ariaLabel ? ariaLabel : $str('more_items', 'editor_weka')
          "
          :data-weka-more="true"
          @click="toggle"
        >
          <More />
        </ButtonIcon>
      </template>
    </ActionDropdown>
  </div>
</template>

<script>
import ActionDropdown from 'editor_weka/components/editing/ActionDropdown';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import More from 'tui/components/icons/More';
import { wekaNodeUI } from '../directives';

export default {
  directives: {
    'weka-node-ui': wekaNodeUI,
  },

  components: {
    ActionDropdown,
    ButtonIcon,
    More,
  },

  props: {
    actions: {
      type: Array,
      required: true,
    },
    ariaLabel: String,
  },
};
</script>

<lang-strings>
{
  "editor_weka": ["more_items"]
}
</lang-strings>

<style lang="scss">
.tui-wekaNodeBar {
  display: flex;
  justify-content: flex-end;
  visibility: hidden;
}

.tui-weka:hover .tui-wekaNodeBar,
.ProseMirror-focused .tui-wekaNodeBar {
  visibility: visible;
}
</style>
