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

  @author Alvin Smith <alvin.smith@totaralearning.com>
  @module container_workspace
-->

<template>
  <a :href="profileUrl">
    <Avatar :src="imageSrc" :alt="imageAlt" size="xxsmall" />
  </a>
</template>

<script>
import Avatar from 'tui/components/avatar/Avatar';

export default {
  components: {
    Avatar,
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

    profileUrl: {
      required: true,
      type: String,
    },
  },
};
</script>
