<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module weka_simple_multi_lang
-->
<template>
  <Modal
    size="large"
    :dismissable="{
      esc: false,
      backdropClick: false,
    }"
    class="tui-wekaEditSimpleMultiLangModal"
  >
    <ModalContent
      :title="$str('edit_language', 'weka_simple_multi_lang')"
      :close-button="true"
    >
      <MultiLangForm
        :lang="lang"
        :content="content"
        :editor-compact="editorCompact"
        :editor-extra-extensions="editorExtraExtensions"
        @submit="$emit('submit', $event)"
        @cancel="$emit('request-close')"
      />
    </ModalContent>
  </Modal>
</template>

<script>
import MultiLangForm from 'weka_simple_multi_lang/components/form/SimpleMultiLangForm';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';

export default {
  components: {
    MultiLangForm,
    Modal,
    ModalContent,
  },

  props: {
    lang: String,
    content: Array,
    editorCompact: Boolean,
    placeholderResolverClass: String,
  },

  computed: {
    editorExtraExtensions() {
      if (!this.placeholderResolverClass) {
        return [];
      }

      return [
        {
          name: 'weka_notification_placeholder_extension',
          options: {
            resolver_class_name: this.placeholderResolverClass,
          },
        },
      ];
    },
  },
};
</script>

<lang-strings>
{
  "weka_simple_multi_lang": [
    "edit_language"
  ]
}
</lang-strings>
