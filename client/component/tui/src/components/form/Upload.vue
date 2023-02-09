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

  @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
  @module tui
-->

<script>
import deleteDraftFile from 'totara_core/graphql/delete_draft_file';
import { config } from 'tui/config';

export default {
  props: {
    href: {
      type: String,
      required: true,
    },
    itemId: {
      type: Number,
      required: true,
    },
    repositoryId: {
      type: Number,
      required: true,
    },
    contextId: [Number, String],
    acceptedTypes: Array,

    /**
     * Prop to tell our server whether to overwrite the draft file or not.
     */
    overwrite: Boolean,

    /**
     * Prop to tell this component fire an event to the server to delete the old draft files
     * before the deletion or not.
     */
    oneFile: Boolean,
  },

  data() {
    return {
      files: [],
      isDrag: false,
    };
  },

  computed: {
    isReady() {
      return this.files.filter(e => e.done == false).length == 0;
    },
  },

  methods: {
    async upload(file) {
      if (this.oneFile) {
        // Since we are only accepting one file, therefore we are going to delete all the other draft files before
        // uploading new draft files to the system.
        await this.$_deleteAllDraftFiles();
      }

      file.progress = 0;
      file.done = false;
      const data = new FormData();
      const request = new XMLHttpRequest();

      request.upload.addEventListener(
        'progress',
        e => {
          if (e.lengthComputable) {
            file.progress = (e.loaded * 100) / e.total;
            this.$emit('progress', {
              file: file,
              loaded: e.loaded,
              total: e.total,
            });
          }
        },
        false
      );

      request.addEventListener('readystatechange', () => {
        if (request.readyState == 4) {
          file.done = true;
          if (request.status == 200) {
            let result = {};
            try {
              result = JSON.parse(request.responseText);
            } catch (error) {
              this.$emit('error', {
                error: error,
                file: file,
              });
            }
            if (result) {
              if (result.error) {
                this.$emit('error', {
                  file: file,
                  error: result.error,
                });
              } else {
                if (result.url) {
                  file.url = result.url;
                } else if (result.newfile && result.newfile.url) {
                  file.url = result.newfile.url;
                }

                this.$emit('load', {
                  file: file,
                });

                const index = this.files.push(file) - 1;
                this.$set(this.files, index, file);
              }
            }
          } else {
            this.$emit('error', {
              file: file,
            });
          }
          if (this.isReady) {
            this.$emit('upload-finished', {
              file: file,
            });
          }
        }
      });

      data.append('repo_upload_file', file);
      data.append('title', file.name);
      data.append('sesskey', config.sesskey);
      data.append('repo_id', this.repositoryId);
      data.append('itemid', this.itemId);
      if (this.overwrite) {
        data.append('overwrite', 1);
      }
      if (this.contextId > 0) {
        data.append('ctx_id', this.contextId);
      }
      if (this.acceptedTypes) {
        this.acceptedTypes.forEach(function(type) {
          data.append('accepted_types[]', type);
        });
      }

      request.open('POST', this.href, true);
      request.send(data);
      this.$emit('upload-started', {
        file: file,
      });
    },

    async deleteDraft(file) {
      await this.$apollo.mutate({
        mutation: deleteDraftFile,
        variables: {
          draftid: this.itemId,
          filename: file.name,
        },
      });

      this.files = this.files.filter(e => e.name !== file.name);
    },

    /**
     * Deleting all the draft files via graphql.
     *
     * @return {Promise<void>}
     */
    async $_deleteAllDraftFiles() {
      await Promise.all(
        Array.prototype.map.call(this.files, file => this.deleteDraft(file))
      );
    },

    change() {
      let files = this.$_getFileInput().files;
      for (let i = 0; i < files.length; i++) {
        this.upload(files[i]);
      }
    },

    clear() {
      this.files = [];
    },

    $_getFileInput() {
      let input = this.$scopedSlots.default()[0].context.$refs.inputFile;
      if (!input || input.type != 'file') {
        throw new Error(
          "Upload element must have input type=file HTML element with ref='inputFile'."
        );
      }
      return input;
    },
  },

  render() {
    return this.$scopedSlots.default({
      files: this.files,
      isDrag: this.isDrag,
      pickFile: () => {
        this.$_getFileInput().click();
      },
      selectEvents: {
        click: () => {
          this.$_getFileInput().click();
        },
      },
      inputEvents: {
        change: () => {
          this.change();
        },
      },
      deleteDraft: this.deleteDraft,
      dragEvents: {
        dragenter: e => {
          e.preventDefault();
          e.stopPropagation();
          this.isDrag = true;
        },
        dragover: e => {
          e.preventDefault();
          e.stopPropagation();
          this.isDrag = true;
        },
        dragleave: e => {
          e.preventDefault();
          e.stopPropagation();
          this.isDrag = false;
        },
        drop: e => {
          e.preventDefault();
          e.stopPropagation();
          this.isDrag = false;

          let files = e.dataTransfer.files;
          for (let i = 0; i < files.length; i++) {
            this.upload(files[i]);
          }
        },
      },
    });
  },
};
</script>
