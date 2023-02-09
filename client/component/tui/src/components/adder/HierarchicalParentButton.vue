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
  <div class="tui-hierarchicalParentButton">
    <ButtonIcon
      :aria-label="$str('navigate_up', 'totara_core')"
      :styleclass="{
        transparent: true,
        transparentNoPadding: true,
      }"
      @click="$emit('click')"
    >
      <BackArrow custom-class="tui-hierarchicalParentButton__backArrow" />
    </ButtonIcon>
    <div class="tui-hierarchicalParentButton__label">{{ label }}</div>
  </div>
</template>

<script>
import BackArrow from 'tui/components/icons/BackArrow';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';

export default {
  components: {
    BackArrow,
    ButtonIcon,
  },

  props: {
    label: {
      type: String,
      required: true,
    },
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "navigate_up"
  ]
}
</lang-strings>

<style lang="scss">
.tui-hierarchicalParentButton {
  display: flex;
  align-items: center;
  padding: var(--gap-4) 0 0;

  &__backArrow {
    text-align: start;

    @media screen and (min-width: $tui-screen-xs) {
      text-align: center;
    }
  }

  &__label {
    margin-left: var(--gap-2);
  }
}
</style>
