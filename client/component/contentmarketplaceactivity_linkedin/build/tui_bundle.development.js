/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./client/component/contentmarketplaceactivity_linkedin/src/components sync recursive ^(?:(?%21__[a-z]*__%7C[/\\\\]internal[/\\\\]).)*$":
/*!*********************************************************************************************************************************!*\
  !*** ./client/component/contentmarketplaceactivity_linkedin/src/components/ sync ^(?:(?%21__[a-z]*__%7C[/\\]internal[/\\]).)*$ ***!
  \*********************************************************************************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

eval("var map = {\n\t\"./side-panel/LinkedInActivityContentsTree\": \"./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue\",\n\t\"./side-panel/LinkedInActivityContentsTree.vue\": \"./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue\"\n};\n\n\nfunction webpackContext(req) {\n\tvar id = webpackContextResolve(req);\n\treturn __webpack_require__(id);\n}\nfunction webpackContextResolve(req) {\n\tif(!__webpack_require__.o(map, req)) {\n\t\tvar e = new Error(\"Cannot find module '\" + req + \"'\");\n\t\te.code = 'MODULE_NOT_FOUND';\n\t\tthrow e;\n\t}\n\treturn map[req];\n}\nwebpackContext.keys = function webpackContextKeys() {\n\treturn Object.keys(map);\n};\nwebpackContext.resolve = webpackContextResolve;\nmodule.exports = webpackContext;\nwebpackContext.id = \"./client/component/contentmarketplaceactivity_linkedin/src/components sync recursive ^(?:(?%21__[a-z]*__%7C[/\\\\\\\\]internal[/\\\\\\\\]).)*$\";\n\n//# sourceURL=webpack:///./client/component/contentmarketplaceactivity_linkedin/src/components/_sync_^(?");

/***/ }),

/***/ "./client/component/contentmarketplaceactivity_linkedin/src/pages sync recursive ^(?:(?%21__[a-z]*__%7C[/\\\\]internal[/\\\\]).)*$":
/*!****************************************************************************************************************************!*\
  !*** ./client/component/contentmarketplaceactivity_linkedin/src/pages/ sync ^(?:(?%21__[a-z]*__%7C[/\\]internal[/\\]).)*$ ***!
  \****************************************************************************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

eval("var map = {\n\t\"./ActivityView\": \"./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue\",\n\t\"./ActivityView.vue\": \"./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue\"\n};\n\n\nfunction webpackContext(req) {\n\tvar id = webpackContextResolve(req);\n\treturn __webpack_require__(id);\n}\nfunction webpackContextResolve(req) {\n\tif(!__webpack_require__.o(map, req)) {\n\t\tvar e = new Error(\"Cannot find module '\" + req + \"'\");\n\t\te.code = 'MODULE_NOT_FOUND';\n\t\tthrow e;\n\t}\n\treturn map[req];\n}\nwebpackContext.keys = function webpackContextKeys() {\n\treturn Object.keys(map);\n};\nwebpackContext.resolve = webpackContextResolve;\nmodule.exports = webpackContext;\nwebpackContext.id = \"./client/component/contentmarketplaceactivity_linkedin/src/pages sync recursive ^(?:(?%21__[a-z]*__%7C[/\\\\\\\\]internal[/\\\\\\\\]).)*$\";\n\n//# sourceURL=webpack:///./client/component/contentmarketplaceactivity_linkedin/src/pages/_sync_^(?");

/***/ }),

/***/ "./server/mod/contentmarketplace/contentmarketplaces/linkedin/webapi/ajax/linkedin_activity.graphql":
/*!**********************************************************************************************************!*\
  !*** ./server/mod/contentmarketplace/contentmarketplaces/linkedin/webapi/ajax/linkedin_activity.graphql ***!
  \**********************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n\n    var doc = {\"kind\":\"Document\",\"definitions\":[{\"kind\":\"OperationDefinition\",\"operation\":\"query\",\"name\":{\"kind\":\"Name\",\"value\":\"contentmarketplaceactivity_linkedin_linkedin_activity\"},\"variableDefinitions\":[{\"kind\":\"VariableDefinition\",\"variable\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"cm_id\"}},\"type\":{\"kind\":\"NonNullType\",\"type\":{\"kind\":\"NamedType\",\"name\":{\"kind\":\"Name\",\"value\":\"core_id\"}}},\"directives\":[]}],\"directives\":[],\"selectionSet\":{\"kind\":\"SelectionSet\",\"selections\":[{\"kind\":\"Field\",\"alias\":{\"kind\":\"Name\",\"value\":\"instance\"},\"name\":{\"kind\":\"Name\",\"value\":\"contentmarketplaceactivity_linkedin_linkedin_activity\"},\"arguments\":[{\"kind\":\"Argument\",\"name\":{\"kind\":\"Name\",\"value\":\"cm_id\"},\"value\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"cm_id\"}}}],\"directives\":[],\"selectionSet\":{\"kind\":\"SelectionSet\",\"selections\":[{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"module\"},\"arguments\":[],\"directives\":[],\"selectionSet\":{\"kind\":\"SelectionSet\",\"selections\":[{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"id\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"course_module\"},\"arguments\":[],\"directives\":[],\"selectionSet\":{\"kind\":\"SelectionSet\",\"selections\":[{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"id\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"completion\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"completionenabled\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"completionstatus\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"rpl\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"progress\"},\"arguments\":[],\"directives\":[]}]}},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"course\"},\"arguments\":[],\"directives\":[],\"selectionSet\":{\"kind\":\"SelectionSet\",\"selections\":[{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"id\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"fullname\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"image\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"url\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"course_format\"},\"arguments\":[],\"directives\":[],\"selectionSet\":{\"kind\":\"SelectionSet\",\"selections\":[{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"has_course_view_page\"},\"arguments\":[],\"directives\":[]}]}}]}},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"name\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"intro\"},\"arguments\":[{\"kind\":\"Argument\",\"name\":{\"kind\":\"Name\",\"value\":\"format\"},\"value\":{\"kind\":\"EnumValue\",\"value\":\"HTML\"}}],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"completion_condition\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"interactor\"},\"arguments\":[],\"directives\":[],\"selectionSet\":{\"kind\":\"SelectionSet\",\"selections\":[{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"has_view_capability\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"can_enrol\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"can_launch\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"is_site_guest\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"is_enrolled\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"non_interactive_enrol_instance_enabled\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"supports_non_interactive_enrol\"},\"arguments\":[],\"directives\":[]}]}}]}},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"learning_object\"},\"arguments\":[],\"directives\":[],\"selectionSet\":{\"kind\":\"SelectionSet\",\"selections\":[{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"id\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"asset_type\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"display_level\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"time_to_complete\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"last_updated_at\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"web_launch_url\"},\"arguments\":[],\"directives\":[]},{\"kind\":\"Field\",\"name\":{\"kind\":\"Name\",\"value\":\"sso_launch_url\"},\"arguments\":[],\"directives\":[]}]}}]}}]}}]};\n    /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (doc);\n  \n\n//# sourceURL=webpack:///./server/mod/contentmarketplace/contentmarketplaces/linkedin/webapi/ajax/linkedin_activity.graphql?");

/***/ }),

