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
  @module totara_perform
-->

<template>
  <ModalPresenter :open="open" @request-close="closeModal">
    <Modal
      class="tui-performQuestionPreviewModal"
      size="normal"
      :aria-labelledby="$id('question-element-preview-modal')"
    >
      <ModalContent
        :title="$str('preview', 'mod_perform')"
        :title-id="$id('question-element-preview-modal')"
        close-button
      >
        <Loader :loading="$apollo.loading">
          <Uniform v-if="element">
            <p>
              <strong>{{ element.title }}</strong>
              <RequiredOptionalIndicator
                v-if="element.is_respondable"
                :is-required="element.is_required"
              />
            </p>

            <component
              :is="component"
              :data="element.data"
              :extra-plugin-config-data="extraPluginConfigData"
              :report-preview="true"
              :section-element="{
                element: element,
                id: elementId,
                other_responder_groups: [],
              }"
            />
          </Uniform>
        </Loader>
        <template v-slot:buttons>
          <Button :text="$str('close', 'totara_core')" @click="closeModal" />
        </template>
      </ModalContent>
    </Modal>
  </ModalPresenter>
</template>
<script>
import Button from 'tui/components/buttons/Button';
import Card from 'tui/components/card/Card';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import ElementQuery from 'mod_perform/graphql/element';
import Loader from 'tui/components/loading/Loader';
import Uniform from 'tui/components/uniform/Uniform';
import RequiredOptionalIndicator from 'mod_perform/components/user_activities/RequiredOptionalIndicator';

export default {
  components: {
    Card,
    RequiredOptionalIndicator,
    Uniform,
    Loader,
    Button,
    Modal,
    ModalContent,
    ModalPresenter,
  },
  props: {
    open: {
      type: Boolean,
    },
    elementId: {
      type: Number,
      required: true,
    },
  },
  data() {
    return {
      element: null,
    };
  },
  computed: {
    component() {
      if (this.element === null) {
        return null;
      }

      return tui.asyncComponent(
        this.element.element_plugin.admin_view_component
      );
    },
    /**
     * @return {Object}
     */
    extraPluginConfigData() {
      if (this.element === null) {
        return null;
      }

      return JSON.parse(
        this.element.element_plugin.plugin_config.extra_config_data
      );
    },
  },
  methods: {
    closeModal() {
      this.$emit('request-close');
    },
  },
  apollo: {
    element: {
      query: ElementQuery,
      variables() {
        return { element_id: this.elementId };
      },
      update: data => {
        const element = data['mod_perform_element'];

        return Object.assign({}, element, { data: JSON.parse(element.data) });
      },
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "preview"
    ],
    "totara_core": [
      "close"
    ]
  }
</lang-strings>
