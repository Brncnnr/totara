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
  <component
    :is="icon"
    :coloring="coloring"
    :alt="alt"
    :class="customClass"
    :size="size"
    :title="title"
  />
</template>
<script>
import PrivateIcon from 'totara_engage/components/icons/access/Private';
import PublicIcon from 'totara_engage/components/icons/access/Public';
import RestrictedIcon from 'totara_engage/components/icons/access/Restricted';
import { AccessManager } from 'totara_engage/index';

export default {
  components: {
    PrivateIcon,
    PublicIcon,
    RestrictedIcon,
  },

  props: {
    alt: String,
    customClass: [String, Object, Array],
    size: [String, Number],
    title: String,

    coloring: {
      type: Boolean,
      default: true,
    },

    access: {
      required: true,
      type: String,
      validator(prop) {
        return AccessManager.isValid(prop.toUpperCase());
      },
    },
  },

  computed: {
    innerDefaultStr() {
      let access = this.access.toLowerCase();
      return this.$str(access, 'totara_engage');
    },

    innerTitle() {
      if (!this.title) {
        return this.innerDefaultStr;
      }

      return this.title;
    },

    innerAlt() {
      if (!this.alt) {
        return this.innerDefaultStr;
      }

      return this.alt;
    },

    icon() {
      let access = this.access.toUpperCase();

      if (AccessManager.isPublic(access)) {
        return 'PublicIcon';
      } else if (AccessManager.isRestricted(access)) {
        return 'RestrictedIcon';
      }

      return 'PrivateIcon';
    },
  },
};
</script>

<lang-strings>
  {
    "totara_engage": [
      "public",
      "private",
      "restricted"
    ]
  }
</lang-strings>
