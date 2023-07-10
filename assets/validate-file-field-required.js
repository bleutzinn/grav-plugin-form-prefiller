
function showError(fileDiv, msg) {
  const errorContainer = document.createElement('div');
  errorContainer.classList.add('gfpfp-validation-error');
  errorContainer.textContent = msg;
  fileDiv.appendChild(errorContainer);
}

function sprintf(format, ...args) {
  return format.replace(/%([sdfx])/g, function(match, type) {
    const arg = args.shift();
    switch (type) {
      case 's':
        return String(arg);
      case 'd':
        return parseInt(arg, 10).toString();
      case 'f':
        return parseFloat(arg).toString();
      case 'x':
        return parseInt(arg, 10).toString(16);
      default:
        return match;
    }
  });
}

// Get the form element
const form = document.querySelector('form');

// Attach event listener to the form's submit event
form.addEventListener("submit", function (event) {
  // Prevent the form from submitting immediately
  event.preventDefault();

  const fileDivs = form.querySelectorAll('div.form-data[data-grav-field="file"]');
  let allValid = true;

  // Process each set of file fields separately
  fileDivs.forEach(function (fileDiv) {
    // Get the attributes from each form file field
    const required = fileDiv.hasAttribute("data-required");
    // Determine the number of successfully uploaded files within the current set
    const uploads = fileDiv.parentNode.querySelectorAll(".dz-success").length;

  
    // Perform validation on required attribute
    if (required && uploads < 1) {
      allValid = false;
      showError(fileDiv, translations.PLUGIN_FORM_PREFILLER.VALIDATION.ERRORS.REQUIRED);
    }

    let minNumber = null;
    let maxNumber = null;

    if (fileDiv.hasAttribute("data-minNumberOfFiles")) {
      minNumber = +fileDiv.getAttribute("data-minNumberOfFiles");
    } else {
      minNumber = null;
    }
    if (fileDiv.hasAttribute("data-maxNumberOfFiles")) {
      maxNumber = +fileDiv.getAttribute("data-maxNumberOfFiles");
    } else {
      maxNumber = null;
    }

    // Perform validation on minimum number of files
    if (minNumber !== null && minNumber > 0 && uploads < minNumber) {
      allValid = false;
      const t = translations.PLUGIN_FORM_PREFILLER.VALIDATION.ERRORS.UPLOAD_MIN;
      if (minNumber > 1 && 'undefined' !== t.PLURAL) {
        msg = sprintf(t.PLURAL, minNumber);
      } else {
        if ('undefined' !== t.SINGULAR) {
          msg = sprintf(t.SINGULAR, minNumber);
        } else {
          msg = sprintf(t, minNumber);
        }
      }
      showError(fileDiv, msg);
    } else {
      // Perform validation on maximum number of files
      if (maxNumber !== null && maxNumber > 0 && uploads > maxNumber) {
        allValid = false;
        const t = translations.PLUGIN_FORM_PREFILLER.VALIDATION.ERRORS.UPLOAD_MAX;
        if (maxNumber > 1 && 'undefined' !== t.PLURAL) {
          msg = sprintf(t.PLURAL, maxNumber);
        } else {
          if ('undefined' !== t.SINGULAR) {
            msg = sprintf(t.SINGULAR, maxNumber);
          } else {
            msg = sprintf(t, maxNumber);
          }
        }
        showError(fileDiv, msg);
      }
    }
  });

  if (allValid) {
    // If all pass validation, submit the form
    form.submit();
  }
});

// Add click event listener to remove error messages
document.addEventListener('click', function () {
  const errorContainers = form.querySelectorAll('.gfpfp-validation-error');
  errorContainers.forEach(function (errorContainer) {
    errorContainer.remove();
  });
});
