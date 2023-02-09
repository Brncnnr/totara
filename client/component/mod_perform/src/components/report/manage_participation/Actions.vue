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

  @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
  @module totara_perform
-->

<template>
  <div>
    <ParticipantDeleteActionModal
      v-if="isParticipantReport"
      :participant-instance-id="id"
      :report-type="reportType"
      :delete-modal-open="deleteModalOpen"
      @modal-close="closeDeleteModal"
    />
    <SubjectDeleteActionModal
      v-if="isSubjectReport"
      :subject-instance-id="id"
      :report-type="reportType"
      :delete-modal-open="deleteModalOpen"
      @modal-close="closeDeleteModal"
    />
    <SubjectOpenCloseActionModal
      v-if="isSubjectReport"
      :modal-open="showModalOpen"
      :subject-instance-id="id"
      :is-open="isOpen"
      :report-type="reportType"
      @modal-close="modalClose"
    />
    <ParticipantOpenCloseActionModal
      v-if="isParticipantReport"
      :modal-open="showModalOpen"
      :participant-instance-id="id"
      :is-open="isOpen"
      :report-type="reportType"
      @modal-close="modalClose"
    />
    <SectionOpenCloseActionModal
      v-if="isSectionReport"
      :modal-open="showModalOpen"
      :participant-section-id="id"
      :is-open="isOpen"
      :report-type="reportType"
      @modal-close="modalClose"
    />

    <Dropdown
      v-if="showActions"
      context-mode="uncontained"
      position="bottom-right"
    >
      <template v-slot:trigger="{ toggle }">
        <MoreButton
          :no-padding="true"
          :aria-label="$str('activity_action_options', 'mod_perform')"
          @click="toggle"
        />
      </template>

      <!-- Add participants -->
      <template
        v-if="isSubjectReport && canAddParticipants && !isParticipantPending"
      >
        <DropdownItem :href="participationManagementUrl">
          {{ $str('activity_participants_add', 'mod_perform') }}
        </DropdownItem>
      </template>

      <!-- Close/open instance -->
      <template
        v-if="showActions && !isParticipantPending && !isParticipantReadonly"
      >
        <DropdownButton @click="showModal">
          {{ $str(isOpen ? 'button_close' : 'button_reopen', 'mod_perform') }}
        </DropdownButton>
      </template>

      <!-- Delete instance -->
      <template v-if="showActions && (isSubjectReport || isParticipantReport)">
        <DropdownButton @click="showDeleteModal">
          {{ $str('button_delete', 'mod_perform') }}
        </DropdownButton>
      </template>
    </Dropdown>
  </div>
</template>
<script>
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownButton from 'tui/components/dropdown/DropdownButton';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import MoreButton from 'tui/components/buttons/MoreIcon';
import ParticipantDeleteActionModal from 'mod_perform/components/report/manage_participation/ParticipantDeleteActionModal';
import ParticipantOpenCloseActionModal from 'mod_perform/components/report/manage_participation/ParticipantOpenCloseActionModal';
import SectionOpenCloseActionModal from 'mod_perform/components/report/manage_participation/SectionOpenCloseActionModal';
import SubjectOpenCloseActionModal from 'mod_perform/components/report/manage_participation/SubjectInstanceOpenCloseActionModal';
import SubjectDeleteActionModal from 'mod_perform/components/report/manage_participation/SubjectInstanceDeleteActionModal';

const REPORT_TYPE_SUBJECT_INSTANCE = 'SUBJECT_INSTANCE';
const REPORT_TYPE_PARTICIPANT_INSTANCE = 'PARTICIPANT_INSTANCE';
const REPORT_TYPE_PARTICIPANT_SECTION = 'PARTICIPANT_SECTION';

export default {
  components: {
    Dropdown,
    DropdownButton,
    DropdownItem,
    MoreButton,
    ParticipantDeleteActionModal,
    ParticipantOpenCloseActionModal,
    SectionOpenCloseActionModal,
    SubjectOpenCloseActionModal,
    SubjectDeleteActionModal,
  },
  props: {
    reportType: {
      type: String,
    },
    id: {
      type: String,
    },
    isOpen: {
      type: Boolean,
    },
    showActions: {
      type: Boolean,
      required: false,
      default: true,
    },
    canAddParticipants: {
      type: Boolean,
    },
    isParticipantPending: {
      type: Boolean,
    },
    isParticipantReadonly: {
      type: Boolean,
      required: false,
      default: false,
    },
  },
  data() {
    return {
      showModalOpen: false,
      deleteModalOpen: false,
    };
  },
  computed: {
    isSectionReport() {
      return this.reportType === REPORT_TYPE_PARTICIPANT_SECTION;
    },
    isParticipantReport() {
      return this.reportType === REPORT_TYPE_PARTICIPANT_INSTANCE;
    },
    isSubjectReport() {
      return this.reportType === REPORT_TYPE_SUBJECT_INSTANCE;
    },
    /**
     * Get the url to the participation management
     *
     * @return {string}
     */
    participationManagementUrl() {
      return this.$url(
        '/mod/perform/manage/participation/add_participants.php',
        {
          subject_instance_id: this.id,
        }
      );
    },
  },
  methods: {
    modalClose() {
      this.showModalOpen = false;
    },
    showModal() {
      this.showModalOpen = true;
    },
    showDeleteModal() {
      this.deleteModalOpen = true;
    },
    closeDeleteModal() {
      this.deleteModalOpen = false;
    },
  },
};
</script>
<lang-strings>
  {
  "mod_perform": [
    "activity_participants_add",
    "activity_action_options",
    "button_close",
    "button_delete",
    "button_reopen"
  ]
  }
</lang-strings>