/***/ "./server/mod/contentmarketplace/webapi/ajax/request_non_interactive_enrol.graphql":
/*!*****************************************************************************************!*\
  !*** ./server/mod/contentmarketplace/webapi/ajax/request_non_interactive_enrol.graphql ***!
  \*****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n\n    var doc = {\"kind\":\"Document\",\"definitions\":[{\"kind\":\"OperationDefinition\",\"operation\":\"mutation\",\"name\":{\"kind\":\"Name\",\"value\":\"mod_contentmarketplace_request_non_interactive_enrol\"},\"variableDefinitions\":[{\"kind\":\"VariableDefinition\",\"variable\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"cm_id\"}},\"type\":{\"kind\":\"NonNullType\",\"type\":{\"kind\":\"NamedType\",\"name\":{\"kind\":\"Name\",\"value\":\"core_id\"}}},\"directives\":[]}],\"directives\":[],\"selectionSet\":{\"kind\":\"SelectionSet\",\"selections\":[{\"kind\":\"Field\",\"alias\":{\"kind\":\"Name\",\"value\":\"result\"},\"name\":{\"kind\":\"Name\",\"value\":\"mod_contentmarketplace_request_non_interactive_enrol\"},\"arguments\":[{\"kind\":\"Argument\",\"name\":{\"kind\":\"Name\",\"value\":\"cm_id\"},\"value\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"cm_id\"}}}],\"directives\":[]}]}}]};\n    /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (doc);\n  \n\n//# sourceURL=webpack:///./server/mod/contentmarketplace/webapi/ajax/request_non_interactive_enrol.graphql?");

/***/ }),

/***/ "./server/mod/contentmarketplace/webapi/ajax/set_self_completion.graphql":
/*!*******************************************************************************!*\
  !*** ./server/mod/contentmarketplace/webapi/ajax/set_self_completion.graphql ***!
  \*******************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n\n    var doc = {\"kind\":\"Document\",\"definitions\":[{\"kind\":\"OperationDefinition\",\"operation\":\"mutation\",\"name\":{\"kind\":\"Name\",\"value\":\"mod_contentmarketplace_set_self_completion\"},\"variableDefinitions\":[{\"kind\":\"VariableDefinition\",\"variable\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"cm_id\"}},\"type\":{\"kind\":\"NonNullType\",\"type\":{\"kind\":\"NamedType\",\"name\":{\"kind\":\"Name\",\"value\":\"core_id\"}}},\"directives\":[]},{\"kind\":\"VariableDefinition\",\"variable\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"status\"}},\"type\":{\"kind\":\"NonNullType\",\"type\":{\"kind\":\"NamedType\",\"name\":{\"kind\":\"Name\",\"value\":\"param_boolean\"}}},\"directives\":[]}],\"directives\":[],\"selectionSet\":{\"kind\":\"SelectionSet\",\"selections\":[{\"kind\":\"Field\",\"alias\":{\"kind\":\"Name\",\"value\":\"result\"},\"name\":{\"kind\":\"Name\",\"value\":\"mod_contentmarketplace_set_self_completion\"},\"arguments\":[{\"kind\":\"Argument\",\"name\":{\"kind\":\"Name\",\"value\":\"cm_id\"},\"value\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"cm_id\"}}},{\"kind\":\"Argument\",\"name\":{\"kind\":\"Name\",\"value\":\"status\"},\"value\":{\"kind\":\"Variable\",\"name\":{\"kind\":\"Name\",\"value\":\"status\"}}}],\"directives\":[]}]}}]};\n    /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (doc);\n  \n\n//# sourceURL=webpack:///./server/mod/contentmarketplace/webapi/ajax/set_self_completion.graphql?");

/***/ }),

/***/ "./client/component/contentmarketplaceactivity_linkedin/src/tui.json":
/*!***************************************************************************!*\
  !*** ./client/component/contentmarketplaceactivity_linkedin/src/tui.json ***!
  \***************************************************************************/
/***/ ((__unused_webpack_module, __unused_webpack_exports, __webpack_require__) => {

eval("!function() {\n\"use strict\";\n\nif (typeof tui !== 'undefined' && tui._bundle.isLoaded(\"contentmarketplaceactivity_linkedin\")) {\n  console.warn(\n    '[tui bundle] The bundle \"' + \"contentmarketplaceactivity_linkedin\" +\n    '\" is already loaded, skipping initialisation.'\n  );\n  return;\n};\ntui._bundle.register(\"contentmarketplaceactivity_linkedin\")\ntui._bundle.addModulesFromContext(\"contentmarketplaceactivity_linkedin/components\", __webpack_require__(\"./client/component/contentmarketplaceactivity_linkedin/src/components sync recursive ^(?:(?%21__[a-z]*__%7C[/\\\\\\\\]internal[/\\\\\\\\]).)*$\"));\ntui._bundle.addModulesFromContext(\"contentmarketplaceactivity_linkedin/pages\", __webpack_require__(\"./client/component/contentmarketplaceactivity_linkedin/src/pages sync recursive ^(?:(?%21__[a-z]*__%7C[/\\\\\\\\]internal[/\\\\\\\\]).)*$\"));\n}();\n\n//# sourceURL=webpack:///./client/component/contentmarketplaceactivity_linkedin/src/tui.json?");

/***/ }),

/***/ "./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-881[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?vue&type=custom&index=0&blockType=lang-strings":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-881[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?vue&type=custom&index=0&blockType=lang-strings ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* export default binding */ __WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony default export */ function __WEBPACK_DEFAULT_EXPORT__(component) {\n        component.options.__langStrings = \n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n{\n  \"mod_contentmarketplace\": [\n    \"a11y_activity_difficulty\",\n    \"a11y_activity_time_to_complete\",\n    \"activity_contents\",\n    \"activity_set_self_completion\",\n    \"activity_status_completed\",\n    \"activity_status_not_completed\",\n    \"course_details\",\n    \"enrol_to_course\",\n    \"enrol_success_message\",\n    \"internal_error\",\n    \"launch\",\n    \"toggle_off_error\",\n    \"toggle_on_error\",\n    \"updated_at\",\n    \"viewing_as_enrollable_admin\",\n    \"viewing_as_enrollable_admin_self_enrol_disabled\",\n    \"viewing_as_enrollable_guest\",\n    \"viewing_as_guest\"\n  ],\n  \"core_enrol\": [\n    \"enrol\"\n  ]\n}\n;\n    }\n\n//# sourceURL=webpack:///./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-881%5B0%5D.rules%5B0%5D.use%5B0%5D!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue":
/*!*************************************************************************************************************************!*\
  !*** ./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue ***!
  \*************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _LinkedInActivityContentsTree_vue_vue_type_template_id_41b563fa___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./LinkedInActivityContentsTree.vue?vue&type=template&id=41b563fa& */ \"./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue?vue&type=template&id=41b563fa&\");\n/* harmony import */ var _LinkedInActivityContentsTree_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./LinkedInActivityContentsTree.vue?vue&type=script&lang=js& */ \"./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue?vue&type=script&lang=js&\");\n/* harmony import */ var _LinkedInActivityContentsTree_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./LinkedInActivityContentsTree.vue?vue&type=style&index=0&lang=scss& */ \"./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue?vue&type=style&index=0&lang=scss&\");\n/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ \"./node_modules/vue-loader/lib/runtime/componentNormalizer.js\");\n\n\n\n;\n\n\n/* normalize component */\n\nvar component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__[\"default\"])(\n  _LinkedInActivityContentsTree_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__[\"default\"],\n  _LinkedInActivityContentsTree_vue_vue_type_template_id_41b563fa___WEBPACK_IMPORTED_MODULE_0__.render,\n  _LinkedInActivityContentsTree_vue_vue_type_template_id_41b563fa___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,\n  false,\n  null,\n  null,\n  null\n  \n)\n\ncomponent.options.__hasBlocks = {\"script\":true,\"template\":true};\n/* hot reload */\nif (false) { var api; }\ncomponent.options.__file = \"client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue\"\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);\n\n//# sourceURL=webpack:///./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue?");

/***/ }),

