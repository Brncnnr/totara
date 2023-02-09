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
  @module totara_engage
-->

<script>
export default {
  props: {
    value: Object,
  },

  watch: {
    value: {
      handler(newVal, oldVal) {
        window.removeEventListener('beforeunload', this.$_unloadHandler);

        if (newVal != oldVal) {
          window.addEventListener('beforeunload', this.$_unloadHandler);
        }
      },
      immediate: true,
    },
  },

  beforeDestroy() {
    window.removeEventListener('beforeunload', this.$_unloadHandler);
  },

  methods: {
    $_unloadHandler(event) {
      // Cancel the event as stated by the standard.
      event.preventDefault();

      // For older browsers that still show custom message.
      const discardUnsavedChanges = this.$str(
        'unsaved_changes_warning',
        'totara_engage'
      );

      // Chrome requires returnValue to be set.
      event.returnValue = discardUnsavedChanges;

      return discardUnsavedChanges;
    },
  },

  render() {
    return null;
  },
};
</script>

<lang-strings>
  {
    "totara_engage": [
      "unsaved_changes_warning"
    ]
  }
</lang-strings>
