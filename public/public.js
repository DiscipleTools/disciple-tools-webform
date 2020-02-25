if ('serviceWorker' in navigator) {
  window.addEventListener('load', function() {
    navigator.serviceWorker.register('sw.js').then(function(registration) {
      // Registration was successful
      console.log('ServiceWorker registration successful with scope: ', registration.scope);
    }, function(err) {
      // registration failed :(
      console.log('ServiceWorker registration failed: ', err);
    });
  });
}

//check online status
window.addEventListener('online', checkStorage);
window.addEventListener('load', checkStorage);

//Get URL Parameters
let getUrlParameter = function getUrlParameter(sParam) {
    let sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
};

jQuery(document).ready(function() {
  if ( getUrlParameter('success') ) {
    jQuery('#report').empty().append( window.TRANSLATION.success + '<br>');
  }
})

function check_form() {
    let validator = jQuery('#contact-form').validate();
    let status = validator.form()
    if( status ) {
        get_data();
    }

}

function storeData(data) {
  // save data in localStorage

  if (typeof Storage !== 'undefined') {
    const formToken = new URLSearchParams(location.search).get('token');
    const time = + new Date()
    const key = `${formToken}_${time}`;

    localStorage.setItem(key, JSON.stringify(data));
    //reenable the submit button if the data is saved.
    document.querySelector('#submit-button').disabled = false;
    document.querySelector("#submit-button-container .spinner").remove()
    return true;
  }
  return false;
}

async function submit_form(data) {
  let url = get_url();
  console.log(data);
  return fetch(url + '/wp-json/dt-public/v1/webform/form_submit', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json;charset=utf-8'
    },
    body: data
  })
  .then((data) => {
    if (data.status == 200) {
      return data.status;
    } else {
      throw new Error(data.status);
    }
  })
  .catch((error) => {
    console.error('Error:', error);
  });
}

function get_data() {
  var submitButtonContainer = document.querySelector('#submit-button-container');
  var submitButton = document.querySelector('#submit-button');

  submitButton.disabled = true;
  submitButtonContainer.insertAdjacentHTML("beforeend", window.SETTINGS.spinner);
  let data = {};

  jQuery(':input:not([type=checkbox])').each(function() {
      data[this.name] = jQuery(this).val();
  });
  jQuery(':input[type=checkbox]:checked').each(function() {
    data[this.name] = jQuery(this).val();
  });

  if (!navigator.onLine) {
    // user is offline, store data locally
    const stored = storeData(data);
    let message = '<strong>You appear to be offline right now. </strong>';
    if (stored) {
      message += 'Your data was saved and will be submitted once you come back online.';
    }

    console.log(message);
    document.querySelector("form").reset();

    if (document.querySelector(".offlineMessage")) {
      document.querySelector(".offlineMessage").remove();
    }

    submitButtonContainer.insertAdjacentHTML("beforeend", `<div class="offlineMessage">${message}</div>`);

  } else {
    submit_form(JSON.stringify(data)).then((response) => {
      console.log(response);
      submitButton.disabled = false;
      document.querySelector("#submit-button-container .spinner").remove()
     });

  }
}

function get_url() {
    return window.location.protocol + '//' + window.location.hostname
}

async function checkStorage() {
  // check if we have saved data in localStorage
  console.log("checkStorage");
  if (typeof Storage !== 'undefined') {
    const token = new URLSearchParams(location.search).get('token');


    for (let i=0; i< localStorage.length; i++) {
      let key = localStorage.key(i);
      if (key.includes(token)) {
        const fromLocalStore = localStorage.getItem(key);
        if (fromLocalStore) {
            // we have saved form data, try to submit it
            const item = JSON.parse(fromLocalStore);

            submit_form(JSON.stringify(item)).then(function(res) {
                if (res === 200) {
                  localStorage.removeItem(key);
                }
              });
        }
      }
    }
  }
}

jQuery(document).ready(function () {

    let validator = jQuery('#contact-form').validate({
        errorPlacement: function(error, element) {
            error.appendTo( element.parent("div") );
        },
        rules: {
            name: {
                required: true,
                minlength: 2,
            },
            phone: {
                required: false,
                minlength: 10
            },
            l: {
                required: false,
                email: true

            }
        },
        messages: {
            name: {
                required: window.TRANSLATION.required,
                minlength: jQuery.validator.format(window.TRANSLATION.characters_required)
            },
            phone: {
                required: window.TRANSLATION.required,
                minlength: jQuery.validator.format(window.TRANSLATION.characters_required)
            }
        },
        submitHandler: function(form) {
            submit_form()
        }

    });
    validator.form()

    let button = jQuery('#submit-button')
    button.html( window.TRANSLATION.submit ).prop('disabled', false)

    // // This is a form delay to discourage robots
    // let counter = 7;
    // let myInterval = setInterval(function () {
    //     let button = jQuery('#submit-button')

    //     button.html( window.TRANSLATION.submit_in + ' ' + counter)
    //     --counter;

    //     if ( counter === 0 ) {
    //         clearInterval(myInterval);
    //         button.html( window.TRANSLATION.submit ).prop('disabled', false)
    //     }

    // }, 1000);


})
