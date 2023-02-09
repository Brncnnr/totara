/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (function() { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./client/component/pathway_learning_plan/src/components sync recursive ^(?:(?%21__[a-z]*__%7C[/\\\\]internal[/\\\\]).)*$":
/*!*******************************************************************************************************************!*\
  !*** ./client/component/pathway_learning_plan/src/components/ sync ^(?:(?%21__[a-z]*__%7C[/\\]internal[/\\]).)*$ ***!
  \*******************************************************************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

eval("var map = {\n\t\"./achievements/AchievementDisplay\": \"./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue\",\n\t\"./achievements/AchievementDisplay.vue\": \"./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue\"\n};\n\n\nfunction webpackContext(req) {\n\tvar id = webpackContextResolve(req);\n\treturn __webpack_require__(id);\n}\nfunction webpackContextResolve(req) {\n\tif(!__webpack_require__.o(map, req)) {\n\t\tvar e = new Error(\"Cannot find module '\" + req + \"'\");\n\t\te.code = 'MODULE_NOT_FOUND';\n\t\tthrow e;\n\t}\n\treturn map[req];\n}\nwebpackContext.keys = function webpackContextKeys() {\n\treturn Object.keys(map);\n};\nwebpackContext.resolve = webpackContextResolve;\nmodule.exports = webpackContext;\nwebpackContext.id = \"./client/component/pathway_learning_plan/src/components sync recursive ^(?:(?%21__[a-z]*__%7C[/\\\\\\\\]internal[/\\\\\\\\]).)*$\";\n\n//# sourceURL=webpack:///./client/component/pathway_learning_plan/src/components/_sync_^(?");

/***/ }),

/***/ "./server/totara/competency/pathway/learning_plan/webapi/ajax/competency_plans.graphql":
/*!*********************************************************************************************!*\
  !*** ./server/totara/competency/pathway/learning_plan/webapi/ajax/competency_plans.graphql ***!
  \*********************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n\n    var doc = {\"kind\":\"Document\",\"definitions\":[{\"kind\":\"OperationDefinition\",\"operation\":\"query\",\"name\":{\"kind\":\"Name\",\"value\":\"pathway_learning_plan_competency_plans\"},\"variableDefinitions\":[{\"kind\":\"VariableDefinition\",\"variable\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"user_id\"}},\"type\":{\"kind\":\"NonNullType\",\"type\":{\"kind\":\"NamedType\",\"name\":{\"kind\":\"Name\",\"value\":\"core_id\"}}},\"directives\":[]},{\"kind\":\"VariableDefinition\",\"variable\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"assignment_id\"}},\"type\":{\"kind\":\"NonNullType\",\"type\":{\"kind\":\"NamedType\",\"name\":{\"kind\":\"Name\",\"value\":\"core_id\"}}},\"directives\":[]}],\"directives\":[],\"selectionSet\":{\"kind\":\"SelectionSet\",\"selections\":[{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"pathway_learning_plan_competency_plans\"},\"arguments\":[{\"kind\":\"Argument\",\"name\":{\"kind\":\"Name\",\"value\":\"user_id\"},\"value\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"user_id\"}}},{\"kind\":\"Argument\",\"name\":{\"kind\":\"Name\",\"value\":\"assignment_id\"},\"value\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"assignment_id\"}}}],\"directives\":[],\"selectionSet\":{\"kind\":\"SelectionSet\",\"selections\":[{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"learning_plans\"},\"arguments\":[],\"directives\":[],\"selectionSet\":{\"kind\":\"SelectionSet\",\"selections\":[{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"id\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"can_view\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"name\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"description\"},\"arguments\":[],\"directives\":[]}]}},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"scale_value\"},\"arguments\":[],\"directives\":[],\"selectionSet\":{\"kind\":\"SelectionSet\",\"selections\":[{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"name\"},\"arguments\":[],\"directives\":[]}]}},{\"kind\":\"Field\",\"alias\":{\"kind\":\"Name\",\"value\":\"date\"},\"name\":{\"kind\":\"Name\",\"value\":\"date_assigned\"},\"arguments\":[{\"kind\":\"Argument\",\"name\":{\"kind\":\"Name\",\"value\":\"format\"},\"value\":{\"kind\":\"EnumValue\",\"value\":\"DATE\"}}],\"directives\":[]}]}}]}}]};\n    /* harmony default export */ __webpack_exports__[\"default\"] = (doc);\n  \n\n//# sourceURL=webpack:///./server/totara/competency/pathway/learning_plan/webapi/ajax/competency_plans.graphql?");

