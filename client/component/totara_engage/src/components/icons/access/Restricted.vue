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

<script>
import AccessRestricted from 'tui/components/icons/AccessRestricted';

export default {
  functional: true,
  components: {
    AccessRestricted,
  },

  props: {
    alt: String,
    size: [String, Number],
    title: String,
    coloring: {
      type: Boolean,
      default: true,
    },
  },

  render(h, { props }) {
    let customClasses = [];

    // Pushing the default icon.
    customClasses.push('tui-engageIconRestricted');

    if (props.coloring) {
      customClasses.push('tui-engageIconRestricted--color');
    }

    let items = {
      alt: props.alt,
      customClass: customClasses,
      size: props.size,
      title: props.title,
    };

    return h(AccessRestricted, { props: items });
  },
};
</script>

<style lang="scss">
.tui-engageIconRestricted {
  &--color {
    color: var(--color-secondary);
  }
}
</style>
