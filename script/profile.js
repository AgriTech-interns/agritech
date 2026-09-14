document.addEventListener("DOMContentLoaded", () => {

    /* =========================================================
       ELEMENTS
    ========================================================== */

    const navItems =
        document.querySelectorAll(".setting-nav-item");

    const sections =
        document.querySelectorAll(".settings-section");

    const searchInput =
        document.getElementById("settingsSearch");

    const profileForm =
        document.getElementById("profileForm");

    const aboutInput =
        document.getElementById("profileAbout");

    const aboutCount =
        document.getElementById("aboutCount");

    const toast =
        document.getElementById("toast");

    const toastMessage =
        document.getElementById("toastMessage");

    const modal =
        document.getElementById("modal");

    const modalContent =
        document.getElementById("modalContent");

    const modalClose =
        document.getElementById("modalClose");

    const photoInput =
        document.getElementById("photoInput");

    const mobileHeader =
        document.querySelector(".mobile-header");

    const sidebar =
        document.querySelector(".settings-sidebar");


    /* =========================================================
       DEFAULT SETTINGS
    ========================================================== */

    const defaultSettings = {
        lastSeen: "Everyone",
        aboutPrivacy: "Everyone",
        profilePhoto: "Everyone",
        groups: "Everyone",
        readReceipts: true,
        onlineStatus: true,
        messageNotifications: true,
        notificationSounds: true,
        communityNotifications: true,
        marketplaceNotifications: true,
        enterToSend: false,
        mediaVisibility: true,
        lowDataMode: false,
        twoStep: false

    };


    let settings =
        JSON.parse(
            localStorage.getItem("agriTechSettings")
        ) || defaultSettings;


    /* =========================================================
       TOAST
    ========================================================== */

    function showToast(message) {
        toastMessage.textContent = message;
        toast.classList.add("show");
        setTimeout(() => {
            toast.classList.remove("show");

        }, 2800);

    }


    /* =========================================================
       SAVE SETTINGS
    ========================================================== */

    function saveSettings() {

        localStorage.setItem(
            "agriTechSettings",
            JSON.stringify(settings)
        );

    }


    /* =========================================================
       LOAD SETTINGS
    ========================================================== */

    function loadSettings() {

        document
            .querySelectorAll("[data-setting]")
            .forEach(element => {

                const name =
                    element.dataset.setting;

                if (!(name in settings)) {
                    return;
                }


                if (element.type === "checkbox") {

                    element.checked =
                        Boolean(settings[name]);

                } else {

                    element.value =
                        settings[name];

                }

            });

    }


    loadSettings();


    /* =========================================================
       NAVIGATION
    ========================================================== */

    navItems.forEach(item => {

        item.addEventListener("click", () => {

            const sectionId =
                item.dataset.section;


            navItems.forEach(nav => {

                nav.classList.remove("active");

            });


            sections.forEach(section => {

                section.classList.remove("active");

            });


            item.classList.add("active");


            const target =
                document.getElementById(sectionId);


            if (target) {

                target.classList.add("active");

                window.scrollTo({
                    top: 0,
                    behavior: "smooth"
                });

            }


            if (window.innerWidth <= 700) {

                sidebar.classList.remove("mobile-open");

            }

        });

    });


    /* =========================================================
       SEARCH SETTINGS
    ========================================================== */

    searchInput.addEventListener("input", () => {

        const query =
            searchInput.value
                .toLowerCase()
                .trim();


        navItems.forEach(item => {

            const text =
                item.textContent.toLowerCase();

            item.style.display =
                text.includes(query)
                    ? "flex"
                    : "none";

        });

    });


    /* =========================================================
       SETTINGS CHANGE
    ========================================================== */

    document
        .querySelectorAll("[data-setting]")
        .forEach(element => {

            element.addEventListener("change", () => {

                const name =
                    element.dataset.setting;


                if (element.type === "checkbox") {

                    settings[name] =
                        element.checked;

                } else {

                    settings[name] =
                        element.value;

                }


                saveSettings();

                showToast("Setting saved.");

            });

        });


    /* =========================================================
       ABOUT COUNTER
    ========================================================== */

    function updateAboutCount() {

        if (!aboutInput) return;

        aboutCount.textContent =
            aboutInput.value.length;

    }


    updateAboutCount();


    aboutInput.addEventListener(
        "input",
        updateAboutCount
    );


    /* =========================================================
       PROFILE FORM
    ========================================================== */

    profileForm.addEventListener(
        "submit",
        async event => {

            event.preventDefault();


            const formData =
                new FormData(profileForm);


            try {

                const response =
                    await fetch(
                        window.location.href,
                        {
                            method: "POST",
                            body: formData
                        }
                    );


                const data =
                    await response.json();


                if (data.success) {

                    updateProfileDisplay(
                        data.user
                    );

                    showToast(
                        data.message
                    );

                } else {

                    showToast(
                        "Unable to update profile."
                    );

                }

            } catch (error) {

                console.error(error);

                /*
                 * If the PHP endpoint is not available,
                 * keep the profile usable locally.
                 */

                const name =
                    document.getElementById(
                        "profileName"
                    ).value;

                const about =
                    document.getElementById(
                        "profileAbout"
                    ).value;


                updateProfileDisplay({
                    name: name,
                    about: about
                });


                showToast(
                    "Profile updated locally."
                );

            }

        }
    );


    /* =========================================================
       UPDATE PROFILE DISPLAY
    ========================================================== */

    function updateProfileDisplay(user) {

        const name =
            user.name || "Agriculture User";

        const about =
            user.about || "Available for farming discussions.";


        const firstLetter =
            name.charAt(0).toUpperCase();


        const sidebarName =
            document.getElementById("sidebarName");

        const sidebarAbout =
            document.getElementById("sidebarAbout");

        const sidebarAvatar =
            document.getElementById("sidebarAvatar");

        const mainName =
            document.getElementById("mainName");

        const mainAvatar =
            document.getElementById("mainAvatar");


        if (sidebarName)
            sidebarName.textContent = name;


        if (sidebarAbout)
            sidebarAbout.textContent = about;


        if (sidebarAvatar)
            sidebarAvatar.textContent = firstLetter;


        if (mainName)
            mainName.textContent = name;


        if (mainAvatar)
            mainAvatar.textContent = firstLetter;


        localStorage.setItem(
            "agriTechProfile",
            JSON.stringify({
                name,
                about
            })
        );

    }


    /* =========================================================
       PROFILE PHOTO
    ========================================================== */

    document
        .getElementById("changePhotoBtn")
        .addEventListener(
            "click",
            () => photoInput.click()
        );


    document
        .getElementById("mainPhotoBtn")
        .addEventListener(
            "click",
            () => photoInput.click()
        );


    photoInput.addEventListener(
        "change",
        event => {

            const file =
                event.target.files[0];


            if (!file) return;


            if (!file.type.startsWith("image/")) {

                showToast(
                    "Please select an image."
                );

                return;

            }


            if (file.size > 5 * 1024 * 1024) {

                showToast(
                    "Image must be smaller than 5MB."
                );

                return;

            }


            const reader =
                new FileReader();


            reader.onload = () => {

                const image =
                    reader.result;


                document
                    .querySelectorAll(
                        ".profile-avatar, .large-avatar"
                    )
                    .forEach(container => {

                        container.style.backgroundImage =
                            `url("${image}")`;

                        container.style.backgroundSize =
                            "cover";

                        container.style.backgroundPosition =
                            "center";

                    });


                document
                    .querySelectorAll(
                        "#sidebarAvatar, #mainAvatar"
                    )
                    .forEach(letter => {

                        letter.style.display = "none";

                    });


                localStorage.setItem(
                    "agriTechProfilePhoto",
                    image
                );


                showToast(
                    "Profile photo updated."
                );

            };


            reader.readAsDataURL(file);

        }
    );


    /* =========================================================
       RESTORE PROFILE PHOTO
    ========================================================== */

    const savedPhoto =
        localStorage.getItem(
            "agriTechProfilePhoto"
        );


    if (savedPhoto) {

        document
            .querySelectorAll(
                ".profile-avatar, .large-avatar"
            )
            .forEach(container => {

                container.style.backgroundImage =
                    `url("${savedPhoto}")`;

                container.style.backgroundSize =
                    "cover";

                container.style.backgroundPosition =
                    "center";

            });


        document
            .querySelectorAll(
                "#sidebarAvatar, #mainAvatar"
            )
            .forEach(letter => {

                letter.style.display = "none";

            });

    }


    /* =========================================================
       MOBILE BACK
    ========================================================== */

    document
        .getElementById("mobileBack")
        .addEventListener(
            "click",
            () => {

                sidebar.classList.toggle(
                    "mobile-open"
                );

            }
        );


    document
        .getElementById("backBtn")
        .addEventListener(
            "click",
            () => {

                if (document.referrer) {

                    window.history.back();

                }

            }
        );


    /* =========================================================
       TWO STEP VERIFICATION
    ========================================================== */

    document
        .getElementById("enableTwoStep")
        .addEventListener(
            "click",
            () => {

                if (settings.twoStep) {

                    showModal(`
                        <h2>Two-step verification</h2>
                        <p>
                            Two-step verification is already
                            enabled on your account.
                        </p>

                        <button class="primary-btn"
                                id="disableTwoStep">
                            Disable
                        </button>
                    `);

                    document
                        .getElementById("disableTwoStep")
                        .addEventListener(
                            "click",
                            () => {

                                settings.twoStep = false;

                                saveSettings();

                                closeModal();

                                showToast(
                                    "Two-step verification disabled."
                                );

                            }
                        );

                    return;

                }


                showModal(`
                    <h2>Enable two-step verification</h2>

                    <p style="margin:12px 0;color:#6d7771;line-height:1.6">
                        Create a PIN to add an extra layer
                        of protection to your AgriTech account.
                    </p>

                    <input
                        type="password"
                        id="twoStepPin"
                        maxlength="6"
                        inputmode="numeric"
                        placeholder="Enter 6-digit PIN"
                        style="
                            width:100%;
                            padding:12px;
                            border:1px solid #e4e9e6;
                            border-radius:8px;
                            outline:none;
                            margin-bottom:12px;
                        "
                    >

                    <button class="primary-btn"
                            id="confirmTwoStep">

                        Enable verification

                    </button>
                `);


                document
                    .getElementById("confirmTwoStep")
                    .addEventListener(
                        "click",
                        () => {

                            const pin =
                                document
                                    .getElementById(
                                        "twoStepPin"
                                    )
                                    .value;


                            if (!/^\d{6}$/.test(pin)) {

                                showToast(
                                    "Enter a 6-digit PIN."
                                );

                                return;

                            }


                            settings.twoStep = true;

                            saveSettings();

                            closeModal();

                            showToast(
                                "Two-step verification enabled."
                            );

                        }
                    );

            }
        );


    /* =========================================================
       ACTIONS
    ========================================================== */

    document
        .querySelectorAll("[data-action]")
        .forEach(element => {

            element.addEventListener(
                "click",
                () => {

                    const action =
                        element.dataset.action;


                    switch (action) {

                        case "invite":

                            showModal(`
                                <h2>Invite a farmer</h2>

                                <p style="margin:12px 0;color:#6d7771">
                                    Share AgriTech with farmers,
                                    agricultural experts and buyers.
                                </p>

                                <button class="primary-btn"
                                        id="shareInvite">

                                    <i class="fa-solid fa-share-nodes"></i>

                                    Share invitation

                                </button>
                            `);


                            document
                                .getElementById("shareInvite")
                                .addEventListener(
                                    "click",
                                    shareInvitation
                                );

                            break;


                        case "devices":

                            showModal(`
                                <h2>Linked devices</h2>

                                <p style="margin:12px 0;color:#6d7771">
                                    No other devices are currently
                                    linked to your AgriTech account.
                                </p>
                            `);

                            break;


                        case "security-alerts":

                            showToast(
                                "Security notifications are enabled."
                            );

                            break;


                        // case "help-center":

                        //     showModal(`
                        //         <h2>AgriTech Help Center</h2>

                        //         <p style="margin:12px 0;color:#6d7771;line-height:1.6">
                        //             Find help about your account,
                        //             messaging, agricultural products,
                        //             communities and marketplace features.
                        //         </p>

                        //         <button class="primary-btn">
                        //             Browse help
                        //         </button>
                        //     `);

                        //     break;


                        // case "contact":

                        //     window.location.href =
                        //         "mailto:support@agritech.local";

                        //     break;


                        // case "terms":

                        //     showModal(`
                        //         <h2>Terms & Privacy</h2>

                        //         <p style="margin-top:12px;color:#6d7771;line-height:1.6">
                        //             Your account information and
                        //             agricultural community activity
                        //             should be handled according to
                        //             the privacy rules of your platform.
                        //         </p>
                        //     `);

                        //     break;

                    }

                }
            );

        });


    /* =========================================================
       SHARE INVITATION
    ========================================================== */

    async function shareInvitation() {

        const shareData = {

            title: "Join AgriTech",

            text:
                "Join me on AgriTech, an agricultural community " +
                "for farmers, buyers and agricultural experts."

        };


        try {

            if (
                navigator.share &&
                window.isSecureContext
            ) {

                await navigator.share(
                    shareData
                );

                showToast(
                    "Invitation shared."
                );

            } else {

                await navigator.clipboard.writeText(
                    shareData.text
                );

                showToast(
                    "Invitation copied."
                );

            }

        } catch (error) {

            console.log(error);

        }

    }


    /* =========================================================
       WALLPAPER
    ========================================================== */

    document
        .getElementById("wallpaperBtn")
        .addEventListener(
            "click",
            () => {

                showModal(`
                    <h2>Chat wallpaper</h2>

                    <p style="margin:12px 0;color:#6d7771">
                        Choose a wallpaper style for your
                        agricultural conversations.
                    </p>

                    <div style="
                        display:grid;
                        grid-template-columns:repeat(3,1fr);
                        gap:10px;
                        margin-top:20px;
                    ">

                        <button
                            class="wallpaper-choice"
                            data-wallpaper="plain">
                            Plain
                        </button>

                        <button
                            class="wallpaper-choice"
                            data-wallpaper="green">
                            Green
                        </button>

                        <button
                            class="wallpaper-choice"
                            data-wallpaper="farm">
                            Farm
                        </button>

                    </div>
                `);


                document
                    .querySelectorAll(
                        ".wallpaper-choice"
                    )
                    .forEach(button => {

                        button.style.padding =
                            "15px";

                        button.style.borderRadius =
                            "9px";

                        button.style.border =
                            "1px solid #e4e9e6";

                        button.style.background =
                            "#f5f7f6";


                        button.addEventListener(
                            "click",
                            () => {

                                const wallpaper =
                                    button.dataset.wallpaper;


                                localStorage.setItem(
                                    "agriChatWallpaper",
                                    wallpaper
                                );


                                closeModal();

                                showToast(
                                    "Chat wallpaper saved."
                                );

                            }
                        );

                    });

            }
        );


    /* =========================================================
       CLEAR STORAGE
    ========================================================== */

    document
        .getElementById("clearStorage")
        .addEventListener(
            "click",
            () => {

                const confirmed =
                    confirm(
                        "Clear cached AgriTech data?"
                    );


                if (!confirmed) return;


                const profilePhoto =
                    localStorage.getItem(
                        "agriTechProfilePhoto"
                    );


                localStorage.removeItem(
                    "agriChatWallpaper"
                );


                localStorage.removeItem(
                    "agriTechSettings"
                );


                if (profilePhoto) {

                    localStorage.setItem(
                        "agriTechProfilePhoto",
                        profilePhoto
                    );

                }


                showToast(
                    "Cached settings cleared."
                );

            }
        );


    /* =========================================================
       LANGUAGE
    ========================================================== */

    document
        .querySelectorAll(".language-option")
        .forEach(option => {

            option.addEventListener(
                "click",
                () => {

                    document
                        .querySelectorAll(
                            ".language-option"
                        )
                        .forEach(item => {

                            item.classList.remove(
                                "selected"
                            );

                            const icon =
                                item.querySelector(
                                    ".check"
                                );

                            if (icon) {

                                icon.className =
                                    "fa-regular fa-circle check";

                            }

                        });


                    option.classList.add(
                        "selected"
                    );


                    const check =
                        option.querySelector(
                            ".check"
                        );


                    if (check) {

                        check.className =
                            "fa-solid fa-circle-check check";

                    }


                    showToast(
                        "Language preference saved."
                    );

                }
            );

        });


    /* =========================================================
       DELETE ACCOUNT
    ========================================================== */

    document
        .getElementById("deleteAccountBtn")
        .addEventListener(
            "click",
            () => {

                showModal(`
                    <h2 style="color:#dc3545">
                        Delete account?
                    </h2>

                    <p style="
                        margin:12px 0 20px;
                        color:#6d7771;
                        line-height:1.6;
                    ">
                        This action is permanent. Your profile,
                        messages and agricultural community
                        information may be removed.
                    </p>

                    <button
                        id="confirmDelete"
                        style="
                            padding:11px 18px;
                            border-radius:8px;
                            background:#dc3545;
                            color:white;
                            font-weight:600;
                        ">

                        Permanently delete

                    </button>
                `);


                document
                    .getElementById("confirmDelete")
                    .addEventListener(
                        "click",
                        () => {

                            showToast(
                                "Account deletion requires server confirmation."
                            );

                            closeModal();

                        }
                    );

            }
        );


    /* =========================================================
       LOGOUT
    ========================================================== */

    document
        .getElementById("logoutBtn")
        .addEventListener(
            "click",
            async () => {

                const confirmed =
                    confirm(
                        "Are you sure you want to log out?"
                    );


                if (!confirmed) return;


                const formData =
                    new FormData();


                formData.append(
                    "action",
                    "logout"
                );


                try {

                    const response =
                        await fetch(
                            window.location.href,
                            {
                                method: "POST",
                                body: formData
                            }
                        );


                    const data =
                        await response.json();


                    if (data.success) {

                        /*
                         * Change this to your actual
                         * login page when connected.
                         */

                        window.location.href =
                            "login.php";

                    }

                } catch (error) {

                    console.error(error);

                    window.location.href =
                        "login.php";

                }

            }
        );


    /* =========================================================
       MODAL FUNCTIONS
    ========================================================== */

    function showModal(content) {

        modalContent.innerHTML =
            content;

        modal.classList.add("show");

    }


    function closeModal() {

        modal.classList.remove(
            "show"
        );

        modalContent.innerHTML = "";

    }


    modalClose.addEventListener(
        "click",
        closeModal
    );


    modal.addEventListener(
        "click",
        event => {

            if (event.target === modal) {

                closeModal();

            }

        }
    );


    document.addEventListener(
        "keydown",
        event => {

            if (
                event.key === "Escape" &&
                modal.classList.contains("show")
            ) {

                closeModal();

            }

        }
    );


    /* =========================================================
       LOAD LOCALLY SAVED PROFILE
    ========================================================== */

    const savedProfile =
        JSON.parse(
            localStorage.getItem(
                "agriTechProfile"
            )
        );


    if (savedProfile) {

        updateProfileDisplay(
            savedProfile
        );

    }

});