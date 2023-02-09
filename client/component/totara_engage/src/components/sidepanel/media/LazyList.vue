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

  @author Brian Barnes <brian.barnes@totaralearning.com>
  @module totara_engage
-->
<template>
  <div class="tui-engageSidepanelLazylist">
    <template v-if="total === 0">
      {{ noItems }}
    </template>
    <Loading v-else-if="loading" />
    <template v-else>
      <ul class="tui-engageSidepanelLazylist__list">
        <li v-for="name in names.slice(0, 9)" :key="name">
          {{ name }}
        </li>
      </ul>
      <p v-if="total > 10">
        {{ $str('numberofmore', 'totara_engage', total - 10) }}
      </p>
    </template>
  </div>
</template>
<script>
import Loading from 'tui/components/icons/Loading';

export default {
  components: {
    Loading,
  },

  props: {
    total: {
      type: Number,
      required: true,
    },
    names: {
      type: Array,
      required: true,
    },
    loading: {
      type: Boolean,
      default: false,
    },
    noItems: {
      type: String,
      required: true,
    },
  },
};
</script>

<lang-strings>
  {
    "totara_engage": [
      "numberofmore"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageSidepanelLazylist {
  &__list {
    margin: 0;
    list-style-type: none;
  }
}
</style>
