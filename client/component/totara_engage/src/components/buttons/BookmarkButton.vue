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
  @module totara_engage
-->
<template>
  <ButtonIcon
    :styleclass="styleClass"
    :disabled="disabled"
    :aria-label="ariaLabelText"
    @click.prevent.stop="$emit('click', !bookmarked)"
  >
    <Bookmark v-if="!bookmarked" :size="size" />
    <BookmarkActive v-else :size="size" />
  </ButtonIcon>
</template>

<script>
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Bookmark from 'tui/components/icons/Bookmark';
import BookmarkActive from 'tui/components/icons/BookmarkActive';

export default {
  components: {
    ButtonIcon,
    Bookmark,
    BookmarkActive,
  },

  props: {
    /**
     * For overriding the aria label.
     */
    ariaLabel: String,

    size: [String, Number],

    bookmarked: {
      type: Boolean,
      required: true,
    },

    disabled: {
      type: Boolean,
      default: false,
    },

    small: {
      type: Boolean,
      default: false,
    },

    primary: {
      type: Boolean,
      default: false,
    },

    square: {
      type: Boolean,
      default: false,
    },

    toggle: {
      type: Boolean,
      default: false,
    },

    transparent: {
      type: Boolean,
      default: false,
    },

    circle: {
      type: Boolean,
      default: true,
    },
  },

  computed: {
    styleClass() {
      return {
        small: this.small,
        primary: this.primary,
        square: this.square,
        toggle: this.toggle,
        transparent: this.transparent,
        circle: this.circle,
      };
    },

    ariaLabelText() {
      if (this.ariaLabel) {
        return this.ariaLabel;
      }

      if (this.bookmarked) {
        return this.$str('unbookmark', 'totara_engage');
      }

      return this.$str('bookmark', 'totara_engage');
    },
  },
};
</script>

<lang-strings>
  {
    "totara_engage": [
      "bookmark",
      "unbookmark"
    ]
  }
</lang-strings>