/***/ "./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue":
/*!*****************************************************************************************!*\
  !*** ./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue ***!
  \*****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _ActivityView_vue_vue_type_template_id_1c8c7495___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ActivityView.vue?vue&type=template&id=1c8c7495& */ \"./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?vue&type=template&id=1c8c7495&\");\n/* harmony import */ var _ActivityView_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ActivityView.vue?vue&type=script&lang=js& */ \"./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?vue&type=script&lang=js&\");\n/* harmony import */ var _ActivityView_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./ActivityView.vue?vue&type=style&index=0&lang=scss& */ \"./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?vue&type=style&index=0&lang=scss&\");\n/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ \"./node_modules/vue-loader/lib/runtime/componentNormalizer.js\");\n/* harmony import */ var _ActivityView_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./ActivityView.vue?vue&type=custom&index=0&blockType=lang-strings */ \"./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?vue&type=custom&index=0&blockType=lang-strings\");\n\n\n\n;\n\n\n/* normalize component */\n\nvar component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__[\"default\"])(\n  _ActivityView_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__[\"default\"],\n  _ActivityView_vue_vue_type_template_id_1c8c7495___WEBPACK_IMPORTED_MODULE_0__.render,\n  _ActivityView_vue_vue_type_template_id_1c8c7495___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,\n  false,\n  null,\n  null,\n  null\n  \n)\n\n/* custom blocks */\n;\nif (typeof _ActivityView_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_4__[\"default\"] === 'function') (0,_ActivityView_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_4__[\"default\"])(component)\n\ncomponent.options.__hasBlocks = {\"script\":true,\"template\":true};\n/* hot reload */\nif (false) { var api; }\ncomponent.options.__file = \"client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue\"\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);\n\n//# sourceURL=webpack:///./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?");

/***/ }),

/***/ "./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue?vue&type=script&lang=js&":
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var tui_components_tree_Tree__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! tui/components/tree/Tree */ \"tui/components/tree/Tree\");\n/* harmony import */ var tui_components_tree_Tree__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(tui_components_tree_Tree__WEBPACK_IMPORTED_MODULE_0__);\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n\n\n\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({\n  components: {\n    Tree: (tui_components_tree_Tree__WEBPACK_IMPORTED_MODULE_0___default()),\n  },\n\n  props: {\n    /**\n     * Tree data for contents\n     */\n    treeData: {\n      type: Array,\n      required: true,\n    },\n    /**\n     * List of open branches\n     */\n    value: {\n      type: Array,\n      required: true,\n    },\n  },\n\n  data() {\n    return {\n      open: this.value,\n    };\n  },\n});\n\n\n//# sourceURL=webpack:///./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue?./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet%5B1%5D.rules%5B3%5D.use%5B0%5D");

/***/ }),

