//check online status
window.addEventListener('online',  checkStorage);

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
    const entry = {
      time: new Date().getTime(),
      data: JSON.stringify(data),
    }
    localStorage.setItem(new URLSearchParams(location.search).get('token'), JSON.stringify(entry));
    //reenable the submit button if the data is saved.
    document.querySelector('#submit-button').disabled = false;
    document.querySelector("#submit-button-container .spinner").remove()
    return true;
  }
  return false;
}

function submit_form(data) {
  let url = get_url();
  console.log(data);
  fetch(url + '/wp-json/dt-public/v1/webform/form_submit', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json;charset=utf-8'
    },
    body: data
  })
  .then((data) => {
    console.log('Success:', data);
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
  console.log(JSON.stringify(data));

  if (!navigator.onLine) {
    // user is offline, store data locally
    const stored = storeData(data);
    let message = '<strong>You appear to be offline right now. </strong>';
    if (stored) {
      message += 'Your data was saved and will be submitted once you come back online.';
    }
    console.log(message);
    document.querySelector("form").reset()
    submitButtonContainer.insertAdjacentHTML("beforeend", `<span class="offlineMessage">${message}</span>`);
  } else {
    submit_form(JSON.stringify(data));
    submitButton.disabled = false;
    document.querySelector("#submit-button-container .spinner").remove()

  }
}

function get_url() {
    return window.location.protocol + '//' + window.location.hostname
}

function checkStorage() {
  // check if we have saved data in localStorage
  console.log("checkStorage");
  if (typeof Storage !== 'undefined') {
    const item = localStorage.getItem(new URLSearchParams(location.search).get('token'));
    const entry = item && JSON.parse(item);

    if (entry) {

      //TODO: Delete the localstorage after confirmed save
      // discard submissions older than one day
      const now = new Date().getTime();
      const day = 24 * 60 * 60 * 1000;
      if (now - day > entry.time) {
        localStorage.removeItem(this.id);
        return;
      }

      // we have saved form data, try to submit it
      var formData = JSON.parse(item).data;
      submit_form(formData);
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
