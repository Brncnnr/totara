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
  <span>
    <ButtonIcon
      :aria-label="$str('button_export', 'mod_perform')"
      :styleclass="{ transparentNoPadding: true }"
      @click="openExportConfirmModal"
    >
      <DownloadIcon />
    </ButtonIcon>

    <ExportConfirmModal
      :open="exportConfirmModal"
      :export-href="exportHref"
      :export-type="$str('export_type_csv', 'mod_perform')"
      @request-close="closeExportConfirmModal"
    />
  </span>
</template>
<script>
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import DownloadIcon from 'tui/components/icons/Download';
import ExportConfirmModal from 'mod_perform/components/report/element_response/ExportConfirmModal';

export default {
  components: {
    ButtonIcon,
    ExportConfirmModal,
    DownloadIcon,
  },
  props: {
    additionalExportHrefParams: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      exportConfirmModal: false,
    };
  },
  computed: {
    exportHref() {
      let params = this.additionalExportHrefParams;
      Object.assign(params, {
        action: 'item',
        export: 'Export',
        format: 'csv',
      });
      return this.$url('/mod/perform/reporting/performance/export.php', params);
    },
  },
  methods: {
    openExportConfirmModal() {
      this.exportConfirmModal = true;
    },
    closeExportConfirmModal() {
      this.exportConfirmModal = false;
    },
  },
};
</script>
<lang-strings>
{
  "mod_perform": [
    "button_export",
    "export_type_csv"
  ]
}
</lang-strings>
