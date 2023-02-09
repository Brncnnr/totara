/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * Totara Enterprise Extensions is provided only to Totara
 * Learning Solutions LTD's customers and partners, pursuant to
 * the terms and conditions of a separate agreement with Totara
 * Learning Solutions LTD or its affiliate.
 *
 * If you do not have an agreement with Totara Learning Solutions
 * LTD, you may not access, use, modify, or distribute this software.
 * Please contact [licensing@totaralearning.com] for more information.
 *
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module editor_weka
 */

// eslint-disable-next-line no-unused-vars
import { EditorState, Transaction, Command } from 'ext_prosemirror/state';
import { EditorView } from 'ext_prosemirror/view';
import { keymap } from 'ext_prosemirror/keymap';
import { history } from 'ext_prosemirror/history';
import { baseKeymap } from 'ext_prosemirror/commands';
import { dropCursor } from 'ext_prosemirror/dropcursor';
import { gapCursor } from 'ext_prosemirror/gapcursor';
import { inputRules } from 'ext_prosemirror/inputrules';
// eslint-disable-next-line no-unused-vars
import { DOMParser, Node, Schema } from 'ext_prosemirror/model';
import { buildKeymap } from './keymap';
import { createSchema } from './schema';
import ComponentView from './ComponentView';
import './transaction';
import textPlaceholder from './plugins/text_placeholder';
import WekaValue from './WekaValue';
import { result, uniqueId } from 'tui/util';
import { notify } from 'tui/notifications';
import { langString, loadLangStrings } from 'tui/i18n';
import { traverseNode } from './internal/serialized_doc';
import cursorInsideNode from './plugins/cursor_inside_node';

export default class Editor {
  /**
   * Create a new Editor instance.
   *
   * @param {object} [options]
   * @param {WekaValue} [options.value] WekaValue.
   * @param {*} [options.parent] Parent Vue component.
   */
  constructor(options = {}) {
    this.uid = uniqueId();
    this._options = options;
    this._parent = options.parent;
    this.viewExtrasEl = options.viewExtrasEl || null;
    this.viewExtrasLiveEl = options.viewExtrasLiveEl || null;
    this._editable = 'editable' in options ? options.editable : true;
    this.destroyed = false;

    /** @type {EditorView} */
    this.view = null;
    /** @type {EditorState} */
    this.state = null;

    this._extensions = options.extensions || [];

    /** @type {FileStorage} */
    this.fileStorage = options.fileStorage;
    this._extensions.forEach(ext => ext.setEditor(this));

    const nodes = {};
    this._nodeViews = {};
    const marks = {};
    this._markViews = {};
    this._allVueComponents = [];
    this._extensions.forEach(ext => {
      this._extractExtensionSchema(
        ext,
        'node',
        'nodes',
        nodes,
        this._nodeViews
      );
      this._extractExtensionSchema(
        ext,
        'mark',
        'marks',
        marks,
        this._nodeViews
      );
    });

    this.schema = createSchema({ nodes, marks });

    this._plugins = [];

    this._extensions.forEach(ext => {
      const extPlugins = ext.plugins();
      if (extPlugins) {
        extPlugins.forEach(plugin => this._plugins.push(plugin));
      }
    });

    this._toolbarItemInstances = this._extensions.reduce(
      (acc, ext) => (ext.toolbarItems ? acc.concat(ext.toolbarItems()) : acc),
      []
    );

    this._toolbarItems = this._toolbarItemInstances.map(x => x.getDef());

    this._mapKeys = this._extensions
      .map(x => x.keymap && x.keymap.bind(x))
      .filter(Boolean);

    this._inputRules = this._extensions.reduce(
      (acc, ext) => (ext.inputRules ? acc.concat(ext.inputRules()) : acc),
      []
    );

    this.dispatch = this.dispatch.bind(this);
    this.execute = this.execute.bind(this);
    this.getFileStorageItemId = this.getFileStorageItemId.bind(this);

    this.setValue(options.value || WekaValue.empty());
  }