/***/ }),

/***/ "./client/component/pathway_learning_plan/src/tui.json":
/*!*************************************************************!*\
  !*** ./client/component/pathway_learning_plan/src/tui.json ***!
  \*************************************************************/
/***/ (function(__unused_webpack_module, __unused_webpack_exports, __webpack_require__) {

eval("!function() {\n\"use strict\";\n\nif (typeof tui !== 'undefined' && tui._bundle.isLoaded(\"pathway_learning_plan\")) {\n  console.warn(\n    '[tui bundle] The bundle \"' + \"pathway_learning_plan\" +\n    '\" is already loaded, skipping initialisation.'\n  );\n  return;\n};\ntui._bundle.register(\"pathway_learning_plan\")\ntui._bundle.addModulesFromContext(\"pathway_learning_plan/components\", __webpack_require__(\"./client/component/pathway_learning_plan/src/components sync recursive ^(?:(?%21__[a-z]*__%7C[/\\\\\\\\]internal[/\\\\\\\\]).)*$\"));\n}();\n\n//# sourceURL=webpack:///./client/component/pathway_learning_plan/src/tui.json?");

/***/ }),

/***/ "./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1140[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?vue&type=custom&index=0&blockType=lang-strings":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1140[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?vue&type=custom&index=0&blockType=lang-strings ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": function() { return /* export default binding */ __WEBPACK_DEFAULT_EXPORT__; }\n/* harmony export */ });\n/* harmony default export */ function __WEBPACK_DEFAULT_EXPORT__(component) {\n        component.options.__langStrings = \n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n{\n  \"pathway_learning_plan\" : [\n    \"achievement_via_learning_plan\",\n    \"name\",\n    \"no_available_learning_plans\",\n    \"no_rating_set\",\n    \"no_permission_view_plan\",\n    \"set_on\",\n    \"view_plan\",\n    \"work_towards_level\"\n  ]\n}\n;\n    }\n\n//# sourceURL=webpack:///./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1140%5B0%5D.rules%5B0%5D.use%5B0%5D!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue":
/*!***************************************************************************************************!*\
  !*** ./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue ***!
  \***************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _AchievementDisplay_vue_vue_type_template_id_53405a02___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AchievementDisplay.vue?vue&type=template&id=53405a02& */ \"./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?vue&type=template&id=53405a02&\");\n/* harmony import */ var _AchievementDisplay_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AchievementDisplay.vue?vue&type=script&lang=js& */ \"./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?vue&type=script&lang=js&\");\n/* harmony import */ var _AchievementDisplay_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AchievementDisplay.vue?vue&type=style&index=0&lang=scss& */ \"./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?vue&type=style&index=0&lang=scss&\");\n/* harmony import */ var _AchievementDisplay_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_AchievementDisplay_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ \"./node_modules/vue-loader/lib/runtime/componentNormalizer.js\");\n/* harmony import */ var _AchievementDisplay_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./AchievementDisplay.vue?vue&type=custom&index=0&blockType=lang-strings */ \"./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?vue&type=custom&index=0&blockType=lang-strings\");\n\n\n\n;\n\n\n/* normalize component */\n\nvar component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__[\"default\"])(\n  _AchievementDisplay_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__[\"default\"],\n  _AchievementDisplay_vue_vue_type_template_id_53405a02___WEBPACK_IMPORTED_MODULE_0__.render,\n  _AchievementDisplay_vue_vue_type_template_id_53405a02___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,\n  false,\n  null,\n  null,\n  null\n  \n)\n\n/* custom blocks */\n;\nif (typeof _AchievementDisplay_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_4__[\"default\"] === 'function') (0,_AchievementDisplay_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_4__[\"default\"])(component)\n\ncomponent.options.__hasBlocks = {\"script\":true,\"template\":true};\n/* hot reload */\nif (false) { var api; }\ncomponent.options.__file = \"client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue\"\n/* harmony default export */ __webpack_exports__[\"default\"] = (component.exports);\n\n//# sourceURL=webpack:///./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?");

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js??clonedRuleSet-1135[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?vue&type=script&lang=js&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js??clonedRuleSet-1135[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?vue&type=script&lang=js& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var totara_competency_components_achievements_AchievementLayout__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! totara_competency/components/achievements/AchievementLayout */ \"totara_competency/components/achievements/AchievementLayout\");\n/* harmony import */ var totara_competency_components_achievements_AchievementLayout__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(totara_competency_components_achievements_AchievementLayout__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var tui_components_links_ActionLink__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! tui/components/links/ActionLink */ \"tui/components/links/ActionLink\");\n/* harmony import */ var tui_components_links_ActionLink__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(tui_components_links_ActionLink__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var tui_components_datatable_Cell__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! tui/components/datatable/Cell */ \"tui/components/datatable/Cell\");\n/* harmony import */ var tui_components_datatable_Cell__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(tui_components_datatable_Cell__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var tui_components_datatable_ExpandCell__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! tui/components/datatable/ExpandCell */ \"tui/components/datatable/ExpandCell\");\n/* harmony import */ var tui_components_datatable_ExpandCell__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(tui_components_datatable_ExpandCell__WEBPACK_IMPORTED_MODULE_3__);\n/* harmony import */ var tui_components_datatable_Table__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! tui/components/datatable/Table */ \"tui/components/datatable/Table\");\n/* harmony import */ var tui_components_datatable_Table__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(tui_components_datatable_Table__WEBPACK_IMPORTED_MODULE_4__);\n/* harmony import */ var pathway_learning_plan_graphql_competency_plans__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! pathway_learning_plan/graphql/competency_plans */ \"./server/totara/competency/pathway/learning_plan/webapi/ajax/competency_plans.graphql\");\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n// Components\n\n\n\n\n // GraphQL\n\n\n/* harmony default export */ __webpack_exports__[\"default\"] = ({\n  components: {\n    AchievementLayout: (totara_competency_components_achievements_AchievementLayout__WEBPACK_IMPORTED_MODULE_0___default()),\n    ActionLink: (tui_components_links_ActionLink__WEBPACK_IMPORTED_MODULE_1___default()),\n    Cell: (tui_components_datatable_Cell__WEBPACK_IMPORTED_MODULE_2___default()),\n    ExpandCell: (tui_components_datatable_ExpandCell__WEBPACK_IMPORTED_MODULE_3___default()),\n    Table: (tui_components_datatable_Table__WEBPACK_IMPORTED_MODULE_4___default())\n  },\n  inheritAttrs: false,\n  props: {\n    assignmentId: {\n      required: true,\n      type: Number\n    },\n    userId: {\n      required: true,\n      type: Number\n    }\n  },\n  data: function data() {\n    return {\n      plans: []\n    };\n  },\n  apollo: {\n    plans: {\n      query: pathway_learning_plan_graphql_competency_plans__WEBPACK_IMPORTED_MODULE_5__[\"default\"],\n      context: {\n        batch: true\n      },\n      variables: function variables() {\n        return {\n          assignment_id: this.assignmentId,\n          user_id: this.userId\n        };\n      },\n      update: function update(_ref) {\n        var plans = _ref.pathway_learning_plan_competency_plans;\n        this.$emit('loaded');\n        return plans;\n      }\n    }\n  },\n  computed: {\n    /**\n     * Check if data contains learning plan\n     *\n     * @return {Boolean}\n     */\n    hasPlans: function hasPlans() {\n      return this.plans.learning_plans;\n    },\n\n    /**\n     * Check if a scale value has been set\n     *\n     * @return {Boolean}\n     */\n    hasValue: function hasValue() {\n      return this.hasPlans && this.plans.scale_value != null;\n    }\n  },\n  methods: {\n    /**\n     * Return URL for plan\n     *\n     * @param {Integer} planId\n     * @return {String}\n     */\n    getPlanUrl: function getPlanUrl(planId) {\n      return this.$url('/totara/plan/component.php', {\n        c: 'competency',\n        id: planId\n      });\n    }\n  }\n});\n\n//# sourceURL=webpack:///./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?./node_modules/babel-loader/lib/index.js??clonedRuleSet-1135%5B0%5D.rules%5B0%5D.use%5B0%5D!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet%5B1%5D.rules%5B3%5D.use%5B0%5D");

/***/ }),

