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
  @author Matthias Bonk <matthias.bonk@totaralearning.com>
  @module mod_perform
-->

<template>
  <Layout :loading="isLoading" :title="title" class="tui-performSectionContent">
    <template v-slot:content-nav>
      <PageBackLink :link="goBackLink.url" :text="goBackLink.text" />
    </template>

    <template v-slot:content>
      <div aria-hidden="true" class="tui-performSectionContent__title-required">
        <span class="tui-performSectionContent__title-requiredStar">
          *
        </span>
        {{ $str('required_fields', 'mod_perform') }}
      </div>

      <div v-if="isDraft" class="tui-performSectionContent__form">
        <Droppable
          v-slot="{
            attrs,
            events,
            dropTarget,
            placeholder,
          }"
          :source-id="$id('element-list')"
          source-name="Element List"
          :accept-drop="validateDropElement"
          :reorder-only="true"
          @drop="handleDropElement"
        >
          <div
            class="tui-performSectionContent__dragList"
            v-bind="attrs"
            v-on="events"
          >
            <render :vnode="dropTarget" />
            <Draggable
              v-for="(sectionElement, index) in sectionElements"
              :key="sectionElement.id"
              v-slot="{ dragging, attrs, events, moveMenu, anyDragging }"
              :index="index"
              :value="sectionElement.id"
              :aria-label="
                $str(
                  'move_element',
                  'mod_perform',
                  sectionElement.element.title
                )
              "
              type="element"
              :disabled="!validDragElement(sectionElement)"
            >
              <div
                class="tui-performSectionContent__draggableItem"
                v-bind="attrs"
                v-on="events"
              >
                <render :vnode="moveMenu" />

                <PerformAdminCustomElement
                  v-if="elementPlugins.length"
                  :activity-state="activityState"
                  :is-multi-section-active="hasOtherSections"
                  :draggable="
                    (!anyDragging || dragging) &&
                      validDragElement(sectionElement) &&
                      !$apollo.loading
                  "
                  :dragging="dragging"
                  :element-plugins="childElementPlugins"
                  :section-component="getSectionComponent(sectionElement)"
                  :section-element="sectionElement"
                  :section-id="sectionId"
                  :section="section"
                  :activity-id="activityId"
                  :activity-context-id="activityContextId"
                  :extra-plugin-config-data="
                    getExtraPluginConfigData(sectionElement)
                  "
                  @child-update="childUpdate"
                  @update="update(sectionElement, $event, index)"
                  @edit="edit(sectionElement)"
                  @display="display(sectionElement)"
                  @display-read="displayReadOnly(sectionElement)"
                  @remove="tryDelete(sectionElement, index)"
                  @move="showMoveModal(sectionElement, index)"
                  @unsaved-child="setUnsavedChildElements($event)"
                />
              </div>
            </Draggable>
            <render :vnode="placeholder" />
          </div>
        </Droppable>
        <ContentAddElementButton
          class="tui-performSectionContent__add"
          :element-plugins="topLevelElementPlugins"
          @add-element-item="add"
        />
      </div>

      <div v-else class="tui-performSectionContent__activeForm">
        <template v-for="(sectionElement, index) in sectionElements">
          <PerformAdminCustomElement
            v-if="elementPlugins.length"
            :key="sectionElement.id"
            :activity-state="activityState"
            :activity-id="activityId"
            :is-multi-section-active="hasOtherSections"
            :section-component="getSectionComponent(sectionElement)"
            :section-element="sectionElement"
            :section-id="sectionId"
            :element-plugins="elementPlugins"
            :extra-plugin-config-data="getExtraPluginConfigData(sectionElement)"
            @update="update(sectionElement, $event, index)"
            @edit="edit(sectionElement)"
            @display="display(sectionElement)"
            @display-read="displayReadOnly(sectionElement)"
            @remove="tryDelete(sectionElement, index)"
          />
        </template>
      </div>
    </template>

    <template v-slot:modals>
      <ConfirmationModal
        :open="deleteModalOpen"
        :title="$str('modal_element_delete_title', 'mod_perform')"
        :confirm-button-text="$str('delete', 'core')"
        :loading="isSaving"
        @confirm="deleteSelectedElement"
        @cancel="closeDeleteModal"
      >
        <p>{{ $str('modal_element_delete_message', 'mod_perform') }}</p>
      </ConfirmationModal>

      <ElementDeletionModal
        :title="modalTitle"
        :description="modalDescription"
        :activity-sections="modalData"
        :open="canNotDeleteModalOpen"
        @close="closeCanNotDeleteModal"
      />

      <ModalPresenter :open="moveModalOpen" @request-close="closeMoveModal">
        <Modal size="normal" :aria-labelledby="moveModalId">
          <ModalContent
            :close-button="false"
            :title="$str('modal_element_move_title', 'mod_perform')"
            :title-id="moveModalId"
            @dismiss="closeMoveModal"
          >
            <p>
              {{ $str('modal_element_move_message', 'mod_perform') }}
            </p>
            <Loader :loading="$apollo.loading">
              <Uniform>
                <FormRow
                  :label="$str('modal_element_move_from', 'mod_perform')"
                >
                  {{ title }}
                </FormRow>
                <FormRow :label="$str('modal_element_move_to', 'mod_perform')">
                  <FormSelect
                    v-model="moveToSectionId"
                    :name="$str('modal_element_move_to', 'mod_perform')"
                    :options="otherSections"
                  />
                </FormRow>
                <FormRow>
                  <ButtonGroup>
                    <Button
                      :styleclass="{ primary: 'true' }"
                      :disabled="isSaving || !moveToSectionId"
                      :text="$str('move', 'mod_perform')"
                      @click="moveSelectedElement"
                    />
                    <ButtonCancel
                      :disabled="isSaving"
                      @click="closeMoveModal"
                    />
                  </ButtonGroup>
                </FormRow>
              </Uniform>
            </Loader>
          </ModalContent>
        </Modal>
      </ModalPresenter>
    </template>
  </Layout>