  /**
   *
   * @param {Number} value
   */
  updateFileItemId(value) {
    this.fileStorage.updateFileItemId(value);
  }

  _extractExtensionSchema(plugin, name, methodName, schema, views) {
    const pluginnodes = plugin[methodName] && plugin[methodName]();
    if (pluginnodes) {
      for (const key in pluginnodes) {
        const node = pluginnodes[key];
        if (schema[key]) {
          console.warn(`[editor_weka] ${name} "${key}" was redefined`);
        }
        schema[key] = node.schema;
        if (node.component) {
          this._allVueComponents.push(node.component);
          views[key] = this._componentNodeView.bind(
            this,
            node.component,
            node.componentContext
          );
        }
        if (node.view) {
          views[key] = node.view;
        }
        // Dev helper: warn if component/view is placed on schema instead of the node
        if (process.env.NODE_ENV !== 'production') {
          const checkProperties = ['component', 'view'];
          checkProperties.forEach(p => {
            if (node.schema && node.schema[p]) {
              console.warn(
                `Property "${p}" defined on node schema for node ${key}, ` +
                  `expected on node definition instead (one level higher).`
              );
            }
          });
        }
      }
    }
  }

  /**
   * Set the editor state to the specified state.
   * @param {WekaValue} value
   */
  setValue(value) {
    if (!value.inflated(this)) {
      if (value.hasHtml()) {
        const element = document.createElement('div');
        element.innerHTML = value.getHtml();
        const state = EditorState.create({
          ...this._editorConfig(),
          doc: DOMParser.fromSchema(this.schema).parse(element),
        });
        value.inflate(this, state);
      } else {
        const state = EditorState.create({
          ...this._editorConfig(),
          doc: this._docFromJson(this.schema, value.getDoc(false)),
        });
        value.inflate(this, state);
      }
    }

    this._value = value;
    this.state = value.getState(this);

    if (this.view) {
      this.view.updateState(this.state);
    }
  }

  /**
   * @param {Schema} schema
   * @param {object} doc
   * @returns {Node}
   */
  _docFromJson(schema, doc) {
    if (!doc) {
      return null;
    }

    // clone doc to avoid mutating
    doc = JSON.parse(JSON.stringify(doc));

    this._execSerializedVisitors(doc, 'load');
    this._rewriteUnknownNodes(schema, doc);

    return Node.fromJSON(schema, doc);
  }

  /**
   * Replace unknown nodes with special "unknown" node types to avoid crashing the editor.
   *
   * @param {Schema} schema
   * @param {object} doc
   */
  _rewriteUnknownNodes(schema, doc) {
    traverseNode(doc, {
      any: node => {
        const { content } = node;
        if (content) {
          for (let i = 0; i < content.length; i++) {
            let child = content[i];
            if (!schema.nodes[child.type]) {
              const parentContentSpec =
                schema.nodes[node.type] && schema.nodes[node.type].spec.content;
              let replaceWithType = null;
              if (
                node.type === 'doc' ||
                /^block[+*]?$/.test(parentContentSpec)
              ) {
                replaceWithType = 'unknown_block';
              } else if (/^inline[+*]?$/.test(parentContentSpec)) {
                replaceWithType = 'unknown_inline';
              }
              if (replaceWithType) {
                // rewrite to unknown
                child.attrs = {
                  type: child.type,
                  attrs: child.attrs,
                  content: child.content,
                };
                child.type = replaceWithType;
                delete child.content;
              } else {
                // can't replace, just remove
                content.splice(i, 1);
                i--;
                child = null;
              }
            }

            if (child) {
              this._rewriteUnknownNodeMarks(schema, child, node);
            }
          }
        }
      },
    });
  }

