require('../css/dawa.css');

const $ = require('jquery');
require('jquery-validation');

// IE 11 does not support Object.assign
require('es6-object-assign/auto');
const dawaAutocomplete = require('dawa-autocomplete2');

$(() => {
  let addressList = document.querySelectorAll('.field--type-pretix-field-type .js-dawa-element');
  for(let i = 0; i < addressList.length; i++) {
    let address = addressList[i];
    if (address !== null) {
      // Address autocomplete using https://dawa.aws.dk/.
      let addressWrapper = document.createElement('div');
      addressWrapper.setAttribute('class', 'dawa-autocomplete-container');
      address.parentNode.replaceChild(addressWrapper, address);
      addressWrapper.appendChild(address);

      dawaAutocomplete.dawaAutocomplete(address, {
        select: function (selected) {
          fetch(selected.data.href)
            .then(function (response) {
              return response.json()
            })
        }
      })
    }
  }
});
