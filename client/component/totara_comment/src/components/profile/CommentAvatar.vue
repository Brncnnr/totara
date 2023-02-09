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
  <a v-if="profileUrl" :href="profileUrl">
    <CoreAvatar :src="imageSrc" :alt="imageAlt" size="xxsmall" />
  </a>

  <CoreAvatar v-else :src="imageSrc" :alt="imageAlt" size="xxsmall" />
</template>

<script>
import CoreAvatar from 'tui/components/avatar/Avatar';

export default {
  components: {
    CoreAvatar,
  },

  props: {
    imageSrc: {
      required: true,
      type: String,
    },

    imageAlt: {
      required: true,
      type: String,
    },

    profileUrl: String,
  },
};
</script>
