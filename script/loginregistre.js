document.addEventListener("DOMContentLoaded", function () {

    /* =========================================
       PASSWORD SHOW / HIDE FUNCTION
    ========================================= */

    const passwordInputs =
        document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(function (input) {

        // Wrap the input so the icon can be positioned
        // relative to the box instead of the whole form-group
        const wrapper =
            document.createElement("div");

        wrapper.className = "password-wrapper";

        wrapper.style.position = "relative";
        wrapper.style.display = "flex";
        wrapper.style.alignItems = "center";
        wrapper.style.width = "100%";

        input.parentElement.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        // Leave room for the icon so text never runs under it
        input.style.width = "100%";
        input.style.boxSizing = "border-box";
        input.style.paddingRight = "40px";

        // Create the toggle button
        const toggleButton =
            document.createElement("button");

        toggleButton.type = "button";

        toggleButton.className =
            "password-toggle";

        toggleButton.setAttribute(
            "aria-label",
            "Show password"
        );

        toggleButton.innerHTML =
            '<i class="fa-regular fa-eye"></i>';

        // Position the button inside the input box
        toggleButton.style.position = "absolute";
        toggleButton.style.top = "50%";
        toggleButton.style.right = "12px";
        toggleButton.style.transform = "translateY(-50%)";
        toggleButton.style.display = "flex";
        toggleButton.style.alignItems = "center";
        toggleButton.style.justifyContent = "center";
        toggleButton.style.padding = "0";
        toggleButton.style.border = "none";
        toggleButton.style.background = "none";
        toggleButton.style.color = "#666";
        toggleButton.style.cursor = "pointer";
        toggleButton.style.lineHeight = "1";

        wrapper.appendChild(toggleButton);

        // Show / hide password
        toggleButton.addEventListener(
            "click",
            function () {

                if (input.type === "password") {

                    input.type = "text";

                    toggleButton.innerHTML =
                        '<i class="fa-regular fa-eye-slash"></i>';

                    toggleButton.setAttribute(
                        "aria-label",
                        "Hide password"
                    );

                } else {

                    input.type = "password";

                    toggleButton.innerHTML =
                        '<i class="fa-regular fa-eye"></i>';

                    toggleButton.setAttribute(
                        "aria-label",
                        "Show password"
                    );

                }

            }
        );

    });


    /* =========================================
       LOGIN FORM VALIDATION
    ========================================= */

    const loginForm =
        document.querySelector(
            'form[action="login.php"]'
        );


    if (loginForm) {

        loginForm.addEventListener(
            "submit",
            function (event) {

                const email =
                    loginForm.querySelector(
                        'input[name="email"]'
                    );

                const password =
                    loginForm.querySelector(
                        'input[name="password"]'
                    );


                // Check email
                if (
                    email.value.trim() === ""
                ) {

                    event.preventDefault();

                    showMessage(
                        "Please enter your email address.",
                        "error"
                    );

                    email.focus();

                    return;

                }


                // Validate email format
                if (
                    !isValidEmail(
                        email.value.trim()
                    )
                ) {

                    event.preventDefault();

                    showMessage(
                        "Please enter a valid email address.",
                        "error"
                    );

                    email.focus();

                    return;

                }


                // Check password
                if (
                    password.value.trim() === ""
                ) {

                    event.preventDefault();

                    showMessage(
                        "Please enter your password.",
                        "error"
                    );

                    password.focus();

                    return;

                }


                // Prevent very short passwords
                if (
                    password.value.length < 8
                ) {

                    event.preventDefault();

                    showMessage(
                        "Password must contain at least 8 characters.",
                        "error"
                    );

                    password.focus();

                    return;

                }

            }
        );

    }


    /* =========================================
       REGISTER FORM VALIDATION
    ========================================= */

    const registerForm =
        document.querySelector(
            'form[action="register.php"]'
        );


    if (registerForm) {

        registerForm.addEventListener(
            "submit",
            function (event) {

                const firstName =
                    registerForm.querySelector(
                        'input[name="first_name"]'
                    );

                const lastName =
                    registerForm.querySelector(
                        'input[name="last_name"]'
                    );

                const email =
                    registerForm.querySelector(
                        'input[name="email"]'
                    );

                const phone =
                    registerForm.querySelector(
                        'input[name="phone"]'
                    );

                const role =
                    registerForm.querySelector(
                        'select[name="role"]'
                    );

                const password =
                    registerForm.querySelector(
                        'input[name="password"]'
                    );

                const confirmPassword =
                    registerForm.querySelector(
                        'input[name="confirm_password"]'
                    );

                const terms =
                    registerForm.querySelector(
                        'input[name="terms"]'
                    );


                /* Check first name */

                if (
                    firstName.value.trim() === ""
                ) {

                    event.preventDefault();

                    showMessage(
                        "Please enter your first name.",
                        "error"
                    );

                    firstName.focus();

                    return;

                }


                /* Check last name */

                if (
                    lastName.value.trim() === ""
                ) {

                    event.preventDefault();

                    showMessage(
                        "Please enter your last name.",
                        "error"
                    );

                    lastName.focus();

                    return;

                }


                /* Check email */

                if (
                    email.value.trim() === ""
                ) {

                    event.preventDefault();

                    showMessage(
                        "Please enter your email address.",
                        "error"
                    );

                    email.focus();

                    return;

                }


                /* Validate email */

                if (
                    !isValidEmail(
                        email.value.trim()
                    )
                ) {

                    event.preventDefault();

                    showMessage(
                        "Please enter a valid email address.",
                        "error"
                    );

                    email.focus();

                    return;

                }


                /* Check phone number */

                if (
                    phone &&
                    phone.value.trim() !== ""
                ) {

                    const phonePattern =
                        /^[0-9+\-\s()]{7,20}$/;

                    if (
                        !phonePattern.test(
                            phone.value.trim()
                        )
                    ) {

                        event.preventDefault();

                        showMessage(
                            "Please enter a valid phone number.",
                            "error"
                        );

                        phone.focus();

                        return;

                    }

                }


                /* Check account type */

                if (
                    role.value === ""
                ) {

                    event.preventDefault();

                    showMessage(
                        "Please select your account type.",
                        "error"
                    );

                    role.focus();

                    return;

                }


                /* Check password */

                if (
                    password.value.length < 8
                ) {

                    event.preventDefault();

                    showMessage(
                        "Password must contain at least 8 characters.",
                        "error"
                    );

                    password.focus();

                    return;

                }


                /* Check password strength */

                if (
                    !isStrongPassword(
                        password.value
                    )
                ) {

                    event.preventDefault();

                    showMessage(
                        "Password must contain uppercase, lowercase, and a number.",
                        "error"
                    );

                    password.focus();

                    return;

                }


                /* Check confirm password */

                if (
                    password.value !==
                    confirmPassword.value
                ) {

                    event.preventDefault();

                    showMessage(
                        "Passwords do not match.",
                        "error"
                    );

                    confirmPassword.focus();

                    return;

                }


                /* Check terms */

                if (
                    !terms.checked
                ) {

                    event.preventDefault();

                    showMessage(
                        "Please accept the Terms and Conditions.",
                        "error"
                    );

                    return;

                }

            }
        );

    }


    /* =========================================
       EMAIL VALIDATION FUNCTION
    ========================================= */

    function isValidEmail(email) {

        const emailPattern =
            /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        return emailPattern.test(email);

    }


    /* =========================================
       PASSWORD STRENGTH FUNCTION
    ========================================= */

    function isStrongPassword(password) {

        const passwordPattern =
            /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;

        return passwordPattern.test(password);

    }


    /* =========================================
       MESSAGE FUNCTION
    ========================================= */

    function showMessage(
        message,
        type
    ) {

        // Remove existing message
        const oldMessage =
            document.querySelector(
                ".auth-message"
            );

        if (oldMessage) {

            oldMessage.remove();

        }


        // Create new message
        const messageBox =
            document.createElement("div");

        messageBox.className =
            "auth-message " + type;

        messageBox.textContent =
            message;


        // Find the form
        const form =
            document.querySelector(
                "form"
            );


        if (form) {

            form.insertBefore(
                messageBox,
                form.firstChild
            );

        }


        // Automatically remove after 5 seconds
        setTimeout(
            function () {
                messageBox.remove();
            },
            5000
        );

    }


    /* =========================================
       SUCCESS MESSAGE AFTER REGISTRATION
    ========================================= */

    const urlParams =
        new URLSearchParams(
            window.location.search
        );


    if (
        urlParams.get("registered") ===
        "success"
    ) {

        showMessage(
            "Account created successfully. You can now log in.",
            "success"
        );

    }


});