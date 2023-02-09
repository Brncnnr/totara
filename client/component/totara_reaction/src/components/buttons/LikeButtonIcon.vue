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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module totara_reaction
-->
<template>
  <ButtonIcon
    :aria-label="ariaLabel"
    :styleclass="{ transparent, small, transparentNoPadding }"
    :disabled="disabled"
    :text="text"
    @click="$emit('click', $event)"
  >
    <Loading v-if="submitting" :size="iconSize" />

    <LikeIcon v-else-if="!liked" :size="iconSize" />

    <LikedIcon v-else :size="iconSize" />
  </ButtonIcon>
</template>

<script>
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import LikeIcon from 'tui/components/icons/Like';
import LikedIcon from 'tui/components/icons/LikeActive';
import Loading from 'tui/components/icons/Loading';

export default {
  components: {
    ButtonIcon,
    LikeIcon,
    LikedIcon,
    Loading,
  },

  props: {
    transparent: {
      type: Boolean,
      default: true,
    },

    small: {
      type: Boolean,
      default: true,
    },

    transparentNoPadding: {
      type: Boolean,
      default: true,
    },

    submitting: Boolean,
    liked: Boolean,
    iconSize: [String, Number],

    ariaLabel: {
      type: String,
      required: true,
    },

    disabled: Boolean,
    text: String,
  },
};
</script>
