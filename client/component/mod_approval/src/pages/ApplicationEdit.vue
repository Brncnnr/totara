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

  @author Simon Tegg <simon.tegg@totaralearning.com>
  @module mod_approval
-->
<template>
  <div>
    <Header
      :back-url="backUrl"
      :machine-id="machineId"
      :show-save-buttons="true"
      :confirm-delete="$matches('confirmDelete')"
      :confirm-withdraw="$matches('confirmWithdraw')"
      :withdrawing="$matches('confirmWithdraw.withdrawing')"
    />
    <LayoutTwoColumn class="tui-mod_approval-applicationEdit__body">
      <template v-slot:left>
        <div class="tui-mod_approval-applicationEdit__sectionNav">
          <h3 class="tui-mod_approval-applicationEdit__sectionNavHeader">
            {{ $str('form_sections', 'mod_approval') }}
          </h3>
          <Loader v-if="$matches('loading')" :loading="true" />
          <template v-for="(section, index) in sections" v-else>
            <a
              :key="section.key"
              :href="'#' + sectionId(section)"
              :class="getActiveClass(index)"
              @click.prevent="
                $send({
                  type: $e.NAVIGATE_TO_SECTION,
                  index,
                  ref: $refs[sectionTitleRef(section)][0],
                })
              "
            >
              {{ sectionTitle(section) }}
            </a>
          </template>
        </div>
      </template>

      <template v-slot:right>
        <div class="tui-mod_approval-applicationEdit__schemaForm">
          <Loader v-if="$matches('loading')" :loading="true" />
          <SchemaForm
            v-if="renderForm"
            ref="schemaForm"
            :display="!$matches('loading')"
            :schema="formSchema"
            :initial-values="initialValues"
            @reform-mounted="setRefMethods"
            @loaded="$send($e.FORM_READY)"
            @change="formData => $send({ type: $e.UPDATE_FORM_DATA, formData })"
            @validation-changed="
              validationResult =>
                $send({ type: $e.VALIDATION_CHANGED, validationResult })
            "
          >
            <template v-slot:sections="{ sections, renderSection }">
              <template v-for="section in sections">
                <div :key="section.key">
                  <h3
                    :id="sectionId(section)"
                    :ref="sectionTitleRef(section)"
                    class="tui-mod_approval-applicationEdit__sectionTitle"
                    :tabindex="tabIndex"
                  >
                    {{ section.title || sectionTitle(section) }}
                  </h3>
                  <render :vnode="renderSection(section)" />
                  <div class="tui-mod_approval-applicationEdit__section" />
                </div>
              </template>
            </template>
          </SchemaForm>
          <SaveButtons v-if="!$matches('loading')" :machine-id="machineId" />
        </div>
      </template>
    </LayoutTwoColumn>

    <ConfirmationModal
      :open="$matches('confirmApplicationSubmission')"
      :title="$str('confirm_submit', 'mod_approval')"
      :confirm-button-text="$str('submit', 'mod_approval')"
      @confirm="$send($e.SUBMIT)"
      @cancel="$send($e.CANCEL)"
    >
      {{ $str('confirm_submit_detail', 'mod_approval') }}
    </ConfirmationModal>

    <ModalPresenter
      :open="$matches('confirmSavingApplication')"
      :dismissable="dismissable"
      @request-close="$send($e.CANCEL)"
    >
      <ConfirmChangesModal />
    </ModalPresenter>
    <Loader v-if="pageLoading" :loading="true" :fullpage="true" />
  </div>
</template>
<script>
import { MOD_APPROVAL__APPLICATION } from 'mod_approval/constants';
import { mapQueryParamsToContext } from 'mod_approval/common/helpers';

// Machine
import applicationEditMachine from 'mod_approval/application/edit/machine';

// Components
import LayoutTwoColumn from 'tui/components/layouts/LayoutTwoColumn';
import Header from 'mod_approval/components/application/header/ApplicationHeader';
import SaveButtons from 'mod_approval/components/application/SaveButtons';
import SchemaForm from 'mod_approval/components/schema_form/SchemaForm';
import Loader from 'tui/components/loading/Loader';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import ConfirmChangesModal from 'mod_approval/components/application/ConfirmChangesModal';

const isIE = document.body.classList.contains('ie');

