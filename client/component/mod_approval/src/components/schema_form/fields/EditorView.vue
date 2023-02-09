<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Simon Chester <simon.chester@totaralearning.com>
  @module mod_approval
-->

<script>
import tui from 'tui/tui';

export default {
  props: {
    value: Object,
  },

  computed: {
    htmlContent() {
      return this.value
        ? this.value.html
        : this.$str('filter_na', 'mod_approval');
    },
  },

  watch: {
    htmlContent: {
      handler() {
        this.$nextTick(() => {
          this.$el.innerHTML = this.htmlContent;
          tui.scan(this.$el);
        });
      },
      immediate: true,
    },
  },

  render(h) {
    return h('div');
  },
};
</script>

<lang-strings>
{
  "mod_approval": [
    "filter_na"
  ]
}
</lang-strings>