/***/ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js":
/*!********************************************************************!*\
  !*** ./node_modules/vue-loader/lib/runtime/componentNormalizer.js ***!
  \********************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": function() { return /* binding */ normalizeComponent; }\n/* harmony export */ });\n/* globals __VUE_SSR_CONTEXT__ */\n\n// IMPORTANT: Do NOT use ES2015 features in this file (except for modules).\n// This module is a runtime utility for cleaner component module output and will\n// be included in the final webpack user bundle.\n\nfunction normalizeComponent (\n  scriptExports,\n  render,\n  staticRenderFns,\n  functionalTemplate,\n  injectStyles,\n  scopeId,\n  moduleIdentifier, /* server only */\n  shadowMode /* vue-cli only */\n) {\n  // Vue.extend constructor export interop\n  var options = typeof scriptExports === 'function'\n    ? scriptExports.options\n    : scriptExports\n\n  // render functions\n  if (render) {\n    options.render = render\n    options.staticRenderFns = staticRenderFns\n    options._compiled = true\n  }\n\n  // functional template\n  if (functionalTemplate) {\n    options.functional = true\n  }\n\n  // scopedId\n  if (scopeId) {\n    options._scopeId = 'data-v-' + scopeId\n  }\n\n  var hook\n  if (moduleIdentifier) { // server build\n    hook = function (context) {\n      // 2.3 injection\n      context =\n        context || // cached call\n        (this.$vnode && this.$vnode.ssrContext) || // stateful\n        (this.parent && this.parent.$vnode && this.parent.$vnode.ssrContext) // functional\n      // 2.2 with runInNewContext: true\n      if (!context && typeof __VUE_SSR_CONTEXT__ !== 'undefined') {\n        context = __VUE_SSR_CONTEXT__\n      }\n      // inject component styles\n      if (injectStyles) {\n        injectStyles.call(this, context)\n      }\n      // register component module identifier for async chunk inferrence\n      if (context && context._registeredComponents) {\n        context._registeredComponents.add(moduleIdentifier)\n      }\n    }\n    // used by ssr in case component is cached and beforeCreate\n    // never gets called\n    options._ssrRegister = hook\n  } else if (injectStyles) {\n    hook = shadowMode\n      ? function () {\n        injectStyles.call(\n          this,\n          (options.functional ? this.parent : this).$root.$options.shadowRoot\n        )\n      }\n      : injectStyles\n  }\n\n  if (hook) {\n    if (options.functional) {\n      // for template-only hot-reload because in that case the render fn doesn't\n      // go through the normalizer\n      options._injectStyles = hook\n      // register for functional component in vue file\n      var originalRender = options.render\n      options.render = function renderWithStyleInjection (h, context) {\n        hook.call(context)\n        return originalRender(h, context)\n      }\n    } else {\n      // inject component registration as beforeCreate hook\n      var existing = options.beforeCreate\n      options.beforeCreate = existing\n        ? [].concat(existing, hook)\n        : [hook]\n    }\n  }\n\n  return {\n    exports: scriptExports,\n    options: options\n  }\n}\n\n\n//# sourceURL=webpack:///./node_modules/vue-loader/lib/runtime/componentNormalizer.js?");