  /**
   * Replace unknown marks with a special "unknown" mark to avoid crashing the editor.
   *
   * @param {Schema} schema
   * @param {object} node
   * @param {object} parent
   */
  _rewriteUnknownNodeMarks(schema, node, parent) {
    const { marks } = node;
    if (marks) {
      for (let j = 0; j < marks.length; j++) {
        const mark = marks[j];
        if (!schema.marks[mark.type]) {
          // marks are not defined on text nodes, but on the node that contains text nodes
          const nodeType =
            schema.nodes[node.type === 'text' ? parent.type : node.type];
          const markSpec = nodeType && nodeType.spec.marks;
          const allowsAllMarks =
            markSpec === '_' ||
            // if a mark spec is not defined, whether all marks are allowed
            // depends on whether the node allows inline content
            (markSpec == null && nodeType.inlineContent);

          if (allowsAllMarks) {
            mark.attrs = { type: mark.type, attrs: mark.attrs };
            mark.type = 'unknown';
          } else {
            // can't replace, just remove
            marks.splice(j, 1);
            j--;
          }
        }
      }
    }
  }

  /**
   * Get editor config object to pass to EditorState.
   *
   * @private
   * @return object
   */
  _editorConfig() {
    return {
      schema: this.schema,
      plugins: this._createPlugins(),
    };
  }

  /**
   * Set references to UI elements.
   *
   * @param {{ viewExtras, viewExtrasLive }} options
   */
  setElements({ viewExtras, viewExtrasLive }) {
    this.viewExtrasEl = viewExtras;
    this.viewExtrasLiveEl = viewExtrasLive;
  }

  /**
   * Create editor view for the specified node.
   *
   * @param {Node} el DOM node.
   * @return {EditorView}
   */
  createView(el) {
    const opts = this._options;
    let attrs = {
      class: 'tui-weka-editor',
      role: 'textbox',
      'aria-multiline': 'true',
      'aria-labelledby': opts.ariaLabelledby,
      'aria-label': opts.ariaLabel,
      'aria-describedby': opts.ariaDescribedby,
      'aria-invalid': opts.ariaInvalid,
    };

    // if no accessible name was provided, try and find a fallback
    if (!attrs['aria-label'] && !attrs['aria-labelledby']) {
      attrs['aria-label'] =
        opts.placeholder || langString('pluginname', 'editor_weka');
    }

    this.view = new EditorView(el, {
      state: this.state,
      dispatchTransaction: this.dispatch,
      nodeViews: this._nodeViews,
      // filter out null/undefined attrs
      attributes: Object.entries(attrs).reduce((acc, [key, value]) => {
        if (value != null) {
          acc[key] = value;
        }
        return acc;
      }, {}),
      editable: () => result(this._editable),
    });

    this.view.dom.addEventListener('focus', e => {
      if (this._options.onFocus) {
        this._options.onFocus(e);
      }
    });
    this.view.dom.addEventListener('blur', e => {
      if (this._options.onBlur) {
        this._options.onBlur(e);
      }
    });

    return this.view;
  }

  allVueComponents() {
    return this._allVueComponents;
  }

  allStrings() {
    const labels = [];

    function findLabelRecursively(toolbarItem) {
      if (toolbarItem.children) {
        toolbarItem.children.forEach(findLabelRecursively);
      }

      if (!toolbarItem.label) {
        return;
      }

      labels.push(toolbarItem.label);
    }

    this._toolbarItems.forEach(findLabelRecursively);

    return labels.concat([langString('pluginname', 'editor_weka')]);
  }

  getParent() {
    return this._parent;
  }

  _componentNodeView(component, componentContext, node, view, getPos) {
    return new ComponentView(component, {
      editor: this,
      node: node,
      view,
      getPos,
      parent: this._parent,
      context: componentContext,
    });
  }

  /**
   * Dispatch a transaction.
   *
   * @param {Transaction} transaction
   */
  dispatch(transaction) {
    const newState = this.state.apply(transaction);
    if (this.view && !this.destroyed) {
      this.view.updateState(newState);
    }
    this.state = newState;
    this._value = WekaValue.fromState(newState, this);

    if (this._options.onTransaction) {
      this._options.onTransaction({
        value: this._value,
        transaction,
      });
    }

    if (!transaction.docChanged || transaction.getMeta('preventUpdate')) {
      return;
    }

    if (this._options.onUpdate) {
      this._options.onUpdate(this._value);
    }
  }

