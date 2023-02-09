<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Simon Chester <simon.chester@totaralearning.com>
  @module mod_approval
-->

<template>
  <div class="tui-mod_approval-browsePickerHierarchyNav">
    <ButtonIcon
      class="tui-mod_approval-browsePickerHierarchyNav__back"
      :aria-label="$str('navigate_up', 'totara_core')"
      :styleclass="{
        small: true,
        stealth: true,
      }"
      @click="$emit('back')"
    >
      <BackArrow />
    </ButtonIcon>
    <div>
      {{ currentName }}
    </div>
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
    hasBack: Boolean,
    currentName: String,
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
.tui-mod_approval-browsePickerHierarchyNav {
  display: flex;
  align-items: center;
  margin-top: var(--gap-1);

  &__back {
    margin-right: var(--gap-2);
  }
}
</style>
