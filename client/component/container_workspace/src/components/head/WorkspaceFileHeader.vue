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

  @author Qingyang Liu <qingyang liu@totaralearning.com>
  @module container_workspace
-->
<template>
  <div class="tui-workspaceFileHeader">
    <div class="tui-workspaceFileHeader__back">
      <ButtonIcon
        :styleclass="{ transparent: true }"
        :aria-label="$str('back', 'core')"
        :text="$str('back', 'core')"
        @click="$emit('go-back')"
      >
        <BackArrow size="200" :alt="$str('back', 'core')" />
      </ButtonIcon>
    </div>

    <div class="tui-workspaceFileHeader__title">
      {{ $str('files', 'container_workspace') }}
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
};
</script>
<lang-strings>
  {
    "core": [
      "back"
    ],
    "container_workspace": [
      "files"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-workspaceFileHeader {
  display: flex;
  flex-direction: column;

  @media (max-width: 764px) {
    padding: var(--gap-4);
  }

  &__back {
    margin-top: var(--gap-4);

    .tui-iconBtn {
      padding: 0;
    }
  }

  &__title {
    @include tui-font-heading-page-title;
    margin-top: var(--gap-2);
    margin-bottom: var(--gap-12);
  }
}
</style>