  /**
   * Forces the editor's view to be re-rendered.
   */
  forceRerenderView() {
    if (!this.view) return;
    const state = EditorState.create(Object.assign({}, this._editorConfig()));
    this.view.updateState(state);
    this.view.updateState(this.state);
  }

  /**
   * Returning the file storage item's id that are holding all the files.
   *
   * @return {?number}
   */
  getFileStorageItemId() {
    return this.fileStorage.getFileStorageItemId();
  }

  /**
   * Create plugins.
   *
   * @returns {Array<Plugin>}
   */
  _createPlugins() {
    let plugins = [];
    if (Array.isArray(this._plugins)) {
      plugins = this._plugins;
    }

    // Note: we use the plugins defined by the extension first, then following up with the base plugins keymap defined by the editor.
    // With this structure, we care assure that the handleKey event can be triggered first, then the base handling.
    return plugins.concat(
      cursorInsideNode(),
      textPlaceholder(this._options.placeholder),
      inputRules({ rules: this._inputRules }),
      keymap(buildKeymap(this.schema, this._mapKeys)),
      keymap(baseKeymap),
      dropCursor(),
      gapCursor(),
      history()
    );
  }

  getToolbarItems() {
    this._toolbarItemInstances.map(b => {
      return b.update(this);
    });

    return this._toolbarItems;
  }

  /**
   * Execute a command.
   *
   * @template TReturn
   * @param {(state: EditorState, dispatch: (tr: Transaction) => TReturn, view: EditorView) => any} command
   * @returns {(TReturn | boolean)}
   */
  execute(command) {
    try {
      return command(this.state, this.dispatch, this.view);
    } catch (e) {
      console.error('[Weka] Failed to execute command.');
      console.error(e);
      const str = langString('error_failed_to_execute', 'editor_weka');
      loadLangStrings([str]).then(() =>
        notify({ type: 'error', message: str.toString() })
      );
      return false;
    }
  }

  /**
   * Check if a command can be executed.
   *
   * @param {Command} command
   */
  canExecute(command) {
    return command(this.state, null, null);
  }

  /**
   *
   * @param {String} name
   * @return {Boolean}
   */
  hasNode(name) {
    const schema = this.state.schema;
    return name in schema.nodes;
  }

  /**
   * Destroy the editor and release all resources.
   */
  destroy() {
    if (this.view) {
      this.view.destroy();
    }
    this.destroyed = true;
  }

  /**
   * Returning the instance's identifier that is set within options.
   * Null will be returned if the fields are not present in options.
   *
   * @return {{
   *   instanceId: ?Number,
   *   area: ?String,
   *   component: ?String,
   *   contextId: ?Number
   * }}
   */
  get identifier() {
    return {
      contextId: this._options.contextId || null,
      component: this._options.component || null,
      area: this._options.area || null,
      instanceId: this._options.instanceId || null,
    };
  }

  /**
   *
   * @return {Function[]}
   */
  get extensionFormatters() {
    return this._extensions.map(extension => {
      return extension.applyFormatters.bind(extension);
    });
  }

  /**
   * Set whether the editor should be editable or not.
   *
   * @param {boolean} value
   */
  setEditable(value) {
    this._editable = value;
    if (this.view) {
      // This is already set in createView, but we need to set it again so
      // ProseMirror rerenders.
      this.view.setProps({
        editable: () => result(this._editable),
      });
    }
  }

  /**
   * Check whether the editor should be editable or not.
   * @returns {boolean}
   */
  getEditable() {
    return this._editable;
  }

  /*
   * Execute visitors defined by extensions on the provided serialized doc.
   *
   * @param {object} doc
   * @param {string} type
   */
  _execSerializedVisitors(doc, type) {
    const extensionVisitors = [];
    this._extensions.forEach(ext => {
      const key = type + 'SerializedVisitor';
      if (ext[key]) {
        extensionVisitors.push(ext[key]());
      }
    });
    if (extensionVisitors.length > 0) {
      extensionVisitors.forEach(visitor => traverseNode(doc, visitor));
    }
  }
}