export default {
  name: 'ApplicationEdit',
  components: {
    LayoutTwoColumn,
    Header,
    SaveButtons,
    SchemaForm,
    Loader,
    ModalPresenter,
    ConfirmationModal,
    ConfirmChangesModal,
  },

  props: {
    queryResults: {
      required: true,
      type: Object,
    },
    backUrl: {
      required: true,
      type: String,
    },
  },

  data() {
    return {
      dismissable: {
        esc: true,
      },
      machineId: MOD_APPROVAL__APPLICATION,
    };
  },

  computed: {
    renderForm() {
      return !this.$matches('loading') || this.$matches('loading.formLoading');
    },

    pageLoading() {
      return this.$matches('submittingApplication');
    },

    initialValues() {
      return this.$selectors.getFormData(this.$context);
    },

    title() {
      return this.$selectors.getTitle(this.$context);
    },

    sections() {
      return this.$selectors.getSections(this.$context);
    },

    activeSectionIndex() {
      return this.$context.activeSectionIndex;
    },

    formSchema() {
      return this.$selectors.getParsedFormSchema(this.$context);
    },
    tabIndex() {
      return isIE ? 0 : -1;
    },
  },
  methods: {
    sectionTitle(section) {
      return `${section.line} - ${section.label}`;
    },

    sectionTitleRef(section) {
      return `sectionTitle-${section.key}`;
    },

    sectionId(section) {
      return `section-${section.key}`;
    },

    setRefMethods() {
      const sectionIds = this.sections.map(this.sectionId);

      this.$send({
        type: this.$e.SET_REF_METHODS,
        focusFirstInvalid: this.$refs.schemaForm.focusFirstInvalid,
        trySubmit: this.$refs.schemaForm.trySubmit,
        validate: this.$refs.schemaForm.$refs.uniform.$refs.reform.$_validate,
        sectionIds,
      });
    },

    getActiveClass(index) {
      return index === this.activeSectionIndex
        ? 'tui-mod_approval-applicationEdit__sectionNavItem--active'
        : 'tui-mod_approval-applicationEdit__sectionNavItem';
    },
  },
  xState: {
    machine() {
      const loadApplicationResult = this.queryResults
        .mod_approval_load_application;

      return applicationEditMachine({ loadApplicationResult });
    },
    mapQueryParamsToContext(params) {
      return mapQueryParamsToContext(params);
    },
    mapContextToQueryParams(context, prevContext) {
      const notifyAndNotifyType = context.notify && context.notifyType;
      return {
        notify: notifyAndNotifyType ? prevContext.notify : undefined,
        notify_type: notifyAndNotifyType ? prevContext.notifyType : undefined,
      };
    },
  },
};
</script>
<lang-strings>
{
  "mod_approval": [
    "button_cancel",
    "confirm_changes",
    "confirm_changes_detail",
    "confirm_submit",
    "confirm_submit_detail",
    "error:delete_application",
    "error:save_application",
    "error:submit_application",
    "error:generic",
    "form_sections",
    "keep_approvals",
    "save",
    "submit",
    "success:clone_application",
    "success:create_draft_application",
    "success:delete_application",
    "success:save_application",
    "warning:save_application_empty",
    "warning:save_application_no_changes"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-applicationEdit {
  &__body {
    padding-right: var(--gap-8);
    padding-bottom: var(--gap-8);
    padding-left: var(--gap-8);

    // override <LayoutTwoColumn>'s unused heading slot which comes with spacing
    .tui-layoutTwoColumn__heading {
      margin-top: 0;
      margin-bottom: 0;
    }
  }

  &__sectionNav {
    position: sticky;
    top: var(--gap-8);
    display: flex;
    flex-direction: column;
    width: auto;
    max-width: 20rem;
    height: auto;
    margin-top: var(--gap-8);
    background-color: var(--color-neutral-3);
    border: var(--border-width-thin) solid var(--color-neutral-5);
  }

  &__sectionNavHeader {
    @include tui-font-body();
    margin-top: var(--gap-2);
    margin-bottom: var(--gap-2);
    padding-left: var(--gap-2);
    font-weight: bold;
  }

  &__sectionNavItem {
    @include tui-font-link();
    padding: var(--gap-2);
    color: var(--link-color);

    &--active {
      @include tui-font-link();
      padding: var(--gap-2);
      color: var(--color-neutral-1);
      background-color: var(--color-state-active);

      &:hover,
      &:focus,
      &:active,
      &:active:hover,
      &:active:focus {
        color: var(--color-neutral-1);
      }
    }
  }

  &__schemaForm {
    padding-left: var(--gap-5);
  }

  &__sectionTitle {
    margin-top: 0;
    margin-bottom: var(--gap-6);
    padding-top: var(--gap-8);
  }

  &__section {
    margin-top: var(--gap-12);
  }
}
</style>
