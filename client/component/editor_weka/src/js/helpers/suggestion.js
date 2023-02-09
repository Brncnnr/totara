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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @module editor_weka
 */

import { ResolvedPos } from 'ext_prosemirror/model';
import Vue from 'vue';
import { getClosestScrollable } from 'tui/dom/scroll';
import { position } from 'tui/lib/popover';
import { Rect, Size } from 'tui/geometry';
import { getBoundingClientRect } from 'tui/dom/position';
import ResizeObserver from 'tui/polyfills/ResizeObserver';
import { loadStrings } from 'tui/i18n';
import { collectStrings } from 'tui/i18n_vue_plugin';

export default class Suggestion {
  /**
   * Create a new suggestion instance.
   *
   * @param {Editor} editor
   */
  constructor(editor) {
    this._instance = null;
    this._editor = editor;
    this._updatePosition = this._updatePosition.bind(this);
  }

  /**
   *
   * @param {RegExp} $regex
   * @param {ResolvedPos} $position
   * @return {{range: {from: *, to: *}, text: string}|null}
   */
  matcher($regex, $position) {
    if (!$position || !($position instanceof ResolvedPos)) {
      return null;
    }

    const text = $position.doc.textBetween(
      $position.start(),
      $position.pos,
      '\n',
      '\0'
    );

    // Empty string, no point to return the matcher.
    if (text === '') {
      return null;
    }

    let match = $regex.exec(text);
    if (!match) {
      return null;
    }

    let from = match.index + $position.start(),
      to = from + match[0].length;

    return {
      range: {
        from,
        to,
      },
      text: match[0],
    };
  }

  /**
   * Destroy the instance.
   */
  destroyInstance() {
    if (this._scrollableContainers) {
      this._scrollableContainers.forEach(x =>
        x.removeEventListener('scroll', this._updatePosition)
      );
      this._scrollableContainers = null;
    }

    window.removeEventListener('resize', this._updatePosition);

    if (this._resizeObserver) {
      this._resizeObserver.disconnect();
      this._resizeObserver = null;
    }
    if (this._instance !== null) {
      this._instance.$destroy();
      document.body.removeChild(this._instance.$el);
      this._instance = null;
    }
  }

  /**
   * Resets the component object.
   * @returns {{apply: boolean, from: number, text: string, to: number}}
   */
  resetComponent() {
    console.warn(
      '[editor_weka] The function Suggestion.resetComponent() had been deprecated, ',
      'please do not use'
    );

    return { apply: false, text: '', from: 0, to: 0 };
  }

  /**
   * Show popup with the list of matching suggestions.
   *
   * @param {EditorView} view
   * @param {Object} component
   * @param {Object} range
   * @param {String} text
   * @param {Function} callback
   */
  async showList({ view, component, state: { range, text }, callback }) {
    // If callback function provided.
    if (callback) {
      console.warn(
        '[editor_weka] The fourth parameter of function Suggestion.showList(), ',
        'had been deprecated, and no longer used'
      );
    }

    if (this._instance !== null) {
      this.destroyInstance();
    }

    await loadStrings(collectStrings(component.component));

    const element = document.createElement('span');
    document.body.appendChild(element);

    this._view = view;
    this._range = range;

    this._location = Vue.observable(this._getLocation());
    const propsData = component.props
      ? Object.assign({}, component.props, { location: this._location })
      : undefined;

    this._instance = new (Vue.extend(component.component))({
      parent: this._editor.getParent(),
      propsData: propsData || {
        location: this._location,
        pattern: text,
        area: this._editor.identifier.area || undefined,
        contextId: this._editor.identifier.contextId || undefined,
        component: this._editor.identifier.component || undefined,
        instanceId: this._editor.identifier.instanceId || undefined,
      },
    });

    this._instance.$mount(element);

    this._scrollableContainers = [];
    let scrollable = getClosestScrollable(this._editor.viewExtrasLiveEl);
    while (scrollable) {
      this._scrollableContainers.push(scrollable);
      scrollable.addEventListener('scroll', this._updatePosition);
      scrollable = getClosestScrollable(scrollable.parentNode);
    }
    this._scrollableContainers.push(document);
    document.addEventListener('scroll', this._updatePosition);

    window.addEventListener('resize', this._updatePosition);

    this._resizeObserver = new ResizeObserver(this._updatePosition);
    this._resizeObserver.observe(this._instance.$el);

    this._instance.$on('item-selected', ({ id, text }) => {
      this.destroyInstance();

      // Add the component node into the editor.
      const node = this.getNode(
        view.state,
        component.name,
        component.attrs(id, text)
      );
      this.convertToNode(range, node);
    });
    this._instance.$on('dismiss', () => {
      this.destroyInstance();
      this._editor.view.focus();
    });
    Vue.nextTick(this._updatePosition);
  }