/***/ "./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var tui_components_card_ActionCard__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! tui/components/card/ActionCard */ \"tui/components/card/ActionCard\");\n/* harmony import */ var tui_components_card_ActionCard__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(tui_components_card_ActionCard__WEBPACK_IMPORTED_MODULE_0__);\n/* harmony import */ var tui_components_settings_navigation_SettingsNavigation__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! tui/components/settings_navigation/SettingsNavigation */ \"tui/components/settings_navigation/SettingsNavigation\");\n/* harmony import */ var tui_components_settings_navigation_SettingsNavigation__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(tui_components_settings_navigation_SettingsNavigation__WEBPACK_IMPORTED_MODULE_1__);\n/* harmony import */ var tui_components_buttons_Button__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! tui/components/buttons/Button */ \"tui/components/buttons/Button\");\n/* harmony import */ var tui_components_buttons_Button__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(tui_components_buttons_Button__WEBPACK_IMPORTED_MODULE_2__);\n/* harmony import */ var mod_contentmarketplace_components_layouts_LayoutBannerTwoColumn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! mod_contentmarketplace/components/layouts/LayoutBannerTwoColumn */ \"mod_contentmarketplace/components/layouts/LayoutBannerTwoColumn\");\n/* harmony import */ var mod_contentmarketplace_components_layouts_LayoutBannerTwoColumn__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(mod_contentmarketplace_components_layouts_LayoutBannerTwoColumn__WEBPACK_IMPORTED_MODULE_3__);\n/* harmony import */ var tui_components_lozenge_Lozenge__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! tui/components/lozenge/Lozenge */ \"tui/components/lozenge/Lozenge\");\n/* harmony import */ var tui_components_lozenge_Lozenge__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(tui_components_lozenge_Lozenge__WEBPACK_IMPORTED_MODULE_4__);\n/* harmony import */ var tui_components_notifications_NotificationBanner__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! tui/components/notifications/NotificationBanner */ \"tui/components/notifications/NotificationBanner\");\n/* harmony import */ var tui_components_notifications_NotificationBanner__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(tui_components_notifications_NotificationBanner__WEBPACK_IMPORTED_MODULE_5__);\n/* harmony import */ var tui_components_layouts_PageBackLink__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! tui/components/layouts/PageBackLink */ \"tui/components/layouts/PageBackLink\");\n/* harmony import */ var tui_components_layouts_PageBackLink__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(tui_components_layouts_PageBackLink__WEBPACK_IMPORTED_MODULE_6__);\n/* harmony import */ var tui_components_progress_Progress__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! tui/components/progress/Progress */ \"tui/components/progress/Progress\");\n/* harmony import */ var tui_components_progress_Progress__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(tui_components_progress_Progress__WEBPACK_IMPORTED_MODULE_7__);\n/* harmony import */ var tui_components_toggle_ToggleSwitch__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! tui/components/toggle/ToggleSwitch */ \"tui/components/toggle/ToggleSwitch\");\n/* harmony import */ var tui_components_toggle_ToggleSwitch__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(tui_components_toggle_ToggleSwitch__WEBPACK_IMPORTED_MODULE_8__);\n/* harmony import */ var tui_notifications__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! tui/notifications */ \"tui/notifications\");\n/* harmony import */ var tui_notifications__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(tui_notifications__WEBPACK_IMPORTED_MODULE_9__);\n/* harmony import */ var mod_contentmarketplace_constants__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! mod_contentmarketplace/constants */ \"mod_contentmarketplace/constants\");\n/* harmony import */ var mod_contentmarketplace_constants__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(mod_contentmarketplace_constants__WEBPACK_IMPORTED_MODULE_10__);\n/* harmony import */ var contentmarketplaceactivity_linkedin_graphql_linkedin_activity__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! contentmarketplaceactivity_linkedin/graphql/linkedin_activity */ \"./server/mod/contentmarketplace/contentmarketplaces/linkedin/webapi/ajax/linkedin_activity.graphql\");\n/* harmony import */ var mod_contentmarketplace_graphql_set_self_completion__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! mod_contentmarketplace/graphql/set_self_completion */ \"./server/mod/contentmarketplace/webapi/ajax/set_self_completion.graphql\");\n/* harmony import */ var mod_contentmarketplace_graphql_request_non_interactive_enrol__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! mod_contentmarketplace/graphql/request_non_interactive_enrol */ \"./server/mod/contentmarketplace/webapi/ajax/request_non_interactive_enrol.graphql\");\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n//\n\n\n\n\n\n\n\n\n\n\n// Utils\n\n\n\n// GraphQL\n\n\n\n\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({\n  components: {\n    ActionCard: (tui_components_card_ActionCard__WEBPACK_IMPORTED_MODULE_0___default()),\n    AdminMenu: (tui_components_settings_navigation_SettingsNavigation__WEBPACK_IMPORTED_MODULE_1___default()),\n    Button: (tui_components_buttons_Button__WEBPACK_IMPORTED_MODULE_2___default()),\n    Layout: (mod_contentmarketplace_components_layouts_LayoutBannerTwoColumn__WEBPACK_IMPORTED_MODULE_3___default()),\n    Lozenge: (tui_components_lozenge_Lozenge__WEBPACK_IMPORTED_MODULE_4___default()),\n    NotificationBanner: (tui_components_notifications_NotificationBanner__WEBPACK_IMPORTED_MODULE_5___default()),\n    PageBackLink: (tui_components_layouts_PageBackLink__WEBPACK_IMPORTED_MODULE_6___default()),\n    Progress: (tui_components_progress_Progress__WEBPACK_IMPORTED_MODULE_7___default()),\n    ToggleSwitch: (tui_components_toggle_ToggleSwitch__WEBPACK_IMPORTED_MODULE_8___default()),\n  },\n\n  props: {\n    /**\n     * The course's module id, not the content marketplace id.\n     */\n    cmId: {\n      type: Number,\n      required: true,\n    },\n\n    /**\n     * Check it has notification or not.\n     */\n    hasNotification: {\n      type: Boolean,\n      required: true,\n    },\n  },\n\n  data() {\n    return {\n      setCompletion: false,\n\n      // We need to store the initial states of the query data state here to ensure\n      // Vue watches them and updates the DOM accordingly when the query gets updated.\n      interactor: {\n        can_enrol: false,\n        can_launch: false,\n        has_view_capability: false,\n        is_enrolled: false,\n        is_site_guest: false,\n        non_interactive_enrol_instance_enabled: false,\n        supports_non_interactive_enrol: false,\n      },\n      module: {\n        completionstatus: mod_contentmarketplace_constants__WEBPACK_IMPORTED_MODULE_10__.COMPLETION_STATUS_UNKNOWN,\n        rpl: false,\n      },\n    };\n  },\n\n  computed: {\n    isProgressBarEnabled() {\n      return this.completionMarketplace && !this.selfCompletionEnabled;\n    },\n    canEnrol() {\n      return (\n        this.interactor.can_enrol &&\n        !this.interactor.is_site_guest &&\n        this.interactor.non_interactive_enrol_instance_enabled\n      );\n    },\n\n    canLaunch() {\n      if (this.interactor.can_enrol) {\n        return false;\n      }\n      return this.interactor.can_launch || this.interactor.is_site_guest;\n    },\n\n    enrolBannerText() {\n      if (this.interactor.has_view_capability) {\n        return this.interactor.non_interactive_enrol_instance_enabled\n          ? this.$str('viewing_as_enrollable_admin', 'mod_contentmarketplace')\n          : this.$str(\n              'viewing_as_enrollable_admin_self_enrol_disabled',\n              'mod_contentmarketplace'\n            );\n      }\n\n      return this.canEnrol\n        ? this.$str('viewing_as_enrollable_guest', 'mod_contentmarketplace')\n        : this.$str('viewing_as_guest', 'mod_contentmarketplace');\n    },\n\n    isActivityCompleted() {\n      return (\n        this.module.completionstatus !== mod_contentmarketplace_constants__WEBPACK_IMPORTED_MODULE_10__.COMPLETION_STATUS_UNKNOWN &&\n        this.module.completionstatus !== mod_contentmarketplace_constants__WEBPACK_IMPORTED_MODULE_10__.COMPLETION_STATUS_INCOMPLETE\n      );\n    },\n\n    completionEnabled() {\n      return this.module.completion !== mod_contentmarketplace_constants__WEBPACK_IMPORTED_MODULE_10__.COMPLETION_TRACKING_NONE;\n    },\n\n    selfCompletionEnabled() {\n      return this.module.completion === mod_contentmarketplace_constants__WEBPACK_IMPORTED_MODULE_10__.COMPLETION_TRACKING_MANUAL;\n    },\n\n    completionMarketplace() {\n      return (\n        this.activity.completion_condition ===\n        mod_contentmarketplace_constants__WEBPACK_IMPORTED_MODULE_10__.COMPLETION_CONDITION_CONTENT_MARKETPLACE\n      );\n    },\n\n    getProgress() {\n      if (this.module.progress !== 100 && this.isActivityCompleted) {\n        return 100;\n      }\n      return this.module.progress;\n    },\n  },\n\n  mounted() {\n    if (this.hasNotification) {\n      (0,tui_notifications__WEBPACK_IMPORTED_MODULE_9__.notify)({\n        message: this.$str('enrol_success_message', 'mod_contentmarketplace'),\n        type: 'success',\n      });\n    }\n  },\n\n  apollo: {\n    activity: {\n      query: contentmarketplaceactivity_linkedin_graphql_linkedin_activity__WEBPACK_IMPORTED_MODULE_11__[\"default\"],\n      variables() {\n        return {\n          cm_id: this.cmId,\n        };\n      },\n      update({ instance: data }) {\n        const activity = data.module;\n        this.course = activity.course;\n        this.interactor = activity.interactor;\n        this.learningObject = data.learning_object;\n        this.module = activity.course_module;\n        this.setCompletion = this.isActivityCompleted;\n        return activity;\n      },\n    },\n  },\n\n  methods: {\n    async launch() {\n      const url = this.learningObject.sso_launch_url\n        ? this.learningObject.sso_launch_url\n        : this.learningObject.web_launch_url;\n      window.open(url, 'linkedIn_course_window');\n    },\n\n    async setCompletionHandler() {\n      await this.$apollo.mutate({\n        mutation: mod_contentmarketplace_graphql_set_self_completion__WEBPACK_IMPORTED_MODULE_12__[\"default\"],\n        refetchAll: false,\n        variables: {\n          cm_id: this.cmId,\n          status: this.setCompletion,\n        },\n      });\n      this.$apollo.queries.activity.refetch();\n    },\n\n    async enrol() {\n      if (this.interactor.supports_non_interactive_enrol) {\n        await this.nonInteractiveEnrol();\n      } else {\n        window.location.href = this.$url('/enrol/index.php', {\n          id: this.course.id,\n        });\n      }\n    },\n\n    async nonInteractiveEnrol() {\n      let {\n        data: { result },\n      } = await this.$apollo.mutate({\n        mutation: mod_contentmarketplace_graphql_request_non_interactive_enrol__WEBPACK_IMPORTED_MODULE_13__[\"default\"],\n        variables: { cm_id: this.cmId },\n        refetchAll: true,\n      });\n\n      if (result) {\n        (0,tui_notifications__WEBPACK_IMPORTED_MODULE_9__.notify)({\n          message: this.$str('enrol_success_message', 'mod_contentmarketplace'),\n          type: 'success',\n        });\n      }\n    },\n  },\n});\n\n\n//# sourceURL=webpack:///./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet%5B1%5D.rules%5B3%5D.use%5B0%5D");

