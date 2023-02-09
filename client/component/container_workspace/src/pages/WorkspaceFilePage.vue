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

  @author Qingyang Liu <qingyang liu@totaralearning.com>
  @module container_workspace
-->
<template>
  <LayoutTwoColumn class="tui-workspaceFilePage">
    <template v-slot:left="{ direction }">
      <SidePanel :show-button-control="false" :initially-open="true">
        <WorkspaceMenu
          v-if="direction === 'horizontal'"
          :selected-workspace-id="workspaceId"
          @create-workspace="navigateToWorkspace"
        />

        <WorkspaceControlMenu
          v-else
          :workspace-id="workspaceId"
          :workspace-name="workspaceName"
          :show-navigation="true"
        />
      </SidePanel>
    </template>
    <template v-slot:right>
      <div v-if="!$apollo.loading" class="tui-workspaceFilePage__content">
        <WorkspaceFileHeader @go-back="goBack" />
        <WorkspaceFileFilter
          :selected-sort="inner.selectedSort"
          :selected-extension="inner.selectedExtension"
          :workspace-id="workspaceId"
          :show-sort="totalFiles > 0"
          @update-filter="updateFilter"
        />

        <p
          v-if="!$apollo.loading && totalFiles === 0"
          class="tui-workspaceFilePage__message"
        >
          {{ $str('no_file_found', 'container_workspace') }}
        </p>

        <WorkspaceFileTable
          v-else
          :workspace-id="workspaceId"
          :selected-sort="inner.selectedSort"
          :selected-extension="inner.selectedExtension"
          @open="handleOpen"
        />

        <ModalPresenter :open="openModal" @request-close="openModal = false">
          <WorkspaceFileViewModal
            :file-name="innerFile.fileName"
            :download-url="innerFile.downloadUrl"
            :context-url="innerFile.contextUrl"
            :mime-type="innerFile.mimeType"
            :extension="innerFile.extension"
            :file-type="innerFile.fileType"
            :alt-text="innerFile.altText"
            :file-url="innerFile.fileUrl"
          />
        </ModalPresenter>
      </div>
    </template>
  </LayoutTwoColumn>
</template>

<script>
import LayoutTwoColumn from 'tui/components/layouts/LayoutTwoColumn';
import WorkspaceMenu from 'container_workspace/components/sidepanel/WorkspaceMenu';
import SidePanel from 'tui/components/sidepanel/SidePanel';
import WorkspaceFileHeader from 'container_workspace/components/head/WorkspaceFileHeader';
import WorkspaceFileFilter from 'container_workspace/components/filter/WorkspaceFileFilter';
import WorkspaceFileTable from 'container_workspace/components/table/WorkspaceFileTable';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import WorkspaceFileViewModal from 'container_workspace/components/modal/WorkspaceFileViewModal';
import WorkspaceControlMenu from 'container_workspace/components/sidepanel/WorkspaceControlMenu';

// GraphQL queries
import getFiles from 'container_workspace/graphql/get_files';

export default {
  components: {
    SidePanel,
    LayoutTwoColumn,
    WorkspaceMenu,
    WorkspaceFileHeader,
    WorkspaceFileFilter,
    WorkspaceFileTable,
    ModalPresenter,
    WorkspaceFileViewModal,
    WorkspaceControlMenu,
  },

  props: {
    workspaceId: {
      type: [Number, String],
      required: true,
    },

    selectedSort: {
      type: String,
      required: true,
    },

    selectedExtension: {
      type: String,
      default: '',
    },

    workspaceName: {
      type: String,
      required: true,
    },
  },

  apollo: {
    totalFiles: {
      query: getFiles,
      variables() {
        return {
          workspace_id: this.workspaceId,
          extension: this.inner.selectedExtension,
          sort: this.inner.selectedSort,
        };
      },

      update({ cursor }) {
        return parseInt(cursor.total, 10);
      },
    },
  },

  data() {
    return {
      totalFiles: 0,
      inner: {
        selectedSort: this.selectedSort,
        selectedExtension: this.selectedExtension,
      },
      openModal: false,
      innerFile: {
        fileName: null,
        extension: null,
        downloadUrl: null,
        contextUrl: null,
        fileUrl: null,
        mimeType: null,
        fileType: null,
        altText: null,
      },
    };
  },

  methods: {
    /**
     *
     * @param {String}  source
     * @param {String}  sort
     */
    updateFilter({ extension, sort }) {
      this.inner.selectedExtension = extension;
      this.inner.selectedSort = sort;

      // Reset our current page.
      this.currentPage = 1;
    },

    /**
     *
     * @param {Number} id
     */
    navigateToWorkspace({ id }) {
      document.location.href = this.$url(
        '/container/type/workspace/workspace.php',
        { id }
      );
    },

    goBack() {
      document.location.href = this.$url(
        '/container/type/workspace/workspace.php?',
        { id: this.workspaceId }
      );
    },

    handleOpen({
      file_name,
      extension,
      context_url,
      download_url,
      file_url,
      mimetype,
      file_type,
      alt_text,
    }) {
      this.innerFile.fileName = file_name;
      this.innerFile.extension = extension;
      this.innerFile.contextUrl = context_url;
      this.innerFile.downloadUrl = download_url;
      this.innerFile.mimeType = mimetype;
      this.innerFile.fileType = file_type;
      this.innerFile.altText = alt_text || this.$str('imagealt', 'core');
      this.innerFile.fileUrl = file_url;

      this.openModal = true;
    },
  },
};
</script>
<lang-strings>
  {
    "core": [
      "imagealt"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-workspaceFilePage {
  display: flex;
  flex-direction: column;

  // Overriding the css here.
  .tui-responsive {
    flex: 1;
    width: 100%;
    height: 100%;

    .tui-grid--vertical {
      display: flex;
      flex-direction: column-reverse;

      .tui-grid-item {
        border-top: 0;
      }
    }

    .tui-layoutTwoColumn__heading {
      margin: 0;
    }
  }

  &__content {
    @media (min-width: $tui-screen-sm) {
      padding: var(--gap-8);
    }
  }

  &__message {
    @include tui-font-body();
    margin: var(--gap-8) 0;
  }
}
</style>
