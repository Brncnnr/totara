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
  <ButtonIcon
    :aria-label="ariaLabel || $str('closebuttontitle', 'core')"
    :styleclass="{ transparent: true }"
    @click.prevent="$emit('click', $event)"
  >
    <CloseIcon :size="size" />
  </ButtonIcon>
</template>

<script>
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import CloseIcon from 'tui/components/icons/Close';

export default {
  components: {
    ButtonIcon,
    CloseIcon,
  },

  props: {
    ariaLabel: String,
    size: {
      type: Number,
      default: 200,
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "closebuttontitle"
  ]
}
</lang-strings>