</template>

<script>
import { Uniform, FormRow, FormSelect } from 'tui/components/uniform';
import Button from 'tui/components/buttons/Button';
import ButtonCancel from 'tui/components/buttons/Cancel';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import ContentAddElementButton from 'mod_perform/components/manage_activity/content/ContentAddElementButton';
import Draggable from 'tui/components/drag_drop/Draggable';
import Droppable from 'tui/components/drag_drop/Droppable';
import Layout from 'tui/components/layouts/LayoutOneColumn';
import Loader from 'tui/components/loading/Loader';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import PageBackLink from 'tui/components/layouts/PageBackLink';
import PerformAdminCustomElement from 'mod_perform/components/element/PerformAdminCustomElement';
import ElementDeletionModal from 'mod_perform/components/manage_activity/content/DeletionValidationModal';

import moveElementToSectionMutation from 'mod_perform/graphql/move_element_to_section';
import performElementPluginsQuery from 'mod_perform/graphql/element_plugins';
import sectionDetailQuery from 'mod_perform/graphql/section_admin';
import sectionsQuery from 'mod_perform/graphql/sections';
import createElementInSectionMutation from 'mod_perform/graphql/create_element_in_section';
import deleteSectionElementMutation from 'mod_perform/graphql/delete_section_element';
import reorderSectionElementMutation from 'mod_perform/graphql/reorder_section_element';
import updateElementInSectionMutation from 'mod_perform/graphql/update_element_in_section';
import elementDeletionValidationQuery from 'mod_perform/graphql/element_deletion_validation';

import { ACTIVITY_STATUS_DRAFT } from 'mod_perform/constants';
import { notify } from 'tui/notifications';
import { pull, uniqueId } from 'tui/util';
import Vue from 'vue';

