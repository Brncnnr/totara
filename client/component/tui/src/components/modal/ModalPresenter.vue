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
import PropsProvider from 'tui/components/util/PropsProvider';

export const presenterInterfaceName = 'modal-presenter-interface';

/**
 * Shows and hides the modal, recreating the widget each time
 */
export default {
  components: {
    PropsProvider,
  },

  props: {
    open: Boolean,
  },

  provide() {
    return {
      [presenterInterfaceName]: {
        setIsOpen: open => {
          this.isOpen = open;
        },
        requestClose: this.$_requestClose,
        data: this.childData,
      },
    };
  },

  data() {
    return {
      isOpen: false,
      childData: { open: false },
    };
  },

  watch: {
    open(open) {
      this.childData.open = open;
    },

    isOpen(isOpen) {
      if (!isOpen) {
        this.$emit('close-complete');
      }
    },
  },

  methods: {
    $_provide() {
      return {
        listeners: { 'request-close': this.$_requestClose },
      };
    },

    $_requestClose(e) {
      // Please be aware, $_request-close makes it an object if the argument is the falsy value
      this.$emit('request-close', e || {});
    },
  },

  render(h) {
    // open: should we be open
    // isOpen: are we currently open (is true until modal *finishes* closing)
    if (this.open || this.isOpen) {
      return h(
        'PropsProvider',
        { props: { provide: this.$_provide } },
        this.$scopedSlots.default()
      );
    }
    return null;
  },
};
</script>
