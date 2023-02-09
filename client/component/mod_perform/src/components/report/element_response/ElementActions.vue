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

  @author Jaron Steenson <jaron.steenson@totaralearning.com>
  @module mod_perform
  @deprecated since Totara 17.0, please use RowActions.vue instead
-->

<template>
  <div>
    <ButtonIcon
      :aria-label="$str('preview', 'mod_perform')"
      :styleclass="{ transparentNoPadding: true }"
      @click="showPreviewModal"
    >
      <PreviewIcon />
    </ButtonIcon>

    <ExportRowAction :additional-export-href-params="exportHrefParams" />

    <QuestionElementPreviewModal
      :element-id="elementId"
      :open="openPreviewModal"
      @request-close="closePreviewModal"
    />
  </div>
</template>
<script>
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import ExportRowAction from 'mod_perform/components/report/element_response/ExportRowAction';
import PreviewIcon from 'tui/components/icons/Preview';
import QuestionElementPreviewModal from 'mod_perform/components/report/element_response/QuestionElementPreviewModal';

export default {
  components: {
    ButtonIcon,
    ExportRowAction,
    PreviewIcon,
    QuestionElementPreviewModal,
  },
  props: {
    elementId: {
      required: true,
      type: Number,
    },
  },
  data() {
    return {
      openPreviewModal: false,
    };
  },
  computed: {
    exportHrefParams() {
      return {
        element_id: this.elementId,
      };
    },
  },
  methods: {
    closePreviewModal() {
      this.openPreviewModal = false;
    },
    showPreviewModal() {
      this.openPreviewModal = true;
    },
  },
};
</script>
<lang-strings>
  {
    "mod_perform": [
      "preview"
    ]
  }
</lang-strings>
