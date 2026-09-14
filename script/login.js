document.addEventListener("DOMContentLoaded", function () {

    /* =========================================
       PASSWORD SHOW / HIDE FUNCTION
    ========================================= */

    const passwordInputs =
        document.querySelectorAll('input[type="password"]');

    passwordInputs.forEach(function (input) {

        const wrapper =
            document.createElement("div");

        wrapper.className = "password-wrapper";

        wrapper.style.position = "relative";
        wrapper.style.display = "flex";
        wrapper.style.alignItems = "center";
        wrapper.style.width = "100%";

        input.parentElement.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        input.style.width = "100%";
        input.style.boxSizing = "border-box";
        input.style.paddingRight = "40px";

        const toggleButton =
            document.createElement("button");

        toggleButton.type = "button";
        toggleButton.className = "password-toggle";
        toggleButton.setAttribute("aria-label", "Show password");
        toggleButton.innerHTML = '<i class="fa-regular fa-eye"></i>';

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

        toggleButton.addEventListener("click", function () {

            if (input.type === "password") {
                input.type = "text";
                toggleButton.innerHTML = '<i class="fa-regular fa-eye-slash"></i>';
                toggleButton.setAttribute("aria-label", "Hide password");
            } else {
                input.type = "password";
                toggleButton.innerHTML = '<i class="fa-regular fa-eye"></i>';
                toggleButton.setAttribute("aria-label", "Show password");
            }

        });

    });


    /* =========================================
       LOGIN FORM VALIDATION
    ========================================= */

    const loginForm =
        document.querySelector('form[action="login.php"]');

    if (loginForm) {

        loginForm.addEventListener("submit", function (event) {

            const email =
                loginForm.querySelector('input[name="email"]');

            const password =
                loginForm.querySelector('input[name="password"]');

            if (email.value.trim() === "") {
                event.preventDefault();
                showMessage(loginForm, "Please enter your email address.", "error");
                email.focus();
                return;
            }

            if (!isValidEmail(email.value.trim())) {
                event.preventDefault();
                showMessage(loginForm, "Please enter a valid email address.", "error");
                email.focus();
                return;
            }

            if (password.value.trim() === "") {
                event.preventDefault();
                showMessage(loginForm, "Please enter your password.", "error");
                password.focus();
                return;
            }

            if (password.value.length < 8) {
                event.preventDefault();
                showMessage(loginForm, "Password must contain at least 8 characters.", "error");
                password.focus();
                return;
            }

        });

    }


    /* =========================================
       EMAIL VALIDATION FUNCTION
    ========================================= */

    function isValidEmail(email) {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailPattern.test(email);
    }


    /* =========================================
       MESSAGE FUNCTION
    ========================================= */

    function showMessage(form, message, type) {

        const oldMessage =
            document.querySelector(".auth-message");

        if (oldMessage) {
            oldMessage.remove();
        }

        const messageBox =
            document.createElement("div");

        messageBox.className = "auth-message " + type;
        messageBox.textContent = message;

        if (form) {
            form.insertBefore(messageBox, form.firstChild);
        }

        setTimeout(function () {
            messageBox.remove();
        }, 5000);

    }


    /* =========================================
       SUCCESS MESSAGE AFTER REGISTRATION
       (user lands here via register.php -> login.php?registered=success)
    ========================================= */

    const urlParams =
        new URLSearchParams(window.location.search);

    if (urlParams.get("registered") === "success" && loginForm) {
        showMessage(loginForm, "Account created successfully. You can now log in.", "success");
    }

});