/***/ }),

/***/ "./node_modules/mini-css-extract-plugin/dist/loader.js??clonedRuleSet-878[0].rules[0].use[0]!./client/tooling/webpack/css_raw_loader.js??clonedRuleSet-878[0].rules[0].use[1]!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-878[0].rules[0].use[2]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue?vue&type=style&index=0&lang=scss&":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/mini-css-extract-plugin/dist/loader.js??clonedRuleSet-878[0].rules[0].use[0]!./client/tooling/webpack/css_raw_loader.js??clonedRuleSet-878[0].rules[0].use[1]!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-878[0].rules[0].use[2]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue?vue&type=style&index=0&lang=scss& ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (() => {

eval("// extracted by mini-css-extract-plugin\n\n//# sourceURL=webpack:///./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue?./node_modules/mini-css-extract-plugin/dist/loader.js??clonedRuleSet-878%5B0%5D.rules%5B0%5D.use%5B0%5D!./client/tooling/webpack/css_raw_loader.js??clonedRuleSet-878%5B0%5D.rules%5B0%5D.use%5B1%5D!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-878%5B0%5D.rules%5B0%5D.use%5B2%5D!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "./node_modules/mini-css-extract-plugin/dist/loader.js??clonedRuleSet-878[0].rules[0].use[0]!./client/tooling/webpack/css_raw_loader.js??clonedRuleSet-878[0].rules[0].use[1]!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-878[0].rules[0].use[2]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?vue&type=style&index=0&lang=scss&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/mini-css-extract-plugin/dist/loader.js??clonedRuleSet-878[0].rules[0].use[0]!./client/tooling/webpack/css_raw_loader.js??clonedRuleSet-878[0].rules[0].use[1]!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-878[0].rules[0].use[2]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?vue&type=style&index=0&lang=scss& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (() => {

eval("// extracted by mini-css-extract-plugin\n\n//# sourceURL=webpack:///./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?./node_modules/mini-css-extract-plugin/dist/loader.js??clonedRuleSet-878%5B0%5D.rules%5B0%5D.use%5B0%5D!./client/tooling/webpack/css_raw_loader.js??clonedRuleSet-878%5B0%5D.rules%5B0%5D.use%5B1%5D!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-878%5B0%5D.rules%5B0%5D.use%5B2%5D!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js":
/*!********************************************************************!*\
  !*** ./node_modules/vue-loader/lib/runtime/componentNormalizer.js ***!
  \********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (/* binding */ normalizeComponent)\n/* harmony export */ });\n/* globals __VUE_SSR_CONTEXT__ */\n\n// IMPORTANT: Do NOT use ES2015 features in this file (except for modules).\n// This module is a runtime utility for cleaner component module output and will\n// be included in the final webpack user bundle.\n\nfunction normalizeComponent (\n  scriptExports,\n  render,\n  staticRenderFns,\n  functionalTemplate,\n  injectStyles,\n  scopeId,\n  moduleIdentifier, /* server only */\n  shadowMode /* vue-cli only */\n) {\n  // Vue.extend constructor export interop\n  var options = typeof scriptExports === 'function'\n    ? scriptExports.options\n    : scriptExports\n\n  // render functions\n  if (render) {\n    options.render = render\n    options.staticRenderFns = staticRenderFns\n    options._compiled = true\n  }\n\n  // functional template\n  if (functionalTemplate) {\n    options.functional = true\n  }\n\n  // scopedId\n  if (scopeId) {\n    options._scopeId = 'data-v-' + scopeId\n  }\n\n  var hook\n  if (moduleIdentifier) { // server build\n    hook = function (context) {\n      // 2.3 injection\n      context =\n        context || // cached call\n        (this.$vnode && this.$vnode.ssrContext) || // stateful\n        (this.parent && this.parent.$vnode && this.parent.$vnode.ssrContext) // functional\n      // 2.2 with runInNewContext: true\n      if (!context && typeof __VUE_SSR_CONTEXT__ !== 'undefined') {\n        context = __VUE_SSR_CONTEXT__\n      }\n      // inject component styles\n      if (injectStyles) {\n        injectStyles.call(this, context)\n      }\n      // register component module identifier for async chunk inferrence\n      if (context && context._registeredComponents) {\n        context._registeredComponents.add(moduleIdentifier)\n      }\n    }\n    // used by ssr in case component is cached and beforeCreate\n    // never gets called\n    options._ssrRegister = hook\n  } else if (injectStyles) {\n    hook = shadowMode\n      ? function () {\n        injectStyles.call(\n          this,\n          (options.functional ? this.parent : this).$root.$options.shadowRoot\n        )\n      }\n      : injectStyles\n  }\n\n  if (hook) {\n    if (options.functional) {\n      // for template-only hot-reload because in that case the render fn doesn't\n      // go through the normalizer\n      options._injectStyles = hook\n      // register for functional component in vue file\n      var originalRender = options.render\n      options.render = function renderWithStyleInjection (h, context) {\n        hook.call(context)\n        return originalRender(h, context)\n      }\n    } else {\n      // inject component registration as beforeCreate hook\n      var existing = options.beforeCreate\n      options.beforeCreate = existing\n        ? [].concat(existing, hook)\n        : [hook]\n    }\n  }\n\n  return {\n    exports: scriptExports,\n    options: options\n  }\n}\n\n\n//# sourceURL=webpack:///./node_modules/vue-loader/lib/runtime/componentNormalizer.js?");

/***/ }),

/***/ "./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?vue&type=custom&index=0&blockType=lang-strings":
/*!****************************************************************************************************************************************!*\
  !*** ./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?vue&type=custom&index=0&blockType=lang-strings ***!
  \****************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _tooling_webpack_tui_lang_strings_loader_js_clonedRuleSet_881_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ActivityView_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-881[0].rules[0].use[0]!../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ActivityView.vue?vue&type=custom&index=0&blockType=lang-strings */ \"./client/tooling/webpack/tui_lang_strings_loader.js??clonedRuleSet-881[0].rules[0].use[0]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?vue&type=custom&index=0&blockType=lang-strings\");\n /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_tooling_webpack_tui_lang_strings_loader_js_clonedRuleSet_881_0_rules_0_use_0_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ActivityView_vue_vue_type_custom_index_0_blockType_lang_strings__WEBPACK_IMPORTED_MODULE_0__[\"default\"]); \n\n//# sourceURL=webpack:///./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?");

