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

  @author Qingyang Liu <qingyang.liu@totaralearning.com>
  @module totara_msteams
-->

<template>
  <ConfirmationModal
    :open="modalOpen"
    :title="$str('openexternally', 'theme_msteams')"
    class="tui-msTeamsExternalUrlModal"
    @confirm="modalConfirmed"
    @cancel="modalCancelled"
  >
    {{ $str('open_externally_content', 'theme_msteams') }}
  </ConfirmationModal>
</template>

<script>
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import { config } from 'tui/config';

export default {
  components: {
    ConfirmationModal,
  },

  data() {
    return {
      modalOpen: false,
      externalUrl: null,
    };
  },

  mounted() {
    window.document.body.addEventListener('click', this.triggerPopup);
  },

  unmounted() {
    window.document.body.removeEventListener('click', this.triggerPopup);
  },

  methods: {
    /**
     * @param {Object}  event
     */
    triggerPopup(event) {
      const link = event.target.closest('a');

      // If not a link or the link URL is an internal URL and doesn't have the show_redirect_confirmation param.
      // So for the confirmation modal to be shown, the URL must either be like:
      // - https://www.external-website.com/ OR
      // - https://www.totara-website.com/totara/plugin/page.php?totara_msteams_confirm_redirect=1
      if (
        !link ||
        !link.href ||
        (link.href.startsWith(config.wwwroot) &&
          !link.href.includes('totara_msteams_confirm_redirect=1'))
      ) {
        return;
      }

      event.preventDefault();
      this.externalUrl = link.href;
      this.openModal();
    },

    openModal() {
      this.modalOpen = true;
    },

    modalConfirmed() {
      if (this.externalUrl.includes('totara_msteams_confirm_redirect=1')) {
        window.open(this.externalUrl, 'redirect_from_url_activity');
        return;
      }
      window.open(this.externalUrl, '');
      this.modalOpen = false;
    },

    modalCancelled() {
      this.modalOpen = false;
    },
  },
};
</script>

<lang-strings>
{
  "theme_msteams": [
    "openexternally",
    "open_externally_content"
  ]
}
</lang-strings>