/***/ }),

/***/ "./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?vue&type=custom&index=0&blockType=lang-strings":
/*!**************************************************************************************************************************************************!*\
  !*** ./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?vue&type=custom&index=0&blockType=lang-strings ***!
  \**************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _tooling_webpack_tui_lang_strings_loader_js_clonedRuleSet_1140_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_AchievementDisplay_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1140[0].rules[0].use[0]!../../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AchievementDisplay.vue?vue&type=custom&index=0&blockType=lang-strings */ \"./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-1140[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?vue&type=custom&index=0&blockType=lang-strings\");\n /* harmony default export */ __webpack_exports__[\"default\"] = (_tooling_webpack_tui_lang_strings_loader_js_clonedRuleSet_1140_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_AchievementDisplay_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_0__[\"default\"]); \n\n//# sourceURL=webpack:///./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?");

/***/ }),

/***/ "./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?vue&type=template&id=53405a02&":
/*!**********************************************************************************************************************************!*\
  !*** ./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?vue&type=template&id=53405a02& ***!
  \**********************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_AchievementDisplay_vue_vue_type_template_id_53405a02___WEBPACK_IMPORTED_MODULE_0__.render; },\n/* harmony export */   \"staticRenderFns\": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_AchievementDisplay_vue_vue_type_template_id_53405a02___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }\n/* harmony export */ });\n/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_AchievementDisplay_vue_vue_type_template_id_53405a02___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AchievementDisplay.vue?vue&type=template&id=53405a02& */ \"./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?vue&type=template&id=53405a02&\");\n\n\n//# sourceURL=webpack:///./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?");

