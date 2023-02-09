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
  @module totara_comment
-->
<template>
  <ButtonIcon
    :aria-label="$str('reply', 'totara_comment')"
    :text="buttonText"
    :styleclass="buttonStyleClass"
    :disabled="disabled"
    class="tui-commentReplyButtonIcon"
    @click="$emit('click', $event)"
  >
    <ReplyIcon :size="iconSize" />
  </ButtonIcon>
</template>

<script>
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import ReplyIcon from 'tui/components/icons/Reply';

export default {
  components: {
    ButtonIcon,
    ReplyIcon,
  },

  props: {
    iconSize: [String, Number],
    showText: Boolean,
    transparent: {
      type: Boolean,
      default: true,
    },
    disabled: Boolean,
    small: {
      type: Boolean,
      default: true,
    },
  },

  computed: {
    buttonText() {
      if (this.showText) {
        return this.$str('reply', 'totara_comment');
      }

      return '';
    },

    buttonStyleClass() {
      return {
        transparentNoPadding: this.transparent,
        small: this.small,
      };
    },
  },
};
</script>

<lang-strings>
  {
    "totara_comment": [
      "reply"
    ]
  }
</lang-strings>
