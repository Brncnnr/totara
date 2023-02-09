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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module totara_engage
-->

<template>
  <div class="tui-accessSetting">
    <ModalPresenter
      :open="showAccessModal"
      @request-close="showAccessModal = false"
    >
      <AccessModal
        :item-id="itemId"
        :component="component"
        :selected-access="accessValue"
        :selected-options="selectedOptions"
        :restricted-disabled="restrictedDisabled"
        :private-disabled="privateDisabled"
        :selected-time-view="selectedTimeView"
        :enable-time-view="enableTimeView"
        :submitting="submitting"
        :has-non-public-resources="hasNonPublicResources"
        :is-private="isPrivate"
        :is-restricted="isRestricted"
        :can-share="canShareResource"
        @done="submit"
        @warning-privatetorestrictedorpublic="warnPrivateToRestrictedOrPublic"
        @warning-restrictedtopublic="warnRestrictedToPublic"
      />
    </ModalPresenter>

    <ConfirmationModal
      :open="showWarningModal"
      :title="$str('privacywarningtitle', 'totara_playlist')"
      :confirm-button-text="$str('privacywarningconfirm', 'totara_playlist')"
      :loading="submitting"
      @confirm="submit(privacyWarningEvent)"
      @cancel="cancelPrivacyChange"
    >
      <p>
        {{ $str('change_playlist_visibility_confirm_1', 'totara_playlist') }}
      </p>
      <p>
        {{ $str('change_playlist_visibility_confirm_2', 'totara_playlist') }}
      </p>
    </ConfirmationModal>

    <AccessDisplay
      slot="content"
      :access-value="accessValue"
      :topics="topics"
      :time-view="selectedTimeView"
      :show-button="showShareButton"
      @request-open="showAccessModal = true"
    />
  </div>
</template>

<script>
import AccessModal from 'totara_engage/components/modal/AccessModal';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import { AccessManager } from 'totara_engage/index';
import AccessDisplay from 'totara_engage/components/sidepanel/access/AccessDisplay';

export default {
  components: {
    AccessModal,
    ConfirmationModal,
    ModalPresenter,
    AccessDisplay,
  },

  props: {
    submitting: {
      type: Boolean,
      default: false,
    },

    itemId: {
      type: [Number, String],
      required: true,
    },

    component: {
      type: String,
      required: true,
    },

    accessValue: {
      type: String,
      required: true,
      validator(prop) {
        return AccessManager.isValid(prop);
      },
    },

    topics: {
      type: Array,
      default() {
        return [];
      },
    },

    shares: {
      type: Array,
      default() {
        return [];
      },
    },

    openAccessModal: Boolean,

    openWarningModal: Boolean,

    selectedTimeView: {
      type: String,
      default: null,
    },

    enableTimeView: {
      type: Boolean,
      default: false,
    },

    hasNonPublicResources: Boolean,
    canShare: Boolean,
    showShareButton: Boolean,
  },

  data() {
    return {
      showAccessModal: this.openAccessModal,
      showWarningModal: this.openWarningModal,
      privacyWarningEvent: null,
    };
  },

  computed: {
    /**
     * @return {Object}
     */
    selectedOptions() {
      return {
        shares: this.shares,
        topics: this.topics,
      };
    },

    /**
     *
     * @returns {boolean}
     */
    restrictedDisabled() {
      return AccessManager.isPublic(this.accessValue);
    },

    privateDisabled() {
      return (
        AccessManager.isPublic(this.accessValue) ||
        AccessManager.isRestricted(this.accessValue)
      );
    },

    isPrivate() {
      return AccessManager.isPrivate(this.accessValue);
    },

    isRestricted() {
      return AccessManager.isRestricted(this.accessValue);
    },

    canShareResource() {
      if (this.canShare) {
        return true;
      }

      return !!this.showShareButton;
    },
  },

  watch: {
    showAccessModal(value) {
      if (!value) {
        this.$emit('close-modal');
      }
    },

    openAccessModal(value) {
      this.showAccessModal = value;
    },

    showWarningModal(value) {
      if (!value) {
        this.$emit('close-modal');
      }
    },

    openWarningModal(value) {
      this.showWarningModal = value;
    },
  },

  methods: {
    /**
     *
     * @param {String} access
     * @param {Array} topics
     * @param {Array} shares
     * @param {String} timeView
     */
    submit({ access, topics, shares, timeView }) {
      this.showAccessModal = false;
      this.showWarningModal = false;
      this.$emit('access-update', { access, topics, shares, timeView });
    },

    warnPrivateToRestrictedOrPublic(event) {
      this.showAccessModal = false;

      // open warningmodal with the event data
      this.privacyWarningEvent = event;
      this.showWarningModal = true;
    },

    warnRestrictedToPublic(event) {
      this.showAccessModal = false;

      // open warningmodal with the event data
      this.privacyWarningEvent = event;
      this.showWarningModal = true;
    },

    cancelPrivacyChange() {
      this.showWarningModal = false;
      this.showAccessModal = true;
    },
  },
};
</script>

<lang-strings>
  {
    "totara_playlist": [
      "privacywarningtitle",
      "change_playlist_visibility_confirm_1",
      "change_playlist_visibility_confirm_2",
      "privacywarningconfirm"
    ]
  }
</lang-strings>
