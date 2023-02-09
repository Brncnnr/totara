<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Jaron Steenson <jaron.steenson@totaralearning.com>
  @module mod_perform
-->

<template>
  <ActionCard class="tui-elementResponseReportingActionCard" :has-shadow="true">
    <template v-slot:card-body>
      <div class="tui-elementResponseReportingActionCard__record">
        <span class="tui-elementResponseReportingActionCard__record-count">
          {{ rowCount }}
        </span>
        {{ countString }}
      </div>
    </template>
    <template v-slot:card-action>
      <Dropdown
        class="tui-elementResponseReportingActionCard__menu"
        context-mode="uncontained"
        position="bottom-right"
      >
        <template v-slot:trigger="{ toggle, isOpen }">
          <Button
            v-if="hasActions"
            :aria-expanded="isOpen ? 'true' : 'false'"
            :aria-label="$str('export', 'mod_perform')"
            :caret="true"
            :text="$str('export', 'mod_perform')"
            @click="toggle"
          />
        </template>

        <DropdownButton
          v-for="(format, index) in exportFormats"
          :key="index"
          @click="triggerExportModal(format.type)"
        >
          {{ format.type }}
        </DropdownButton>
      </Dropdown>
      <Button
        v-if="hasActions"
        class="tui-elementResponseReportingActionCard__viewReport"
        position="bottom-right"
        :text="$str('button_view_as_report', 'mod_perform')"
        @click="tryViewReportModal()"
      />

      <ExportConfirmModal
        :open="exportConfirmModal"
        :export-href="exportHref"
        :export-type="exportType"
        @request-close="closeExportConfirmModal"
      />

      <ModalPresenter
        :open="exportLimitExceededModal"
        @request-close="closeExportLimitExceededModal"
      >
        <Modal
          :aria-labelledby="$id('question-element-preview-modal')"
          class="tui-elementResponseReportingExportConfirmModal"
          size="normal"
        >
          <ModalContent
            :title="$str('export_limit_exceeded_modal_title', 'mod_perform')"
            :title-id="$id('element-response-export-limit-exceeded-modal')"
            close-button
          >
            <p>
              {{
                $str(
                  'export_limit_exceeded_modal_text',
                  'mod_perform',
                  exportRowLimit
                )
              }}
            </p>

            <template v-slot:buttons>
              <Button
                :text="$str('close', 'totara_core')"
                @click="closeExportLimitExceededModal"
              />
            </template>
          </ModalContent>
        </Modal>
      </ModalPresenter>

      <ViewReportConfirmModal
        :view-report-href="viewReportHref"
        :open="showViewReportConfirmModal"
        @request-close="closeViewReportConfirmModal"
      />
    </template>
  </ActionCard>
</template>

<script>
import ActionCard from 'tui/components/card/ActionCard';
import Button from 'tui/components/buttons/Button';
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownButton from 'tui/components/dropdown/DropdownButton';
import ExportConfirmModal from 'mod_perform/components/report/element_response/ExportConfirmModal';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import ViewReportConfirmModal from 'mod_perform/components/report/element_response/ViewReportConfirmModal';

export default {
  components: {
    ActionCard,
    Button,
    Dropdown,
    DropdownButton,
    ExportConfirmModal,
    Modal,
    ModalContent,
    ModalPresenter,
    ViewReportConfirmModal,
  },
  props: {
    additionalExportHrefParams: {
      type: Object,
      required: true,
    },
    rowCount: {
      type: Number,
      required: true,
    },
    embeddedShortname: {
      type: String,
      required: true,
    },
    exportFormats: {
      type: Array,
    },
    filterHash: {
      type: String,
      required: true,
    },
    exportRowLimit: {
      type: Number,
      required: true,
    },
  },
  data() {
    return {
      exportConfirmModal: false,
      exportLimitExceededModal: false,
      // Format of export
      exportType: '',
      // Show view report preview modal
      showViewReportConfirmModal: false,
    };
  },
  computed: {
    exportRowLimitExceeded() {
      return this.rowCount > this.exportRowLimit;
    },

    exportHref() {
      if (this.exportRowLimitExceeded) {
        return '';
      }

      let params = this.additionalExportHrefParams;
      Object.assign(params, {
        action: 'bulk',
        export: 'Export',
        format: this.exportType,
        filtered_report_export_type: this.embeddedShortname,
        filtered_report_filter_hash: this.filterHash,
      });
      return this.$url('/mod/perform/reporting/performance/export.php', params);
    },

    viewReportHref() {
      if (this.exportRowLimitExceeded) {
        return '';
      }

      let params = Object.assign({}, this.additionalExportHrefParams, {
        action: 'bulk',
        filtered_report_export_type: this.embeddedShortname,
      });
      delete params.export;
      delete params.format;
      return this.$url(
        '/mod/perform/reporting/performance/response_data.php',
        params
      );
    },

    countString() {
      return this.rowCount === 1
        ? this.$str('x_record_found', 'mod_perform')
        : this.$str('x_records_found', 'mod_perform');
    },

    /**
     * Check if there is any available actions
     *
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
      this.tryConfirmExport();
    },
    /**
     * Display the 'View as report' modal
     */
    tryViewReportModal() {
      if (this.exportRowLimitExceeded) {
        this.openExportLimitExceededModal();
      } else {
        this.openViewReportConfirmModal();
      }
    },
    tryConfirmExport() {
      if (this.exportRowLimitExceeded) {
        this.openExportLimitExceededModal();
      } else {
        this.openExportConfirmModal();
      }
    },
    openViewReportConfirmModal() {
      this.showViewReportConfirmModal = true;
    },
    closeViewReportConfirmModal() {
      this.showViewReportConfirmModal = false;
    },
    openExportConfirmModal() {
      this.exportConfirmModal = true;
    },
    closeExportConfirmModal() {
      this.exportConfirmModal = false;
    },
    openExportLimitExceededModal() {
      this.exportLimitExceededModal = true;
    },
    closeExportLimitExceededModal() {
      this.exportLimitExceededModal = false;
    },
  },
};
</script>

<lang-strings>
{
  "mod_perform": [
    "button_cancel",
    "button_view_as_report",
    "export",
    "export_all",
    "export_confirm_modal_title",
    "export_limit_exceeded_modal_title",
    "export_limit_exceeded_modal_text",
    "x_record_found",
    "x_records_found"
  ],
  "totara_core": [
    "close"
  ]
}
</lang-strings>

<style lang="scss">
.tui-elementResponseReportingActionCard {
  margin-bottom: var(--gap-10);

  &__record {
    &-count {
      font-weight: bold;
    }
  }

  &__viewReport {
    margin-left: var(--gap-2);
  }
}
</style>
