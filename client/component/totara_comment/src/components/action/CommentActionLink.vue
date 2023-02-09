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
  <div class="tui-commentActionLink">
    <a
      v-if="linkText"
      href="#"
      class="tui-commentActionLink__link"
      :class="{
        'tui-commentActionLink__link--small': isSmall,
        'tui-commentActionLink__link--disabled': loading || !showLoadMore,
      }"
      @click.prevent="handleClick"
    >
      <span>{{ linkText }}</span>
    </a>
  </div>
</template>

<script>
import { isValid, SIZE_SMALL } from 'totara_comment/index';

export default {
  props: {
    loading: {
      type: Boolean,
      default: false,
    },

    showLoadMore: {
      type: Boolean,
      default: false,
    },

    size: {
      type: String,
      default() {
        return SIZE_SMALL;
      },

      validator(prop) {
        return isValid(prop);
      },
    },
  },

  computed: {
    isSmall() {
      return SIZE_SMALL === this.size;
    },

    linkText() {
      if (this.showLoadMore) {
        return this.$str('loadmorecomments', 'totara_comment');
      }

      return '';
    },
  },

  methods: {
    handleClick() {
      if (this.loading) {
        return;
      }

      if (this.showLoadMore) {
        this.$emit('load-more');
      }
    },
  },
};
</script>

<lang-strings>
  {
    "totara_comment": [
      "loadmorecomments"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-commentActionLink {
  &__link {
    &--small {
      @include tui-font-link-small();
    }

    &--disabled {
      color: var(--color-neutral-5);
      cursor: not-allowed;

      &:hover,
      &:focus {
        color: var(--color-neutral-5);
        text-decoration: none;
      }
    }
  }
}
</style>
