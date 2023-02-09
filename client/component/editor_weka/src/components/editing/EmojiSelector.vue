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
  <div class="tui-wekaEmojiSelector">
    <ButtonIcon
      v-for="(emoji, index) in emojis"
      :key="index"
      :styleclass="{ stealth: true }"
      :aria-label="fromShortCode(emoji.shortcode)"
      @click.native="select(emoji)"
    >
      <Emoji
        class="tui-wekaEmojiSelector__emoji"
        :short-code="emoji.shortcode"
      />
    </ButtonIcon>
  </div>
</template>

<script>
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Emoji from 'tui/components/json_editor/nodes/Emoji';

export default {
  components: {
    ButtonIcon,
    Emoji,
  },

  props: {
    emojis: {
      type: Array,
      required: true,
      validator: emojis => emojis.every(emoji => 'shortcode' in emoji),
    },
  },

  methods: {
    select(emoji) {
      this.$emit('emoji-selected', emoji);
    },

    fromShortCode(shortCode) {
      return String.fromCodePoint('0x' + shortCode);
    },
  },
};
</script>

<style lang="scss">
.tui-wekaEmojiSelector {
  display: flex;
  flex-wrap: wrap;

  &__emoji {
    font-size: var(--font-size-22);
  }
}
</style>