/***/ }),

/***/ "./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue?vue&type=template&id=41b563fa&":
/*!********************************************************************************************************************************************************!*\
  !*** ./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue?vue&type=template&id=41b563fa& ***!
  \********************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_LinkedInActivityContentsTree_vue_vue_type_template_id_41b563fa___WEBPACK_IMPORTED_MODULE_0__.render),\n/* harmony export */   \"staticRenderFns\": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_LinkedInActivityContentsTree_vue_vue_type_template_id_41b563fa___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)\n/* harmony export */ });\n/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_LinkedInActivityContentsTree_vue_vue_type_template_id_41b563fa___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LinkedInActivityContentsTree.vue?vue&type=template&id=41b563fa& */ \"./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue?vue&type=template&id=41b563fa&\");\n\n\n//# sourceURL=webpack:///./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue?");

/***/ }),

/***/ "./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?vue&type=template&id=1c8c7495&":
/*!************************************************************************************************************************!*\
  !*** ./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?vue&type=template&id=1c8c7495& ***!
  \************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ActivityView_vue_vue_type_template_id_1c8c7495___WEBPACK_IMPORTED_MODULE_0__.render),\n/* harmony export */   \"staticRenderFns\": () => (/* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ActivityView_vue_vue_type_template_id_1c8c7495___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)\n/* harmony export */ });\n/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ActivityView_vue_vue_type_template_id_1c8c7495___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ActivityView.vue?vue&type=template&id=1c8c7495& */ \"./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?vue&type=template&id=1c8c7495&\");\n\n\n//# sourceURL=webpack:///./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?");

/***/ }),

/***/ "./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue?vue&type=script&lang=js&":
/*!**************************************************************************************************************************************************!*\
  !*** ./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_LinkedInActivityContentsTree_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!../../../../../../node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./LinkedInActivityContentsTree.vue?vue&type=script&lang=js& */ \"./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue?vue&type=script&lang=js&\");\n /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_LinkedInActivityContentsTree_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__[\"default\"]); \n\n//# sourceURL=webpack:///./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue?");

/***/ }),

/***/ "./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?vue&type=script&lang=js&":
/*!******************************************************************************************************************!*\
  !*** ./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?vue&type=script&lang=js& ***!
  \******************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_ActivityView_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!../../../../../node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./ActivityView.vue?vue&type=script&lang=js& */ \"./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/source-map-loader/dist/cjs.js??ruleSet[1].rules[3].use[0]!./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?vue&type=script&lang=js&\");\n /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_source_map_loader_dist_cjs_js_ruleSet_1_rules_3_use_0_ActivityView_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__[\"default\"]); \n\n//# sourceURL=webpack:///./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?");

/***/ }),

/***/ "./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue?vue&type=style&index=0&lang=scss&":
/*!***********************************************************************************************************************************************************!*\
  !*** ./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue?vue&type=style&index=0&lang=scss& ***!
  \***********************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_clonedRuleSet_878_0_rules_0_use_0_tooling_webpack_css_raw_loader_js_clonedRuleSet_878_0_rules_0_use_1_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_878_0_rules_0_use_2_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_LinkedInActivityContentsTree_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/mini-css-extract-plugin/dist/loader.js??clonedRuleSet-878[0].rules[0].use[0]!../../../../../tooling/webpack/css_raw_loader.js??clonedRuleSet-878[0].rules[0].use[1]!../../../../../../node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-878[0].rules[0].use[2]!../../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LinkedInActivityContentsTree.vue?vue&type=style&index=0&lang=scss& */ \"./node_modules/mini-css-extract-plugin/dist/loader.js??clonedRuleSet-878[0].rules[0].use[0]!./client/tooling/webpack/css_raw_loader.js??clonedRuleSet-878[0].rules[0].use[1]!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-878[0].rules[0].use[2]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue?vue&type=style&index=0&lang=scss&\");\n/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_clonedRuleSet_878_0_rules_0_use_0_tooling_webpack_css_raw_loader_js_clonedRuleSet_878_0_rules_0_use_1_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_878_0_rules_0_use_2_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_LinkedInActivityContentsTree_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_mini_css_extract_plugin_dist_loader_js_clonedRuleSet_878_0_rules_0_use_0_tooling_webpack_css_raw_loader_js_clonedRuleSet_878_0_rules_0_use_1_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_878_0_rules_0_use_2_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_LinkedInActivityContentsTree_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);\n/* harmony reexport (unknown) */ var __WEBPACK_REEXPORT_OBJECT__ = {};\n/* harmony reexport (unknown) */ for(const __WEBPACK_IMPORT_KEY__ in _node_modules_mini_css_extract_plugin_dist_loader_js_clonedRuleSet_878_0_rules_0_use_0_tooling_webpack_css_raw_loader_js_clonedRuleSet_878_0_rules_0_use_1_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_878_0_rules_0_use_2_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_LinkedInActivityContentsTree_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__) if(__WEBPACK_IMPORT_KEY__ !== \"default\") __WEBPACK_REEXPORT_OBJECT__[__WEBPACK_IMPORT_KEY__] = () => _node_modules_mini_css_extract_plugin_dist_loader_js_clonedRuleSet_878_0_rules_0_use_0_tooling_webpack_css_raw_loader_js_clonedRuleSet_878_0_rules_0_use_1_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_878_0_rules_0_use_2_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_LinkedInActivityContentsTree_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__[__WEBPACK_IMPORT_KEY__]\n/* harmony reexport (unknown) */ __webpack_require__.d(__webpack_exports__, __WEBPACK_REEXPORT_OBJECT__);\n /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((_node_modules_mini_css_extract_plugin_dist_loader_js_clonedRuleSet_878_0_rules_0_use_0_tooling_webpack_css_raw_loader_js_clonedRuleSet_878_0_rules_0_use_1_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_878_0_rules_0_use_2_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_LinkedInActivityContentsTree_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default())); \n\n//# sourceURL=webpack:///./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue?");

/***/ }),

