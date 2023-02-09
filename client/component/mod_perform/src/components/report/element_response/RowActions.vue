<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module mod_perform
-->

<template>
  <div class="tui-performResponseReportRowActions">
    <Dropdown
      v-if="hasActions"
      class="tui-performResponseReportRowActions__menu"
      context-mode="uncontained"
      position="bottom-right"
    >
      <template v-slot:trigger="{ toggle }">
        <MoreButton
          :aria-label="$str('activity_response_data_actions', 'mod_perform')"
          @click="toggle"
        />
      </template>
      <DropdownButton
        v-if="elementId && elementPreview"
        @click="showElementPreviewModal = true"
      >
        {{ $str('preview_question', 'mod_perform') }}
      </DropdownButton>

      <DropdownButton
        v-for="(format, index) in exportFormats"
        :key="index"
        @click="triggerExportModal(format.type)"
      >
        {{ format.trigger_string }}
      </DropdownButton>

      <DropdownButton @click="triggerViewReportModal()">
        {{ $str('view_as_report', 'mod_perform') }}
      </DropdownButton>
    </Dropdown>

    <QuestionElementPreviewModal
      v-if="elementId"
      :element-id="elementId"
      :open="showElementPreviewModal"
      @request-close="showElementPreviewModal = false"
    />

    <ExportConfirmModal
      :export-href="exportHref"
      :export-type="exportType"
      :open="showExportConfirmModal"
      @request-close="showExportConfirmModal = false"
    />

    <ViewReportConfirmModal
      :view-report-href="viewReportHref"
      :open="showViewReportConfirmModal"
      @request-close="showViewReportConfirmModal = false"
    />
  </div>
</template>

<script>
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownButton from 'tui/components/dropdown/DropdownButton';
import ExportConfirmModal from 'mod_perform/components/report/element_response/ExportConfirmModal';
import MoreButton from 'tui/components/buttons/MoreIcon';
import QuestionElementPreviewModal from 'mod_perform/components/report/element_response/QuestionElementPreviewModal';
import ViewReportConfirmModal from 'mod_perform/components/report/element_response/ViewReportConfirmModal';

export default {
  components: {
    Dropdown,
    DropdownButton,
    ExportConfirmModal,
    MoreButton,
    QuestionElementPreviewModal,
    ViewReportConfirmModal,
  },

  props: {
    elementId: {
      type: [Number],
    },
    elementPreview: {
      type: Boolean,
    },
    reportParams: {
      required: true,
      type: Object,
    },
    exportFormats: {
      type: Array,
    },
  },

  data() {
    return {
      // Format of export
      exportType: '',
      // Display the export confirmation modal
      showExportConfirmModal: false,
      // Show element preview modal
      showElementPreviewModal: false,
      // Show view report preview modal
      showViewReportConfirmModal: false,
    };
  },

  computed: {
    exportHref() {
      let params = this.reportParams;

      Object.assign(params, {
        action: 'item',
        export: 'Export',
        format: this.exportType,
      });
      return this.$url('/mod/perform/reporting/performance/export.php', params);
    },
    viewReportHref() {
      let params = Object.assign({}, this.reportParams, {
        action: 'item',
      });
      delete params.export;
      delete params.format;
      return this.$url(
        '/mod/perform/reporting/performance/response_data.php',
        params
      );
    },

    /**
     * Check if there is any available actions
     */
    hasActions() {
      return (
        (this.exportFormats && this.exportFormats.length) || this.elementPreview
      );
    },
  },

  methods: {
    /**
     * Display the export modal with the correct export type
     *
     * @param {String} type
     */
    triggerExportModal(type) {
      this.exportType = type;
      this.showExportConfirmModal = true;
    },
    /**
     * Display the 'View report' modal
     */
    triggerViewReportModal() {
      this.showViewReportConfirmModal = true;
    },
  },
};
</script>

<lang-strings>
{
  "mod_perform": [
    "activity_response_data_actions",
    "preview_question",
    "view_as_report"
  ]
}
</lang-strings>

<style lang="scss">
.tui-performResponseReportRowActions {
  display: flex;

  &__menu {
    margin-left: auto;
  }
}
</style>
