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
 * @module tui
 */

/**
 * Module store.
 *
 * Handles storing and resolving JS modules.
 *
 * @private
 */
export default class TotaraModuleStore {
  constructor({ bundleLoader }) {
    this._loader = bundleLoader;
    this._modules = {};
    this._overridablePrefixes = [
      {
        prefix: ['components', 'overrides'],
        replaceWith: ['components'],
      },
      { prefix: ['pages', 'overrides'], replaceWith: ['pages'] },
    ];
    this._pendingOverrideChains = {};
    this._overrideChains = {};
    this._currentlyEvaluating = {};
  }

  /**
   * Add a set of modules from webpack's `require.context()` or compatible.
   *
   * @param {string} idBase ID prefix, excluding /. e.g. 'tui'.
   * @param {*} req Result of calling `require.context()`.
   */
  addFromContext(idBase, req) {
    req.keys().forEach(key => {
      // exclude modules with a . in the name
      if (/\.[^/\n]+$/.test(key)) {
        return;
      }

      // strip out relative './'
      if (!key.startsWith('./')) {
        return;
      }
      const id = key.substr(2);

      // delay module execution to when they're actually required
      // otherwise any module side effects will happen immediately (e.g. css
      // being inserted) and dependency resolution will not work properly as
      // the module's dependencies may not have been added to the store yet
      const getter = req.bind(null, key);

      const fullId = idBase + '/' + id;

      this.addModule(fullId, getter);
    });
  }

  /**
   * Add a single module.
   *
   * @param {string} id Module ID, e.g. 'tui/components/Test' or 'vue'.
   * @param {function} getter Function returning the module's exports as an
   *     object. Will be called whenever we need to resolve the value of this
   *     module. The result of this function is not cached as whatever is
   *     responsible for resolving the module (webpack normally) should handle
   *     caching, but this is something to be aware of if you are are calling
   *     this function yourself. Not caching also allows for some flexibility if
   *     the module changes, e.g. for Hot Module Replacement.
   */
  addModule(id, getter) {
    const parsedName = this._parseName(id);

    if (parsedName.overridingComponent) {
      const overridingId =
        parsedName.overridingComponent + '/' + parsedName.path;
      // if the module this module is overriding has not been loaded yet, delay
      // adding this module until after it has
      if (!this._modules[overridingId]) {
        if (this._pendingOverrideChains[overridingId]) {
          this._pendingOverrideChains[overridingId].push({ id, getter });
        } else {
          this._pendingOverrideChains[overridingId] = [{ id, getter }];
        }
        return;
      }
      this._addModule(id, getter);
      this._addModule(overridingId, getter);
    } else {
      this._addModule(id, getter);

      // check for pending override chains
      if (this._pendingOverrideChains[id]) {
        this._pendingOverrideChains[id].forEach(x => {
          this._addModule(x.id, x.getter);
          this._addModule(id, x.getter);
        });
        delete this._pendingOverrideChains[id];
      }
    }
  }

  /**
   * Add a single module (internal)
   *
   * @private
   * @param {string} id
   * @param {function} getter
   */
  _addModule(id, getter) {
    // if overriding a module, store its previous value
    if (this._modules[id]) {
      if (this._overrideChains[id]) {
        this._overrideChains[id].push(getter);
      } else {
        this._overrideChains[id] = [this._modules[id], getter];
      }
    }
    this._modules[id] = getter;
  }

  /**
   * Check if the provided module is loaded and available.
   *
   * @param {string} id
   */
  hasModule(id) {
    return !!this._modules[id];
  }

  /**
   * Check if the provided module can be synchronously imported.
   *
   * The difference from hasModule is if hasModule() returns true, the module
   * is definitely available, whereas if this returns true we know that we don't
   * need to load any more code from the server to import the module, it's
   * either available or not. If this function returns false, we need to load
   * code.
   *
   * @param {string} id
   */
  syncImportable(id) {
    // hasModule check is not just an optimization - it is needed for
    // syncImportable to return the correct result for modules that are not part
    // of Tui components - like 'vue'
    if (this.hasModule(id)) {
      return true;
    }
    const comp = this._extractComponent(id);
    if (this._loader.isComponentFinal(comp)) {
      return true;
    }
    return false;
  }

