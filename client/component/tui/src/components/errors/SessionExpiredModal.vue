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

  @author Kunle Odusan <kunle.odusan@totaralearning.com>
  @module tui
-->

<template>
  <Modal :aria-labelledby="$id('title')" error-modal>
    <ModalContent
      :close-button="true"
      :title="$str('error:sesskey_expired_title', 'totara_core')"
      :title-id="$id('title')"
      @dismiss="$emit('cancel')"
    >
      <p>{{ message }}</p>
      <Button
        class="tui-sesskeyExpired__button"
        :styleclass="{ primary: true }"
        :text="buttonText"
        @click="continueAction"
      />
    </ModalContent>
  </Modal>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import { totaraUrl } from '../../js/internal/util/url';

export default {
  components: {
    Button,
    Modal,
    ModalContent,
  },
  props: {
    category: {
      type: String,
      required: true,
    },
  },
  computed: {
    message() {
      return this.category === 'require_login'
        ? this.$str('error:sesskey_expired_login_message', 'totara_core')
        : this.$str('error:sesskey_expired_refresh_message', 'totara_core');
    },
    buttonText() {
      return this.category === 'require_login'
        ? this.$str('error:sesskey_expired_login_button', 'totara_core')
        : this.$str('error:sesskey_expired_refresh_button', 'totara_core');
    },
  },
  methods: {
    /**
     * Button action.
     */
    continueAction() {
      this.category === 'require_login'
        ? (window.location = totaraUrl('/login/index.php'))
        : location.reload();
    },
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "error:sesskey_expired_title",
    "error:sesskey_expired_login_button",
    "error:sesskey_expired_login_message",
    "error:sesskey_expired_refresh_button",
    "error:sesskey_expired_refresh_message"
  ]
}
</lang-strings>

<style lang="scss">
.tui-sesskeyExpired {
  &__button {
    margin-top: var(--gap-6);
    margin-left: auto;
  }
}
</style>