  /**
   * Update position of suggestion menu
   */
  _updatePosition() {
    Object.assign(this._location, this._getLocation());
  }

  /**
   * Work out location to place suggestion menu
   *
   * @returns {{x: number, y: number}}
   */
  _getLocation() {
    const html = document.documentElement;
    const parentCoords = getBoundingClientRect(document.body);
    const refCoords = this._view.coordsAtPos(this._range.from);
    const refRect = Rect.fromPositions({
      left: refCoords.left,
      top: refCoords.top,
      right: refCoords.right + 1,
      bottom: refCoords.bottom,
    }).sub(parentCoords.getPosition());
    const viewport = new Rect(0, 0, html.clientWidth, html.clientHeight).sub(
      parentCoords.getPosition()
    );

    const pos = position({
      position: ['bottom', 'left'],
      ref: refRect,
      viewport,
      size: this._instance
        ? new Size(
            this._instance.$el.offsetWidth,
            this._instance.$el.offsetHeight
          )
        : new Size(50, 50),
      padding: 0,
    });

    return {
      x: pos.location.x,
      y: pos.location.y,
    };
  }

  /**
   * Gets the node to replace text.
   * @param {EditorState} state
   * @param {String} name
   * @param {Object} props
   * @returns {Node}
   */
  getNode(state, name, props) {
    return state.schema.node(name, props);
  }

  /**
   * Convert text to component node.
   * @param {Object} range
   * @param {Object} node
   */
  convertToNode(range, node) {
    this._editor.execute((state, dispatch) => {
      let tr = this.createTransaction(state, range, node);

      // Dispatch the transaction with the above changes.
      dispatch(tr);

      // Bring back the focus to the editor.
      this._editor.view.focus();
    });
  }

  /**
   * Create transaction to replace text with node.
   *
   * @param {EditorState} state
   * @param {Object} range
   * @param {Node} node
   * @returns {Transaction}
   */
  createTransaction(state, range, node) {
    let tr = state.tr;

    // Replace the typed text with the selected node component.
    tr = tr.replaceWith(range.from, range.to, node);

    return tr;
  }

  /**
   *
   * @param {Transaction} transaction
   * @param {{
   *  active: Boolean,
   *  range: Object,
   *  text: String|null
   * }} oldState
   * @param {RegExp} regex
   * @param {Object|null} cache - deprecated
   * @return {Object}
   */
  apply(transaction, oldState, regex, cache) {
    if (cache) {
      console.warn(
        '[editor_weka] The fourth parameter of function Suggestion.apply() had been deprecated ',
        'and no longer needed. Please update all calls'
      );
    }

    const { selection } = transaction;
    let next = Object.assign({}, oldState);

    if (selection.from === selection.to) {
      const match = this.matcher(regex, selection.$from);

      if (match) {
        next.active = true;
        next.range = match.range;
        next.text = match.text;

        return next;
      }
    }

    next.active = false;
    next.range = {};
    next.text = null;

    return next;
  }
}
