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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module tui
-->

<script>
import ErrorPageRender from 'tui/components/errors/ErrorPageRender';
import { showError } from 'tui/errors';

export default {
  // must declare here so it is picked up by lang string scanner
  components: {
    ErrorPageRender,
  },

  data() {
    return {
      errored: false,
      error: null,
    };
  },

  errorCaptured(err, vm, info) {
    // we only care about render errors - we don't want to unmount the
    // entire tree because an event handler threw an exception
    if (info == 'render') {
      this.errored = true;
      this.error = err;
      return;
    }

    showError(err, { vm });
  },

  methods: {
    /**
     * Try to render again.
     *
     * All the original components will have been unmounted so all component
     * state will be reset.
     */
    retry() {
      this.errored = false;
    },
  },

  render(h) {
    if (this.errored) {
      return h(ErrorPageRender, {
        props: { error: this.error, retryable: true },
        on: { retry: this.retry },
      });
    } else {
      return this.$scopedSlots.default();
    }
  },
};
</script>