/***/ "./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?vue&type=style&index=0&lang=scss&":
/*!***************************************************************************************************************************!*\
  !*** ./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?vue&type=style&index=0&lang=scss& ***!
  \***************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_clonedRuleSet_878_0_rules_0_use_0_tooling_webpack_css_raw_loader_js_clonedRuleSet_878_0_rules_0_use_1_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_878_0_rules_0_use_2_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ActivityView_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/mini-css-extract-plugin/dist/loader.js??clonedRuleSet-878[0].rules[0].use[0]!../../../../tooling/webpack/css_raw_loader.js??clonedRuleSet-878[0].rules[0].use[1]!../../../../../node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-878[0].rules[0].use[2]!../../../../tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ActivityView.vue?vue&type=style&index=0&lang=scss& */ \"./node_modules/mini-css-extract-plugin/dist/loader.js??clonedRuleSet-878[0].rules[0].use[0]!./client/tooling/webpack/css_raw_loader.js??clonedRuleSet-878[0].rules[0].use[1]!./node_modules/postcss-loader/dist/cjs.js??clonedRuleSet-878[0].rules[0].use[2]!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?vue&type=style&index=0&lang=scss&\");\n/* harmony import */ var _node_modules_mini_css_extract_plugin_dist_loader_js_clonedRuleSet_878_0_rules_0_use_0_tooling_webpack_css_raw_loader_js_clonedRuleSet_878_0_rules_0_use_1_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_878_0_rules_0_use_2_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ActivityView_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_mini_css_extract_plugin_dist_loader_js_clonedRuleSet_878_0_rules_0_use_0_tooling_webpack_css_raw_loader_js_clonedRuleSet_878_0_rules_0_use_1_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_878_0_rules_0_use_2_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ActivityView_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);\n/* harmony reexport (unknown) */ var __WEBPACK_REEXPORT_OBJECT__ = {};\n/* harmony reexport (unknown) */ for(const __WEBPACK_IMPORT_KEY__ in _node_modules_mini_css_extract_plugin_dist_loader_js_clonedRuleSet_878_0_rules_0_use_0_tooling_webpack_css_raw_loader_js_clonedRuleSet_878_0_rules_0_use_1_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_878_0_rules_0_use_2_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ActivityView_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__) if(__WEBPACK_IMPORT_KEY__ !== \"default\") __WEBPACK_REEXPORT_OBJECT__[__WEBPACK_IMPORT_KEY__] = () => _node_modules_mini_css_extract_plugin_dist_loader_js_clonedRuleSet_878_0_rules_0_use_0_tooling_webpack_css_raw_loader_js_clonedRuleSet_878_0_rules_0_use_1_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_878_0_rules_0_use_2_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ActivityView_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__[__WEBPACK_IMPORT_KEY__]\n/* harmony reexport (unknown) */ __webpack_require__.d(__webpack_exports__, __WEBPACK_REEXPORT_OBJECT__);\n /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((_node_modules_mini_css_extract_plugin_dist_loader_js_clonedRuleSet_878_0_rules_0_use_0_tooling_webpack_css_raw_loader_js_clonedRuleSet_878_0_rules_0_use_1_node_modules_postcss_loader_dist_cjs_js_clonedRuleSet_878_0_rules_0_use_2_tooling_webpack_tui_vue_loader_js_ruleSet_0_rules_0_use_0_node_modules_vue_loader_lib_index_js_vue_loader_options_ActivityView_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default())); \n\n//# sourceURL=webpack:///./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?");

