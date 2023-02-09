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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module editor_weka
-->

<template>
  <Modal
    size="normal"
    :aria-labelledby="$id('title')"
    :dismissable="{ backdropClick: false }"
  >
    <ModalContent
      :title="
        isNew
          ? $str('insert_link_label', 'editor_weka')
          : $str('edit_link_label', 'editor_weka')
      "
      :title-id="$id('title')"
      :close-button="true"
    >
      <Form input-width="full" @submit.prevent="confirm">
        <p v-if="error">{{ error }}</p>
        <FormRow v-slot="{ id }" :label="$str('linkurl', 'editor')">
          <InputText :id="id" ref="url" v-model="url" :autofocus="true" />
        </FormRow>
        <FormRow v-slot="{ id }" :label="$str('display_text', 'editor_weka')">
          <InputText
            :id="id"
            :value="display == 'link' ? text : ''"
            :disabled="display != 'link'"
            @input="updateText"
          />
        </FormRow>
        <FormRow v-if="!urlIsMedia" v-slot="{ id }">
          <Checkbox :id="id" v-model="openInNewWindow">
            {{ $str('open_in_new_window', 'editor_weka') }}
          </Checkbox>
        </FormRow>
        <!-- <FormRow v-slot="{ id }" :label="$str('displayas', 'editor_weka')">
          <RadioGroup :id="id" v-model="display">
            <Radio value="link">
              Embedded media
            </Radio>
            <Radio v-if="!urlIsMedia" value="link_block">
              Link card
            </Radio>
            <Radio v-if="urlIsMedia" value="link_media">
              Plain link
            </Radio>
          </RadioGroup>
        </FormRow> -->
        <input type="submit" :style="{ display: 'none' }" />
      </Form>
      <template v-slot:buttons>
        <ButtonGroup>
          <Button
            :styleclass="{ primary: 'true' }"
            :text="$str('done', 'editor_weka')"
            :disabled="!formValid || loading"
            @click="confirm"
          />
          <ButtonCancel @click="close" />
        </ButtonGroup>
      </template>
    </ModalContent>
  </Modal>
</template>

<script>
import Vue from 'vue';
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import InputText from 'tui/components/form/InputText';
import Checkbox from 'tui/components/form/Checkbox';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import Button from 'tui/components/buttons/Button';
import ButtonCancel from 'tui/components/buttons/Cancel';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';

export default {
  components: {
    Form,
    FormRow,
    InputText,
    Checkbox,
    Modal,
    ModalContent,
    Button,
    ButtonCancel,
    ButtonGroup,
  },

  props: {
    isNew: Boolean,
    attrs: Object,
    save: Function,
    isMedia: Function,
  },

  data: function() {
    return {
      openInNewWindow: this.attrs.open_in_new_window,
      url: this.attrs.url,
      text: this.attrs.text,
      loading: false,
      display: this.attrs.type || 'link',
      wasText: !!this.attrs.text,
      error: null,
    };
  },

  computed: {
    urlIsMedia() {
      return this.isMedia && this.isMedia(this.url);
    },

    formValue() {
      return {
        type: this.display,
        url: this.url,
        text: this.text,
        open_in_new_window: this.openInNewWindow,
      };
    },

    formValid() {
      return !!this.url;
    },
  },

  watch: {
    urlIsMedia(media) {
      if (!this.wasText) {
        this.display = media ? 'link_media' : 'link';
      }
    },

    formValue() {
      this.error = null;
    },

    openInNewWindow(newVal) {
      if (newVal === true) {
        if (!this.text) {
          this.text = this.$str(
            'opens_in_new_windowx',
            'editor_weka',
            this.url
          );
        } else {
          this.text = this.$str(
            'opens_in_new_windowx',
            'editor_weka',
            this.text
          );
        }
      }
    },
  },

  mounted() {
    Vue.nextTick(() => {
      if (this.$refs.url && this.$refs.url.$el) {
        this.$refs.url.$el.focus();
      }
    });
  },

  methods: {
    close() {
      this.$emit('request-close');
    },

    confirm() {
      if (this.loading) {
        return;
      }
      this.loading = true;
      const attrs = this.formValue;

      attrs.url = this.fixUrl(attrs.url);

      Promise.resolve(this.save(attrs))
        .then(this.close)
        .catch(e => {
          console.error(e);
        })
        .then(() => (this.loading = false));
    },

    updateText(text) {
      this.text = text;
    },

    /**
     * Try to ensure uris like "wwww.examnple.com" are converted to a well formed url (http://www.example.com),
     * while preserving links that are most likely purposefully relative (/mod/perform/activity/index.php).
     *
     * @param uri {string}
     */
    fixUrl(uri) {
      try {
        new URL(uri);
      } catch (e) {
        // Assuming a relative link, leave it.
        if (uri.startsWith('/') || uri.startsWith('#')) {
          return uri;
        }

        // We are using http rather than https because most sites are configured
        // to automatically upgrade to https if it is available,
        // however the reverse is not true (https -> http).
        return `http://${uri}`;
      }

      return uri;
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "cancel",
    "ok"
  ],
  "editor": [
    "linkurl"
  ],
  "editor_weka": [
    "done",
    "displayas",
    "display_text",
    "edit_link_label",
    "insert_link_label",
    "error_no_url_info",
    "open_in_new_window",
    "opens_in_new_windowx"
  ]
}
</lang-strings>
