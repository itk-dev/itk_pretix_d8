const $ = require('jquery');
require('jquery-validation');
import 'dawa-autocomplete2/css/dawa-autocomplete2.css';

// IE 11 does not support Object.assign
require('es6-object-assign/auto');
const dawaAutocomplete = require('dawa-autocomplete2');

const buildDawaAutocompleteElements = (context) => {
  const addresses = Array.from(context.querySelectorAll('.field--type-pretix-date .js-dawa-element'));
  addresses.forEach(address => {
    // Check if dawa autocomplete has already been initialized.
    if ($(address).closest('.dawa-autocomplete-container').length > 0) {
      return;
    }

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
  })
}

$(() => {
  Drupal.behaviors.itk_pretix_dawa = {
    attach: (context, settings) => {
      buildDawaAutocompleteElements(context);
    }
  }

  buildDawaAutocompleteElements(document)
});