  /**
   * Get all exports of the module with the provided ID.
   *
   * @param {string} id Module ID, e.g. 'tui/components/Test' or 'vue'.
   * @return {*} Module exports.
   * @throws {Error} when module cannot be found.
   */
  require(id) {
    const getter = this._modules[id];
    if (!getter) {
      const component = this._extractComponent(id);
      let error;
      if (this._loader.isComponentLoaded(component)) {
        error = new Error(
          `Cannot find module "${id}" in Tui component "${component}"`
        );
      } else if (this._loader.isComponentFinal(component)) {
        // not loaded but in final state: must be an error loading the bundle
        error = new Error(
          `Module "${id}" is not available as the Tui component ` +
            `bundle for "${component}" failed to load`
        );
      } else {
        error = new Error(
          `Tui component "${component}" is not loaded. Load the module "${id}" ` +
            `asynchronously with tui.import() or tui.asyncComponent(), or ` +
            `declare a static dependency on "${component}" in tui.json.`
        );
      }
      error.code = 'MODULE_NOT_FOUND';
      throw error;
    }

    // require previous versions for their side effects (css insertion)
    // this also helps for accessing previous implementations later because
    // they would have already been evaluated
    // i.e. side effects happen in the correct order
    const chain = this._overrideChains[id];
    if (chain) {
      for (let i = 0; i < chain.length - 1; i++) {
        this._currentlyEvaluating[id] = chain[i];
        chain[i]();
        delete this._currentlyEvaluating[id];
      }
    }

    this._currentlyEvaluating[id] = getter;
    const result = getter();
    delete this._currentlyEvaluating[id];
    return result;
  }

  /**
   * Asynchronously load the module with the provided ID if it is not loaded,
   * then return all its exports.
   *
   * @param {string} id Module ID, e.g. 'tui/components/Test' or 'vue'.
   * @returns {Promise}
   *   resolving to module exports, or rejecting when module cannot be found or
   *   bundle load fails.
   */
  async import(id) {
    if (this.syncImportable(id)) {
      return this.require(id);
    }
    try {
      await this._loader.loadBundle(this._extractComponent(id));
    } catch (e) {
      // failed/not found are handled by require with more context
      if (e.code != 'BUNDLE_FAILED' && e.code != 'BUNDLE_NOT_FOUND') {
        throw e;
      }
    }
    return this.require(id);
  }

  /**
   * Extract the component (e.g. tui) from a module id (e.g. tui/foo/bar).
   * If there are no slashes the id will be returned as-is.
   *
   * @param {string} id
   */
  _extractComponent(id) {
    const i = id.indexOf('/');
    return i === -1 ? id : id.slice(0, i);
  }

  /**
   * Get the default export of the provided module.
   *
   * @param {*} result Module exports.
   * @return {*} Default export.
   * @throws {Error} when module cannot be found.
   */
  default(result) {
    if (result && result.__esModule) {
      // ES module - default export is on .default
      return result.default;
    }
    // CommonJS only supports a single export
    return result;
  }

  /**
   * Get a list of the loaded submodules of the specified module.
   *
   * E.g. getLoadedSubmodules('tui')
   * => ['tui/foo', 'tui/components/Comp']
   *
   * @param {string} prefix
   * @returns {string[]}
   */
  getLoadedSubmodules(prefix) {
    if (prefix.slice(-1) !== '/') {
      prefix = prefix + '/';
    }
    return Object.keys(this._modules).filter(x => x.startsWith(prefix));
  }

  /**
   * Check if the specified module is currently being evaluated as part of the
   * require() process
   *
   * @return {boolean}
   */
  isEvaluating(id) {
    return !!this._currentlyEvaluating[id];
  }

  /**
   * During overridden module resolution, call this to get the previous defined
   * version of the module
   *
   * @param {string} id
   * @return {?function}
   * @private
   */
  _requirePrevious(id) {
    const current = this._currentlyEvaluating[id];
    if (!current) {
      throw new Error('Not currently resolving module' + id);
    }
    if (!this._overrideChains[id]) {
      return undefined;
    }
    const index = this._overrideChains[id].indexOf(current);
    const getter = this._overrideChains[id][index - 1];
    return getter ? getter() : undefined;
  }

  /**
   * Parse the name of a module and return data needed to use it
   * e.g. override info
   *
   * @private
   * @param {string} fullId
   */
  _parseName(fullId) {
    let parts = fullId.split('/');

    const component = parts.shift();
    let overridingComponent = null;

    // only themes can override components
    if (component.startsWith('theme_')) {
      // check if this path is an override
      for (let i = 0; i < this._overridablePrefixes.length; i++) {
        const item = this._overridablePrefixes[i];
        if (this._arrayStartsWith(parts, item.prefix)) {
          overridingComponent = parts[item.prefix.length];
          // remove prefix + overriding component
          parts.splice(0, item.prefix.length + 1);
          // insert replaceWith in their place
          parts = item.replaceWith.concat(parts);
          break;
        }
      }
    }

    return {
      component: component,
      overridingComponent: overridingComponent,
      path: parts.join('/'),
    };
  }

  /**
   * Check if an array contains all elements of prefix, in order, at the beginning
   *
   * @private
   * @param {Array} arr
   * @param {Array} prefix
   */
  _arrayStartsWith(arr, prefix) {
    return !prefix.some((v, i) => arr[i] !== v);
  }
}
