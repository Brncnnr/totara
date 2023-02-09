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
-->

<template>
  <ModalPresenter :open="isOpen" @request-close="close">
    <Modal>
      <ModalContent
        :title="$str('manage_participation_select_activity', 'mod_perform')"
      >
        <Loader :loading="$apollo.loading">
          <Select
            v-if="mightHaveOptions"
            v-model="selectedActivityId"
            :options="options"
            name="manage-participation-activity-select"
          />
          <p v-else>
            {{ $str('manage_participation_no_activities', 'mod_perform') }}
          </p>
        </Loader>

        <template v-slot:buttons>
          <ActionLink
            v-if="mightHaveOptions"
            :href="continueHref"
            :text="$str('button_continue', 'mod_perform')"
            :disabled="!selectedActivityId"
            :styleclass="{ primary: true }"
          />
          <Button :text="$str('button_cancel', 'mod_perform')" @click="close" />
        </template>
      </ModalContent>
    </Modal>
  </ModalPresenter>
</template>

<script>
import ActionLink from 'tui/components/links/ActionLink';
import Button from 'tui/components/buttons/Button';
import Loader from 'tui/components/loading/Loader';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import Select from 'tui/components/form/Select';
import participantManageableActivitiesQuery from 'mod_perform/graphql/participant_manageable_activities';

export default {
  components: {
    ActionLink,
    Button,
    Loader,
    Modal,
    ModalContent,
    ModalPresenter,
    Select,
  },
  props: {
    value: {
      type: Boolean,
      required: true,
    },
  },
  data() {
    return {
      isOpen: false,
      selectedActivityId: null,
      activities: [],
    };
  },
  computed: {
    mightHaveOptions() {
      if (this.$apollo.loading) {
        return true;
      }

      return this.activities.length > 0;
    },
    options() {
      return this.activities.map(activity => {
        return {
          id: activity.id,
          label: activity.name,
        };
      });
    },
    continueHref() {
      if (!this.selectedActivityId) {
        return '#';
      }

      return this.$url(
        `/mod/perform/manage/participation/subject_instances.php?activity_id=${this.selectedActivityId}`
      );
    },
  },
  mounted() {
    if (this.value) {
      this.open();
    }
  },
  apollo: {
    activities: {
      query: participantManageableActivitiesQuery,
      update(data) {
        const activities =
          data['mod_perform_participant_manageable_activities'];

        if (activities.length > 0) {
          this.selectedActivityId = activities[0].id;
        }

        return activities;
      },
    },
  },
  methods: {
    open() {
      this.isOpen = true;
    },
    close() {
      this.isOpen = false;
      this.$emit('input', false);
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "button_cancel",
      "button_continue",
      "manage_participation",
      "manage_participation_select_activity",
      "manage_participation_no_activities"
    ]
  }
</lang-strings>
