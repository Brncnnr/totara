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

  @author Matthias Bonk <matthias.bonk@totaralearning.com>
  @module mod_perform
  @deprecated since Totara 17.0, please use RowActions.vue instead
-->

<template>
  <div>
    <ExportRowAction :additional-export-href-params="exportHrefParams" />
  </div>
</template>
<script>
import ExportRowAction from 'mod_perform/components/report/element_response/ExportRowAction';

export default {
  components: {
    ExportRowAction,
  },
  props: {
    subjectUserId: {
      required: true,
      type: Number,
    },
  },
  computed: {
    exportHrefParams() {
      return {
        subject_user_id: this.subjectUserId,
      };
    },
  },
};
</script>