/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue?vue&type=template&id=41b563fa&":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue?vue&type=template&id=41b563fa& ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": () => (/* binding */ render),\n/* harmony export */   \"staticRenderFns\": () => (/* binding */ staticRenderFns)\n/* harmony export */ });\nvar render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return _c('Tree',{staticClass:\"tui-linkedinActivityContentTree\",attrs:{\"tree-data\":_vm.treeData},on:{\"input\":function($event){return _vm.$emit('input', $event)}},scopedSlots:_vm._u([{key:\"custom-label\",fn:function(ref){\nvar label = ref.label;\nreturn [_vm._v(\"\\n    \"+_vm._s(label)+\"\\n  \")]}},{key:\"content\",fn:function(ref){\nvar content = ref.content;\nreturn [_c('div',{staticClass:\"tui-linkedinActivityContentTree__contents\"},[_vm._l((content.items),function(item,i){return [_c('div',{key:i,staticClass:\"tui-linkedinActivityContentTree__contents-item\"},[_vm._v(\"\\n          \"+_vm._s(item)+\"\\n        \")])]})],2)]}}]),model:{value:(_vm.open),callback:function ($$v) {_vm.open=$$v},expression:\"open\"}})}\nvar staticRenderFns = []\nrender._withStripped = true\n\n\n//# sourceURL=webpack:///./client/component/contentmarketplaceactivity_linkedin/src/components/side-panel/LinkedInActivityContentsTree.vue?./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?vue&type=template&id=1c8c7495&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet[0].rules[0].use[0]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?vue&type=template&id=1c8c7495& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"render\": () => (/* binding */ render),\n/* harmony export */   \"staticRenderFns\": () => (/* binding */ staticRenderFns)\n/* harmony export */ });\nvar render = function () {var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;return (_vm.activity)?_c('Layout',{staticClass:\"tui-linkedinActivity\",attrs:{\"banner-image-url\":_vm.course.image,\"loading-full-page\":_vm.$apollo.loading,\"title\":_vm.activity.name},scopedSlots:_vm._u([(_vm.course.course_format.has_course_view_page)?{key:\"content-nav\",fn:function(){return [_c('PageBackLink',{attrs:{\"link\":_vm.course.url,\"text\":_vm.course.fullname}})]},proxy:true}:null,{key:\"banner-content\",fn:function(ref){\nvar stacked = ref.stacked;\nreturn [_c('div',{staticClass:\"tui-linkedinActivity__admin\"},[_c('AdminMenu',{attrs:{\"stacked-layout\":stacked}})],1)]}},(!_vm.interactor.is_enrolled)?{key:\"feedback-banner\",fn:function(){return [_c('NotificationBanner',{attrs:{\"type\":\"info\"},scopedSlots:_vm._u([{key:\"body\",fn:function(){return [_c('ActionCard',{attrs:{\"no-border\":true},scopedSlots:_vm._u([{key:\"card-body\",fn:function(){return [_vm._v(\"\\n            \"+_vm._s(_vm.enrolBannerText)+\"\\n          \")]},proxy:true},(_vm.canEnrol)?{key:\"card-action\",fn:function(){return [_c('Button',{attrs:{\"styleclass\":{ primary: 'true' },\"title\":_vm.$str(\n                  'enrol_to_course',\n                  'mod_contentmarketplace',\n                  _vm.course.fullname\n                ),\"text\":_vm.$str('enrol', 'core_enrol')},on:{\"click\":_vm.enrol}})]},proxy:true}:null],null,true)})]},proxy:true}],null,false,4199808284)})]},proxy:true}:null,{key:\"main-content\",fn:function(){return [_c('div',{staticClass:\"tui-linkedinActivity__body\"},[_c('Button',{attrs:{\"disabled\":!_vm.canLaunch,\"styleclass\":{ primary: 'true' },\"text\":_vm.$str('launch', 'mod_contentmarketplace')},on:{\"click\":_vm.launch}}),_vm._v(\" \"),_c('hr',{staticClass:\"tui-linkedinActivity__divider\"}),_vm._v(\" \"),(_vm.completionEnabled && _vm.interactor.is_enrolled)?_c('div',{staticClass:\"tui-linkedinActivity__status\",class:{\n          'tui-linkedinActivity__progressContainer': _vm.isProgressBarEnabled,\n        }},[_c('div',{staticClass:\"tui-linkedinActivity__status-completion\"},[_c('Lozenge',{attrs:{\"text\":_vm.isActivityCompleted\n                ? _vm.$str('activity_status_completed', 'mod_contentmarketplace')\n                : _vm.$str(\n                    'activity_status_not_completed',\n                    'mod_contentmarketplace'\n                  )}})],1),_vm._v(\" \"),(_vm.isProgressBarEnabled)?_c('Progress',{staticClass:\"tui-linkedinActivity__status-progress\",attrs:{\"value\":_vm.getProgress}}):_vm._e(),_vm._v(\" \"),(_vm.selfCompletionEnabled && !_vm.module.rpl)?_c('ToggleSwitch',{staticClass:\"tui-linkedinActivity__status-toggle\",attrs:{\"text\":_vm.$str('activity_set_self_completion', 'mod_contentmarketplace'),\"toggle-first\":true},on:{\"input\":_vm.setCompletionHandler},model:{value:(_vm.setCompletion),callback:function ($$v) {_vm.setCompletion=$$v},expression:\"setCompletion\"}}):_vm._e()],1):_vm._e()],1),_vm._v(\" \"),_c('div',{staticClass:\"tui-linkedinActivity__details\"},[_c('h3',{staticClass:\"tui-linkedinActivity__details-header\"},[_vm._v(\"\\n        \"+_vm._s(_vm.$str('course_details', 'mod_contentmarketplace'))+\"\\n      \")]),_vm._v(\" \"),_c('div',{staticClass:\"tui-linkedinActivity__details-content\"},[_c('div',{staticClass:\"tui-linkedinActivity__details-bar\"},[_c('div',[_c('span',{staticClass:\"sr-only\"},[_vm._v(\"\\n              \"+_vm._s(_vm.$str(\n                  'a11y_activity_time_to_complete',\n                  'mod_contentmarketplace'\n                ))+\"\\n            \")]),_vm._v(\"\\n            \"+_vm._s(_vm.learningObject.time_to_complete)+\"\\n          \")]),_vm._v(\" \"),_c('div',[_c('span',{staticClass:\"sr-only\"},[_vm._v(\"\\n              \"+_vm._s(_vm.$str('a11y_activity_difficulty', 'mod_contentmarketplace'))+\"\\n            \")]),_vm._v(\"\\n            \"+_vm._s(_vm.learningObject.display_level)+\"\\n          \")]),_vm._v(\" \"),_c('div',[_vm._v(\"\\n            \"+_vm._s(_vm.$str(\n                'updated_at',\n                'mod_contentmarketplace',\n                _vm.learningObject.last_updated_at\n              ))+\"\\n          \")])]),_vm._v(\" \"),_c('div',{staticClass:\"tui-linkedinActivity__details-desc\",domProps:{\"innerHTML\":_vm._s(_vm.activity.intro)}})])])]},proxy:true}],null,true)}):_vm._e()}\nvar staticRenderFns = []\nrender._withStripped = true\n\n\n//# sourceURL=webpack:///./client/component/contentmarketplaceactivity_linkedin/src/pages/ActivityView.vue?./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./client/tooling/webpack/tui_vue_loader.js??ruleSet%5B0%5D.rules%5B0%5D.use%5B0%5D!./node_modules/vue-loader/lib/index.js??vue-loader-options");

/***/ }),

/***/ "mod_contentmarketplace/components/layouts/LayoutBannerTwoColumn":
/*!***************************************************************************************************!*\
  !*** external "tui.require(\"mod_contentmarketplace/components/layouts/LayoutBannerTwoColumn\")" ***!
  \***************************************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("mod_contentmarketplace/components/layouts/LayoutBannerTwoColumn");

/***/ }),

/***/ "mod_contentmarketplace/constants":
/*!********************************************************************!*\
  !*** external "tui.require(\"mod_contentmarketplace/constants\")" ***!
  \********************************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("mod_contentmarketplace/constants");

/***/ }),

/***/ "tui/components/buttons/Button":
/*!*****************************************************************!*\
  !*** external "tui.require(\"tui/components/buttons/Button\")" ***!
  \*****************************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("tui/components/buttons/Button");

/***/ }),

/***/ "tui/components/card/ActionCard":
/*!******************************************************************!*\
  !*** external "tui.require(\"tui/components/card/ActionCard\")" ***!
  \******************************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("tui/components/card/ActionCard");

/***/ }),

/***/ "tui/components/layouts/PageBackLink":
/*!***********************************************************************!*\
  !*** external "tui.require(\"tui/components/layouts/PageBackLink\")" ***!
  \***********************************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("tui/components/layouts/PageBackLink");

/***/ }),

/***/ "tui/components/lozenge/Lozenge":
/*!******************************************************************!*\
  !*** external "tui.require(\"tui/components/lozenge/Lozenge\")" ***!
  \******************************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("tui/components/lozenge/Lozenge");

/***/ }),

/***/ "tui/components/notifications/NotificationBanner":
/*!***********************************************************************************!*\
  !*** external "tui.require(\"tui/components/notifications/NotificationBanner\")" ***!
  \***********************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("tui/components/notifications/NotificationBanner");

/***/ }),

/***/ "tui/components/progress/Progress":
/*!********************************************************************!*\
  !*** external "tui.require(\"tui/components/progress/Progress\")" ***!
  \********************************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("tui/components/progress/Progress");

/***/ }),

/***/ "tui/components/settings_navigation/SettingsNavigation":
/*!*****************************************************************************************!*\
  !*** external "tui.require(\"tui/components/settings_navigation/SettingsNavigation\")" ***!
  \*****************************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("tui/components/settings_navigation/SettingsNavigation");

/***/ }),

/***/ "tui/components/toggle/ToggleSwitch":
/*!**********************************************************************!*\
  !*** external "tui.require(\"tui/components/toggle/ToggleSwitch\")" ***!
  \**********************************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("tui/components/toggle/ToggleSwitch");

/***/ }),

/***/ "tui/components/tree/Tree":
/*!************************************************************!*\
  !*** external "tui.require(\"tui/components/tree/Tree\")" ***!
  \************************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("tui/components/tree/Tree");

/***/ }),

/***/ "tui/notifications":
/*!*****************************************************!*\
  !*** external "tui.require(\"tui/notifications\")" ***!
  \*****************************************************/
/***/ ((module) => {

"use strict";
module.exports = tui.require("tui/notifications");

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
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = __webpack_require__("./client/component/contentmarketplaceactivity_linkedin/src/tui.json");
/******/ 	
/******/ })()
;