export default {
  components: {
    Button,
    ButtonCancel,
    ButtonGroup,
    ConfirmationModal,
    ContentAddElementButton,
    Draggable,
    Droppable,
    FormRow,
    FormSelect,
    Layout,
    Loader,
    Modal,
    ModalContent,
    ModalPresenter,
    PageBackLink,
    PerformAdminCustomElement,
    ElementDeletionModal,
    Uniform,
  },

  props: {
    activityState: {
      type: Object,
      required: true,
    },
    activityId: {
      type: Number,
      required: true,
    },
    sectionId: {
      type: Number,
      required: true,
    },
    title: {
      type: String,
      required: true,
    },
    isMultiSectionActive: {
      required: true,
      type: Boolean,
    },
    goBackLink: {
      type: Object,
      required: true,
    },
    activityContextId: {
      type: Number,
      required: true,
    },
  },

  data() {
    return {
      elementPlugins: [],
      section: {
        title: '',
        section_elements: [],
      },
      sectionElements: [],
      sections: [],
      editingIds: [],
      readOnlyIds: [],
      removeIds: [],
      errors: {},
      isSaving: false,
      deleteModalOpen: false,
      moveModalOpen: false,
      elementToDelete: null,
      elementToMove: null,
      type: null,
      moveToSectionId: null,
      canNotDeleteModalOpen: false,
      modalTitle: null,
      modalDescription: null,
      modalData: [],
      unsavedChildElements: {},
    };
  },

  apollo: {
    section: {
      query: sectionDetailQuery,
      variables() {
        return { section_id: this.sectionId };
      },
      update: data => data.mod_perform_section_admin,
      fetchPolicy: 'network-only',
      result({ data }) {
        this.updateSectionElementData(
          data.mod_perform_section_admin.section_elements
        );
      },
    },
    elementPlugins: {
      query: performElementPluginsQuery,
      variables() {
        return [];
      },
      update: data => data.mod_perform_element_plugins,
    },
    sections: {
      query: sectionsQuery,
      variables() {
        return { activity_id: this.activityId };
      },
      update: data => data.mod_perform_activity.sections,
      skip() {
        // We don't need to check how many sections there are if we already know muli section is disabled
        return !this.isMultiSectionActive;
      },
    },
  },

  computed: {
    /**
     * Are we currently mutating or querying data via graphQL?
     *
     * @return {Boolean}
     */
    isLoading() {
      return this.$apollo.loading || this.isSaving;
    },

    /**
     * Returns element plugins that can be added on top level.
     *
     * @return {Array}
     */
    topLevelElementPlugins() {
      return this.elementPlugins.filter(elementPlugin => {
        return elementPlugin.element_usage.can_be_top_level_element;
      });
    },

    /**
     * Returns element plugins that can be added as child elements.
     *
     * @return {Array}
     */
    childElementPlugins() {
      return this.elementPlugins.filter(elementPlugin => {
        return elementPlugin.element_usage.can_be_child_element;
      });
    },

    /**
     * Are there any elements still being edited?
     *
     * @return {Boolean}
     */
    hasUnsavedChanges() {
      return this.editingIds.length > 0;
    },

    /**
     * Check if any elements have unsaved child elements
     *
     * @return {Boolean}
     */
    hasUnsavedChildElements() {
      let hasUnsavedChanges = false;
      Object.entries(this.unsavedChildElements).forEach(([key]) => {
        if (this.unsavedChildElements[key] === true) {
          hasUnsavedChanges = true;
        }
      });
      return hasUnsavedChanges;
    },

    /**
     * Is the activity a draft, and thus can be modified?
     *
     * @return {Boolean}
     */
    isDraft() {
      return this.activityState.name === ACTIVITY_STATUS_DRAFT;
    },

    /**
     * Get info for all sections in the activity except the current one.
     */
    otherSections() {
      return this.sections
        .map(section => ({
          id: section.id,
          label: section.display_title,
        }))
        .filter(section => section.id != this.sectionId);
    },

    moveModalId() {
      return this.$id('move-modal');
    },

    /**
     * Is multi section active, and are there other sections?
     *
     * @return {Boolean}
     */
    hasOtherSections() {
      return this.isMultiSectionActive && this.otherSections.length > 0;
    },
  },

  mounted() {
    // Confirm navigation away if user is currently editing.
    window.addEventListener('beforeunload', this.unloadHandler);
  },

  beforeDestroy() {
    // Modal will no longer exist so remove the navigation warning.
    window.removeEventListener('beforeunload', this.unloadHandler);
  },

  methods: {
    /**
     * Add new plugin element.
     */
    add(plugin) {
      const sectionElement = {
        id: 'unsaved-' + uniqueId(),
        element: {
          id: null,
          type: plugin,
          title: '',
          raw_title: '',
          identifier: '',
          data: {},
          raw_data: {},
        },
        sort_order: this.sectionElements.length + 1,
        creating: true,
      };

      this.sectionElements.push(sectionElement);
      this.edit(sectionElement);
    },

    /**
     * Child element update
     *
     * @param {String} successMessage
     */
    async childUpdate(successMessage) {
      await this.$apollo.queries.section.refetch();
      if (successMessage) {
        this.showSuccessNotification(successMessage);
      } else {
        this.showErrorNotification();
      }
    },

    /**
     * Check if element has unsaved child elements
     *
     * @param {Object} element
     * @return {Boolean}
     */
    elementHasUnsavedChild(element) {
      let id = element.id;
      return this.unsavedChildElements[id];
    },

    /**
     * Track if elements have unsaved child elements
     *
     * @param {Object} data
     */
    setUnsavedChildElements(data) {
      Vue.set(this.unsavedChildElements, data.key, data.hasChanges);
    },

    /**
     * Update existing elements and shows display view of the element.
     */
    update(sectionElement, elementData, index) {
      sectionElement.element = Object.assign(
        sectionElement.element,
        elementData,
        {
          raw_title: elementData.title,
          raw_data: elementData.data,
          parent: elementData.parent,
        }
      );

      const elementToSave = {
        title: sectionElement.element.raw_title,
        data: JSON.stringify(sectionElement.element.raw_data),
      };

      // 'identifier' and 'is_required' attributes don't exist for static elements so need to handle them.
      if (sectionElement.element.identifier != null) {
        elementToSave.identifier = sectionElement.element.identifier;
      }
      if (sectionElement.element.is_required != null) {
        elementToSave.is_required = sectionElement.element.is_required;
      }

      let variables = {};
      let mutation;

      if (sectionElement.creating) {
        delete sectionElement.creating;
        mutation = createElementInSectionMutation;
        variables = {
          section_id: this.sectionId,
          after_section_element_id: this.getSectionElementIdBeforeIndex(index),
          element: {
            plugin_name: sectionElement.element.type.plugin_name,
            element_details: elementToSave,
          },
        };
      } else {
        mutation = updateElementInSectionMutation;
        variables = {
          element_details: elementToSave,
          section_element_id: sectionElement.id,
        };
      }
      this.save(
        variables,
        this.$str('toast_success_save_element', 'mod_perform'),
        mutation
      );

      this.display(sectionElement);
    },

    /**
     * Has the specified element not been saved into the DB?
     */
    elementDoesNotExist(element) {
      return 'creating' in element;
    },

    /**
     * Has the specified element been saved into the DB?
     */
    elementExists(element) {
      return !this.elementDoesNotExist(element);
    },

    /**
     * Calculate the sort order value for a section element.
     *
     * @param {Number} index
     * @deprecated in favor of getSectionElementBefore
     */
    getSortOrder(index) {
      const savedSectionElementsBefore = this.sectionElements
        .slice(0, index)
        .filter(this.elementExists);

      return savedSectionElementsBefore.length + 1;
    },

    /**
     * Returns the section_element_id before the specified index.
     *
     * @param {Number} index
     */
    getSectionElementIdBeforeIndex(index) {
      const savedSectionElementsBefore = this.sectionElements
        .slice(0, index)
        .filter(this.elementExists);
      if (savedSectionElementsBefore.length === 0) {
        return null;
      }

      let previousSavedSectionElement = savedSectionElementsBefore
        .reverse()
        .find(this.elementExists);

      return previousSavedSectionElement
        ? previousSavedSectionElement.id
        : null;
    },

    /**
     * Add element into edit list
     */
    edit(sectionElement) {
      this.editingIds.push(sectionElement.id);
    },

    /**
     * Remove element from the edit list.
     */
    stopEditing(sectionElement) {
      pull(this.editingIds, sectionElement.id);
    },

    /**
     * Is the element currently being edited?
     */
    isEditing(element) {
      return this.editingIds.includes(element.id);
    },

    /**
     * Display section element
     * Remove creating view if section element move to display mode
     */
    display(sectionElement) {
      if (this.isDraft) {
        this.stopEditing(sectionElement);
        if (sectionElement.creating) {
          this.remove(sectionElement);
        }
      } else {
        pull(this.readOnlyIds, sectionElement.id);
      }
    },

    /**
     * Display Read only section element when activity in active mode
     * @param {Object} sectionElement
     *
     */
    displayReadOnly(sectionElement) {
      this.readOnlyIds.push(sectionElement.id);
    },

    /**
     * Is the element in a read-only state?
     */
    isReadOnly(element) {
      return this.readOnlyIds.includes(element.id);
    },

    /**
     * Remove section element
     * if section element already saved update remove list
     */
    remove(sectionElement) {
      this.stopEditing(sectionElement);
      pull(this.sectionElements, sectionElement);
    },

    /**
     * Try delete the section element.
     * If it exists in the DB, show a confirmation before deleting.
     * @param {Object} sectionElement
     * @param {Number} index
     */
    async tryDelete(sectionElement, index) {
      if (sectionElement.element.id) {
        const {
          data: { validation_info: result },
        } = await this.$apollo.query({
          query: elementDeletionValidationQuery,
          variables: {
            input: { section_element_id: sectionElement.id },
          },
          fetchPolicy: 'no-cache',
        });

        if (result.can_delete) {
          this.deleteModalOpen = true;
          this.elementToDelete = sectionElement;
          this.elementToDelete.index = index;
        } else {
          this.modalTitle = result.title;
          this.modalDescription = result.reason.description;
          this.modalData = result.reason.data;
          this.showCanNotDeleteModal();
        }
      } else {
        this.remove(sectionElement);
      }
    },

    /**
     * Display the modal for moving an element to another section.
     * @param {Object} sectionElement
     * @param {Number} index
     */
    showMoveModal(sectionElement, index) {
      this.moveModalOpen = true;
      this.elementToMove = sectionElement;
      this.elementToMove.index = index;
    },

    /**
     * Validate current settings and call mutation to move element.
     */
    moveSelectedElement() {
      if (!this.moveToSectionId || !this.elementToMove) {
        return;
      }
      if (
        this.otherSections.filter(
          section => section.id === this.moveToSectionId
        ).length !== 1
      ) {
        return;
      }

      // Move element.
      this.doMoveSelectedElement();
    },

    /**
     * Trigger mutation to move the element to another section.
     */
    async doMoveSelectedElement() {
      this.isSaving = true;

      try {
        const { data: result } = await this.$apollo.mutate({
          mutation: moveElementToSectionMutation,
          variables: {
            input: {
              element_id: this.elementToMove.element.id,
              source_section_id: this.sectionId,
              target_section_id: this.moveToSectionId,
            },
          },
          refetchAll: false,
        });
        this.updateSectionElementData(
          result.mod_perform_move_element_to_section.source_section_elements
        );
        this.showSuccessNotification(
          this.$str('toast_success_move_element', 'mod_perform')
        );
        this.remove(this.elementToMove);
      } catch (e) {
        this.showErrorNotification();
      }
      this.closeMoveModal();
      this.isSaving = false;
    },

    /**
     * Trigger mutation to delete the element from the DB.
     */
    async deleteSelectedElement() {
      this.isSaving = true;

      await this.save(
        {
          section_element_id: this.elementToDelete.id,
        },
        this.$str('toast_success_delete_element', 'mod_perform'),
        deleteSectionElementMutation
      );

      this.remove(this.elementToDelete);
      this.closeDeleteModal();
      this.isSaving = false;
    },

    /**
     * Reorder elements
     */
    async reorderElements(sectionElements) {
      this.isSaving = true;
      const toSave = {};
      toSave.move = sectionElements
        .filter(this.elementExists)
        .map((element, index) => {
          return {
            section_element_id: element.id,
            sort_order: index + 1,
          };
        });

      await this.save(toSave, null, reorderSectionElementMutation);

      this.isSaving = false;
    },

    /**
     * Close element deletion confirmation modal.
     */
    closeDeleteModal() {
      this.deleteModalOpen = false;
      this.elementToDelete = null;
    },

    /**
     * Close modal for moving elements.
     */
    closeMoveModal() {
      this.moveModalOpen = false;
      this.moveToSectionId = null;
    },

    /**
     * Get the component type (view/editing/readonly), component and its settings for the current section
     *
     * @param {Object} sectionElement Current section content
     * @return {Object}
     */
    getSectionComponent(sectionElement) {
      let subComponent = {
        component: '',
        settings: '',
        type: '',
      };

      const { type } = sectionElement.element;
      const elementPlugin = this.elementPlugins
        .filter(item => item.plugin_name == type.plugin_name)
        .shift();

      subComponent.settings = elementPlugin.plugin_config;

      if (this.isReadOnly(sectionElement)) {
        subComponent.type = 'readOnly';
        subComponent.component = tui.asyncComponent(
          elementPlugin.admin_summary_component
        );
      } else if (this.isEditing(sectionElement)) {
        subComponent.type = 'editing';
        subComponent.component = tui.asyncComponent(
          elementPlugin.admin_edit_component
        );
      } else {
        subComponent.type = 'view';
        subComponent.component = tui.asyncComponent(
          elementPlugin.admin_view_component
        );
      }

      return subComponent;
    },

    /**
     * @param {Object} sectionElement
     * @return {Object|Array}
     */
    getExtraPluginConfigData(sectionElement) {
      const elementPlugin = this.elementPlugins.find(
        plugin => plugin.plugin_name === sectionElement.element.type.plugin_name
      );

      return JSON.parse(elementPlugin.plugin_config.extra_config_data);
    },

    /**
     * Try to persist the activity elements to the back end.
     * Shows toasts on success/failure.
     *
     * @param {Object} variables
     * @param {String} [saveNotificationMessage] Override text that is shown in the success notification.
     * @param mutation
     */
    async save(variables, saveNotificationMessage, mutation) {
      this.isSaving = true;

      try {
        const { data: result } = await this.$apollo.mutate({
          mutation,
          variables: {
            input: variables,
          },
          refetchAll: false,
        });
        const section = result.updated_section_details.section;
        this.updateSectionElementData(section.section_elements);
        if (saveNotificationMessage) {
          this.showSuccessNotification(saveNotificationMessage);
        }
        this.isSaving = false;
      } catch (e) {
        this.showErrorNotification();
        // If something goes wrong during create, allow the user to try again.
        this.isSaving = false;
      }
    },

    /**
     * Displays a warning message if the user tries to navigate away without saving.
     * @param {Event} e
     * @returns {String|void}
     */
    unloadHandler(e) {
      if (!this.hasUnsavedChanges && !this.hasUnsavedChildElements) {
        return;
      }

      // For older browsers that still show custom message.
      const discardUnsavedChanges = this.$str(
        'unsaved_changes_warning',
        'mod_perform'
      );
      e.preventDefault();
      e.returnValue = discardUnsavedChanges;
      return discardUnsavedChanges;
    },

    /**
     * Process and apply the section element data from gql for use within vue.
     *
     * @param {Array} data GraphQL data
     */
    updateSectionElementData(data) {
      const elements = data.map(item => {
        return Object.assign({}, item, {
          element: Object.assign({}, item.element, {
            type: item.element.element_plugin,
            data: item.element.data ? JSON.parse(item.element.data) : {},
            raw_data: item.element.raw_data
              ? JSON.parse(item.element.raw_data)
              : {},
            children: item.element.children,
          }),
        });
      });

      const unsavedElements = this.sectionElements.filter(
        this.elementDoesNotExist
      );

      this.sectionElements = elements.concat(unsavedElements).sort((a, b) => {
        const sortDiff = a.sort_order - b.sort_order;
        if (sortDiff !== 0) {
          return sortDiff;
        } else {
          // There can be duplicate sort orders because the back end must always have sequential orders,
          // but in the front end there could be an unsaved element that we want to place between two
          // saved elements. We should show the unsaved element before the saved one in this case.
          return this.elementDoesNotExist(a) ? -1 : 1;
        }
      });
    },

    /**
     * Show a generic saving success toast.
     *
     * @param {String} messageString Override the message text.
     */
    showSuccessNotification(messageString) {
      notify({
        message:
          messageString ||
          this.$str('toast_success_save_element', 'mod_perform'),
        type: 'success',
      });
    },

    /**
     * Show a generic saving error toast.
     */
    showErrorNotification() {
      notify({
        message: this.$str('toast_error_generic_update', 'mod_perform'),
        type: 'error',
      });
    },

    /**
     * check whether a drag is allowed.
     *
     * @param {Array} sectionElement
     */
    validDragElement(sectionElement) {
      return (
        !this.isEditing(sectionElement) &&
        this.sectionElements.length > 1 &&
        !this.elementHasUnsavedChild(sectionElement)
      );
    },

    /**
     * check whether a drop is allowed.
     *
     * @param {DropInfo} info
     */
    validateDropElement(info) {
      return info.destination.sourceId == info.source.sourceId;
    },

    /**
     * Called when element is dropped on a list.
     *
     * @param {DropInfo} info
     */
    handleDropElement(info) {
      if (info.destination.index === info.source.index) {
        return;
      }
      let newIndex =
        info.source.index < info.destination.index
          ? info.destination.index + 1
          : info.destination.index;
      let afterSectionElementId = this.getSectionElementIdBeforeIndex(newIndex);

      if (afterSectionElementId === info.item.value) {
        return;
      }

      let variables = {
        section_element_id: info.item.value,
        move_to_after_section_element_id: afterSectionElementId,
      };
      this.save(
        variables,
        this.$str('toast_success_move_element', 'mod_perform'),
        reorderSectionElementMutation
      );
    },

    /**
     * Check element editable
     */
    isElementEditable() {
      return this.activityState.name !== 'ACTIVE';
    },

    /**
     * Replace null with empty string before passing it on to the element components because they shouldn't
     * bother about handling null value.
     *
     * @param {String} identifier
     */
    normaliseIdentifierForElements(identifier) {
      return identifier === null ? '' : identifier;
    },

    /**
     * Show can not delete modal
     */
    showCanNotDeleteModal() {
      this.canNotDeleteModalOpen = true;
    },

    /**
     * Hide can not delete modal
     */
    closeCanNotDeleteModal() {
      this.canNotDeleteModalOpen = false;
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "delete"
  ],
  "mod_perform": [
    "modal_element_delete_message",
    "modal_element_delete_title",
    "modal_element_move_from",
    "modal_element_move_message",
    "modal_element_move_title",
    "modal_element_move_to",
    "move",
    "move_element",
    "required_fields",
    "toast_error_generic_update",
    "toast_success_delete_element",
    "toast_success_move_element",
    "toast_success_save_element",
    "unsaved_changes_warning"
  ]
}
</lang-strings>

<style lang="scss">
.tui-performSectionContent {
  &__title {
    &-required {
      @include tui-font-body;
    }

    &-requiredStar {
      color: var(--color-prompt-alert);
    }
  }

  &__form {
    min-height: 2rem;
    & > * + * {
      margin-top: var(--gap-4);
    }
  }

  &__activeForm {
    min-height: 2rem;
    padding-top: var(--gap-2);
    & > * + * {
      margin-top: var(--gap-4);
    }
  }

  &__dragList {
    & > * + * {
      margin-top: var(--gap-2);
    }
  }

  &__draggableItem {
    position: relative;
  }

  &__add {
    margin-top: var(--gap-8);
  }
}
</style>
