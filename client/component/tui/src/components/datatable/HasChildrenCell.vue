<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Arshad Anwer <arshad.anwer@totaralearning.com>
  @module tui
-->

<template>
  <div class="tui-hasChildrenCell">
    <ButtonIcon
      :aria-label="$str('navigate_down', 'totara_core', currentParent.fullname)"
      :styleclass="{ transparent: true, transparentNoPadding: true }"
      @click="$emit('gotochild', $event)"
    >
      <ForwardArrow custom-class="tui-hasChildrenCell__forwardArrow" />
    </ButtonIcon>
  </div>
</template>

<script>
import ForwardArrow from 'tui/components/icons/ForwardArrow';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';

export default {
  components: {
    ForwardArrow,
    ButtonIcon,
  },

  props: {
    currentParent: {
      type: Object,
      required: true,
    },
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "navigate_down"
  ]
}
</lang-strings>
