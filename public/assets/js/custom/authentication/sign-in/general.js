"use strict";

// Class definition
var KTSigninGeneral = (function () {
    // Elements
    var form;
    var submitButton;
    var validator;
    var defaultPopupConfig = {
        timer: 3000, // Default duration in milliseconds
        timerProgressBar: true,
        showConfirmButton: true,
        allowOutsideClick: false, // Prevent closing by clicking outside
        allowEscapeKey: false, // Prevent closing by pressing ESC
        willClose: () => {
            // Reset form and redirect if needed
            form.querySelector('[name="email"]').value = "";
            form.querySelector('[name="password"]').value = "";
            const redirectUrl = form.getAttribute("data-kt-redirect-url");
            if (redirectUrl) {
                location.href = redirectUrl;
            }
        },
    };

    // Handle form
    var handleValidation = function (e) {
        // Init form validation rules. For more info check the FormValidation plugin's official documentation:https://formvalidation.io/
        validator = FormValidation.formValidation(form, {
            fields: {
                email: {
                    validators: {
                        regexp: {
                            regexp: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                            message: "The value is not a valid email address",
                        },
                        notEmpty: {
                            message: "Email address is required",
                        },
                    },
                },
                password: {
                    validators: {
                        notEmpty: {
                            message: "The password is required",
                        },
                    },
                },
            },
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap: new FormValidation.plugins.Bootstrap5({
                    rowSelector: ".fv-row",
                    eleInvalidClass: "",
                    eleValidClass: "",
                }),
            },
        });
    };

    var showPopup = function (config) {
        // Merge config with defaultPopupConfig
        const mergedConfig = {
            ...defaultPopupConfig,
            ...config,
            // Override timer if showConfirmButton is true
            timer: config.showConfirmButton
                ? undefined
                : defaultPopupConfig.timer,
            customClass: {
                confirmButton: "btn btn-primary",
            },
            buttonsStyling: false,
        };

        return Swal.fire(mergedConfig);
    };

    var handleSubmitDemo = function (e) {
        submitButton.addEventListener("click", function (e) {
            e.preventDefault();

            validator.validate().then(function (status) {
                if (status == "Valid") {
                    submitButton.setAttribute("data-kt-indicator", "on");
                    submitButton.disabled = true;

                    setTimeout(function () {
                        submitButton.removeAttribute("data-kt-indicator");
                        submitButton.disabled = false;

                        showPopup({
                            text: "You have successfully logged in!",
                            icon: "success",
                            confirmButtonText: "Ok, got it!",
                            showConfirmButton: true,
                        });
                    }, 2000);
                } else {
                    showPopup({
                        text: "Sorry, looks like there are some errors detected, please try again.",
                        icon: "error",
                        confirmButtonText: "Ok, got it!",
                        showConfirmButton: true,
                    });
                }
            });
        });
    };

    var isValidUrl = function (url) {
        try {
            new URL(url);
            return true;
        } catch (e) {
            return false;
        }
    };

    return {
        init: function () {
            form = document.querySelector("#kt_sign_in_form");
            submitButton = document.querySelector("#kt_sign_in_submit");

            handleValidation();

            if (
                isValidUrl(submitButton.closest("form").getAttribute("action"))
            ) {
                // handleSubmitAjax();
            } else {
                // handleSubmitAjax();
            }
        },
    };
})();

// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTSigninGeneral.init();
});

// Nonaktifkan AJAX login, gunakan submit standar
// Komentari atau hapus handler handleSubmitAjax dan inisialisasinya
// document.addEventListener('DOMContentLoaded', function () {
//     form = document.querySelector("#kt_sign_in_form");
//     submitButton = document.querySelector("#kt_sign_in_submit");
//     handleValidation();
//     // Jangan inisialisasi handleSubmitAjax, biarkan form submit standar
// });
