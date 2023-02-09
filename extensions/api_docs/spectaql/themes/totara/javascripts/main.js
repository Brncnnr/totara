// TOTARA: use shadow container instead of document
var container = document.getElementById('spectaql-shadow-container').shadowRoot
toggleMenu(container)
scrollSpy(container)
totara(container)