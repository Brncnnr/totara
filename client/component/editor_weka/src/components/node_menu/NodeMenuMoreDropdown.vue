<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

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
  <NodeMenuActionDropdown
    :position="position"
    :actions="actions"
    :aria-label="$str('more_options', 'editor_weka')"
    :title="$str('more_options', 'editor_weka')"
    :caret="false"
    :button-attrs="{ 'data-weka-more': 'true' }"
  >
    <template v-slot:icon>
      <MoreIcon />
    </template>
  </NodeMenuActionDropdown>
</template>

<script>
import NodeMenuActionDropdown from 'editor_weka/components/node_menu/NodeMenuActionDropdown';
import MoreIcon from 'tui/components/icons/More';

export default {
  components: {
    NodeMenuActionDropdown,
    MoreIcon,
  },

  props: {
    text: String,
    position: {
      type: String,
      default: 'bottom-right',
    },
    actions: Array,
  },
};
</script>

<lang-strings>
{
  "editor_weka": ["more_options"]
}
</lang-strings>