/***/ }),

/***/ "./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?vue&type=script&lang=js&":
/*!****************************************************************************************************************************!*\
  !*** ./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?vue&type=script&lang=js& ***!
  \****************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _node_modules_babel_loader_lib_index_js_clonedRuleSet_1135_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_AchievementDisplay_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js??clonedRuleSet-1135[0].rules[0].use[0]!../../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!../../../../../../node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./AchievementDisplay.vue?vue&type=script&lang=js& */ \"./node_modules/babel-loader/lib/index.js??clonedRuleSet-1135[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?vue&type=script&lang=js&\");\n /* harmony default export */ __webpack_exports__[\"default\"] = (_node_modules_babel_loader_lib_index_js_clonedRuleSet_1135_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_AchievementDisplay_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__[\"default\"]); \n\n//# sourceURL=webpack:///./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?");

/***/ }),

/***/ "./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?vue&type=style&index=0&lang=scss&":
/*!*************************************************************************************************************************************!*\
  !*** ./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?vue&type=style&index=0&lang=scss& ***!
  \*************************************************************************************************************************************/
/***/ (function() {

eval("\n\n//# sourceURL=webpack:///./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?");

/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?vue&type=template&id=53405a02&":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?vue&type=template&id=53405a02& ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": function() { return /* binding */ render; },\n/* harmony export */   \"staticRenderFns\": function() { return /* binding */ staticRenderFns; }\n/* harmony export */ });\nvar render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('div',{staticClass:\"tui-pathwayLearningPlanAchievement\"},[(!_vm.hasPlans)?_c('div',{staticClass:\"tui-pathwayLearningPlanAchievement__empty\"},[_vm._v(\"\\n    \"+_vm._s(_vm.$str('no_available_learning_plans', 'pathway_learning_plan'))+\"\\n  \")]):_c('div',{staticClass:\"tui-pathwayLearningPlanAchievement__content\"},[_c('AchievementLayout',{scopedSlots:_vm._u([{key:\"left\",fn:function(){return [_c('div',{staticClass:\"tui-pathwayLearningPlanAchievement__overview\"},[_c('h5',{staticClass:\"tui-pathwayLearningPlanAchievement__title\"},[_vm._v(\"\\n            \"+_vm._s(_vm.$str('achievement_via_learning_plan', 'pathway_learning_plan'))+\"\\n          \")]),_vm._v(\" \"),(_vm.hasValue)?[_c('div',{staticClass:\"tui-pathwayLearningPlanAchievement__value\"},[_c('span',{staticClass:\"tui-pathwayLearningPlanAchievement__value-title\"},[_vm._v(\"\\n                \"+_vm._s(_vm.plans.scale_value.name)+\"\\n              \")]),_vm._v(\"\\n              \"+_vm._s(_vm.$str('set_on', 'pathway_learning_plan', _vm.plans.date))+\"\\n            \")])]:[_c('div',{staticClass:\"tui-pathwayLearningPlanAchievement__noValue\"},[_vm._v(\"\\n              \"+_vm._s(_vm.$str('no_rating_set', 'pathway_learning_plan'))+\"\\n            \")])]],2)]},proxy:true},{key:\"right\",fn:function(){return [_c('Table',{staticClass:\"tui-pathwayLearningPlanAchievement__list\",attrs:{\"data\":_vm.plans.learning_plans,\"expandable-rows\":true},scopedSlots:_vm._u([{key:\"row\",fn:function(ref){\nvar row = ref.row;\nvar expand = ref.expand;\nvar expandState = ref.expandState;\nreturn [(!row.can_view)?[_c('ExpandCell',{attrs:{\"header\":true}}),_vm._v(\" \"),_c('Cell',{attrs:{\"size\":\"11\"}},[_vm._v(\"\\n                \"+_vm._s(_vm.$str('no_permission_view_plan', 'pathway_learning_plan'))+\"\\n              \")])]:[_c('ExpandCell',{attrs:{\"aria-label\":row.name,\"expand-state\":expandState},on:{\"click\":function($event){return expand()}}}),_vm._v(\" \"),_c('Cell',{attrs:{\"size\":\"11\",\"column-header\":_vm.$str('name', 'pathway_learning_plan')}},[_vm._v(\"\\n                \"+_vm._s(row.name)+\"\\n              \")])]]}},{key:\"expand-content\",fn:function(ref){\nvar row = ref.row;\nreturn [_c('div',{staticClass:\"tui-pathwayLearningPlanAchievement__summary\"},[_c('h6',{staticClass:\"tui-pathwayLearningPlanAchievement__summary-header\"},[_vm._v(\"\\n                \"+_vm._s(row.name)+\"\\n              \")]),_vm._v(\" \"),(row.description)?_c('div',{staticClass:\"tui-pathwayLearningPlanAchievement__summary-body\",domProps:{\"innerHTML\":_vm._s(row.description)}}):_vm._e(),_vm._v(\" \"),_c('ActionLink',{class:'tui-pathwayLearningPlanAchievement__summary-button',attrs:{\"href\":_vm.getPlanUrl(row.id),\"text\":_vm.$str('view_plan', 'pathway_learning_plan'),\"styleclass\":{\n                  primary: true,\n                  small: true,\n                }}})],1)]}}])})]},proxy:true}])})],1)])}\nvar staticRenderFns = []\nrender._withStripped = true\n\n\n//# sourceURL=webpack:///./client/component/pathway_learning_plan/src/components/achievements/AchievementDisplay.vue?./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "totara_competency/components/achievements/AchievementLayout":
/*!***********************************************************************************************!*\
  !*** external "tui.require(\"totara_competency/components/achievements/AchievementLayout\")" ***!
  \***********************************************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("totara_competency/components/achievements/AchievementLayout");

/***/ }),

/***/ "tui/components/datatable/Cell":
/*!*****************************************************************!*\
  !*** external "tui.require(\"tui/components/datatable/Cell\")" ***!
  \*****************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/components/datatable/Cell");

/***/ }),

/***/ "tui/components/datatable/ExpandCell":
/*!***********************************************************************!*\
  !*** external "tui.require(\"tui/components/datatable/ExpandCell\")" ***!
  \***********************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/components/datatable/ExpandCell");

/***/ }),

/***/ "tui/components/datatable/Table":
/*!******************************************************************!*\
  !*** external "tui.require(\"tui/components/datatable/Table\")" ***!
  \******************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/components/datatable/Table");

/***/ }),

/***/ "tui/components/links/ActionLink":
/*!*******************************************************************!*\
  !*** external "tui.require(\"tui/components/links/ActionLink\")" ***!
  \*******************************************************************/
/***/ (function(module) {

"use strict";
module.exports = tui.require("tui/components/links/ActionLink");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	!function() {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = function(module) {
/******/ 			var getter = module && module.__esModule ?
/******/ 				function() { return module['default']; } :
/******/ 				function() { return module; };
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = __webpack_require__("./client/component/pathway_learning_plan/src/tui.json");
/******/ 	
/******/ })()
;