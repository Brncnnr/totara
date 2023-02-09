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

<template>
  <!-- inline styles used in sample, but classes should be used normally! -->
  <div style="height: 400px;">
    <OrganisationPicker v-model="value" style="height: 100%" />
  </div>
</template>

<script>
import OrganisationPicker from 'mod_approval/components/browse_picker/OrganisationPicker';

export default {
  components: {
    OrganisationPicker,
  },

  data() {
    return {
      // this will be updated as the selection changes
      value: null,
    };
  },
};
</script>
