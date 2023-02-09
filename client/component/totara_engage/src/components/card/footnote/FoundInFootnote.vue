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
  @module totara_engage
-->

<template>
  <div
    :style="{ visibility: containers.length > 0 ? 'visible' : 'hidden' }"
    class="tui-cardFoundInFootnote"
  >
    <span class="tui-cardFoundInFootnote__text">
      {{ $str('foundin', 'totara_engage') }}
    </span>
    <a
      v-if="containers.length === 1"
      class="tui-cardFoundInFootnote__url"
      :href="containerUrl"
    >
      {{ containerName }}
    </a>
    <span v-else>
      multiple containers
    </span>
  </div>
</template>

<script>
export default {
  props: {
    containers: {
      type: Array,
      default: () => [],
      validator: containers =>
        containers.every(container =>
          ['name', 'type', 'url'].every(prop => prop in container)
        ),
    },
  },

  computed: {
    containerName() {
      return this.containers[0].name;
    },
    containerUrl() {
      return this.containers[0].url;
    },
  },
};
</script>

<lang-strings>
{
  "totara_engage": [
    "foundin"
  ]
}
</lang-strings>
