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

  @author Rodney Cruden-Powell <rodney.cruden-powell@totaralearning.com>
  @module totara_perform
-->

<template>
  <div>
    <ConfirmationModal
      :open="openModal"
      :title="$str('close_all_instances', 'mod_perform')"
      :confirm-button-text="$str('close_all', 'mod_perform')"
      @confirm="closeAllInstances"
      @cancel="openModal = false"
    >
      {{ $str('close_all_instances_modal_message', 'mod_perform') }}
    </ConfirmationModal>
    <Button
      :text="$str('button_close_all_instances', 'mod_perform')"
      :aria-label="$str('button_close_all_instances', 'mod_perform')"
      @click="openModal = true"
    />
  </div>
</template>
<script>
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import Button from 'tui/components/buttons/Button';
import { redirectWithPost } from 'tui/dom/form';
import { notify } from 'tui/notifications';
import CloseAllOpenInstancesMutation from 'mod_perform/graphql/close_activity_subject_instances';

export default {
  components: {
    Button,
    ConfirmationModal,
  },
  props: {
    activityId: Number,
  },
  data() {
    return {
      openModal: false,
    };
  },
  methods: {
    async closeAllInstances() {
      try {
        await this.$apollo.mutate({
          mutation: CloseAllOpenInstancesMutation,
          variables: {
            input: {
              activity_id: this.activityId,
            },
          },
        });
        redirectWithPost(window.location, {
          close_all_instances_success: true,
        });
      } catch (e) {
        this.openModal = false;
        this.showErrorNotification();
      }
    },

    showErrorNotification() {
      notify({
        message: this.$str('toast_error_generic_update', 'mod_perform'),
        type: 'error',
      });
    },
  },
};
</script>

<lang-strings>
{
  "mod_perform": [
    "button_close_all_instances",
    "toast_error_generic_update",
    "close_all",
    "close_all_instances",
    "close_all_instances_modal_message"
  ]
}
</lang-strings>
