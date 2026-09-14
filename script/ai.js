document.addEventListener("DOMContentLoaded", () => {

    "use strict";

    /* =====================================================
       CONFIGURATION
    ===================================================== */

    const config = window.AgroAIConfig || {};

    const endpoint =
        config.endpoint || "ai-assistant.php";

    const csrfToken =
        config.csrfToken || "";

    const maxLength =
        config.maxMessageLength || 4000;


    /* =====================================================
       ELEMENTS
    ===================================================== */

    const sidebar =
        document.getElementById("aiSidebar");

    const openSidebarButton =
        document.getElementById("openSidebar");

    const closeSidebarButton =
        document.getElementById("closeSidebar");

    const newChatButton =
        document.getElementById("newChatButton");

    const clearChatButton =
        document.getElementById("clearChatButton");

    const themeButton =
        document.getElementById("themeButton");

    const messageInput =
        document.getElementById("messageInput");

    const sendButton =
        document.getElementById("sendButton");

    const voiceButton =
        document.getElementById("voiceButton");

    const chatMessages =
        document.getElementById("chatMessages");

    const welcomeScreen =
        document.getElementById("welcomeScreen");

    const typingIndicator =
        document.getElementById("typingIndicator");

    const characterCount =
        document.getElementById("characterCount");

    const conversationList =
        document.getElementById("conversationList");


    /* =====================================================
       STATE
    ===================================================== */

    let messages = [];

    let conversationId =
        localStorage.getItem(
            "agro_ai_conversation_id"
        ) ||
        generateId();

    let conversations =
        JSON.parse(
            localStorage.getItem(
                "agro_ai_conversations"
            ) || "[]"
        );


    /* =====================================================
       GENERATE ID
    ===================================================== */

    function generateId() {

        return (
            Date.now().toString(36) +
            Math.random()
                .toString(36)
                .substring(2, 9)
        );
    }


    /* =====================================================
       SAVE STATE
    ===================================================== */

    function saveConversation() {

        localStorage.setItem(
            `agro_ai_messages_${conversationId}`,
            JSON.stringify(messages)
        );

        localStorage.setItem(
            "agro_ai_conversation_id",
            conversationId
        );
    }


    function saveConversationList() {

        localStorage.setItem(
            "agro_ai_conversations",
            JSON.stringify(conversations)
        );
    }


    /* =====================================================
       LOAD CONVERSATION
    ===================================================== */

    function loadConversation() {

        const saved =
            localStorage.getItem(
                `agro_ai_messages_${conversationId}`
            );

        if (!saved) {

            messages = [];

            showWelcome();

            return;
        }

        try {

            messages =
                JSON.parse(saved);

        } catch {

            messages = [];
        }

        if (messages.length) {

            hideWelcome();

            messages.forEach(message => {

                renderMessage(
                    message.role,
                    message.content,
                    message.time,
                    false
                );
            });

        } else {

            showWelcome();
        }
    }


    /* =====================================================
       WELCOME
    ===================================================== */

    function showWelcome() {

        if (welcomeScreen) {
            welcomeScreen.hidden = false;
        }
    }


    function hideWelcome() {

        if (welcomeScreen) {
            welcomeScreen.hidden = true;
        }
    }


    /* =====================================================
       ESCAPE HTML
    ===================================================== */

    function escapeHtml(text) {

        const div =
            document.createElement("div");

        div.textContent =
            text;

        return div.innerHTML;
    }


    /* =====================================================
       BASIC MARKDOWN
    ===================================================== */

    function formatAIResponse(text) {

        let html =
            escapeHtml(text);

        /*
         * Bold
         */
        html =
            html.replace(
                /\*\*(.*?)\*\*/g,
                "<strong>$1</strong>"
            );

        /*
         * Italic
         */
        html =
            html.replace(
                /\*(.*?)\*/g,
                "<em>$1</em>"
            );

        /*
         * Bullet points
         */
        html =
            html.replace(
                /^\s*[-•]\s+(.*)$/gm,
                "<li>$1</li>"
            );

        html =
            html.replace(
                /(<li>.*<\/li>)/gs,
                "<ul>$1</ul>"
            );

        /*
         * Numbered lists
         */
        html =
            html.replace(
                /^\s*(\d+)\.\s+(.*)$/gm,
                "<li>$2</li>"
            );

        /*
         * New lines
         */
        html =
            html.replace(
                /\n/g,
                "<br>"
            );

        return html;
    }


    /* =====================================================
       RENDER MESSAGE
    ===================================================== */

    function renderMessage(
        role,
        content,
        time = null,
        scroll = true
    ) {

        hideWelcome();

        const wrapper =
            document.createElement("div");

        wrapper.className =
            `chat-message ${role}`;

        const avatar =
            document.createElement("div");

        avatar.className =
            "message-avatar";

        avatar.innerHTML =
            role === "ai"
                ? '<i class="fa-solid fa-seedling"></i>'
                : '<i class="fa-solid fa-user"></i>';

        const body =
            document.createElement("div");

        body.className =
            "message-body";

        const contentDiv =
            document.createElement("div");

        contentDiv.className =
            "message-content";

        if (role === "ai") {

            contentDiv.innerHTML =
                formatAIResponse(content);

        } else {

            contentDiv.textContent =
                content;
        }

        const timeDiv =
            document.createElement("div");

        timeDiv.className =
            "message-time";

        timeDiv.textContent =
            time ||
            new Date().toLocaleTimeString(
                [],
                {
                    hour: "2-digit",
                    minute: "2-digit"
                }
            );

        body.appendChild(contentDiv);
        body.appendChild(timeDiv);

        wrapper.appendChild(avatar);
        wrapper.appendChild(body);

        chatMessages.appendChild(wrapper);

        if (scroll) {
            scrollToBottom();
        }
    }


    /* =====================================================
       SCROLL
    ===================================================== */

    function scrollToBottom() {

        requestAnimationFrame(() => {

            chatMessages.scrollTop =
                chatMessages.scrollHeight;

        });
    }


    /* =====================================================
       ADD MESSAGE
    ===================================================== */

    function addMessage(
        role,
        content
    ) {

        const time =
            new Date().toLocaleTimeString(
                [],
                {
                    hour: "2-digit",
                    minute: "2-digit"
                }
            );

        const message = {

            role,
            content,
            time

        };

        messages.push(message);

        renderMessage(
            role,
            content,
            time
        );

        saveConversation();

        return message;
    }


    /* =====================================================
       SEND MESSAGE
    ===================================================== */

    async function sendMessage() {

        const text =
            messageInput.value.trim();

        if (!text) return;

        if (text.length > maxLength) {

            showToast(
                `Your question is too long. Maximum ${maxLength} characters.`,
                "error"
            );

            return;
        }

        messageInput.value = "";

        updateCharacterCount();

        autoResize();

        sendButton.disabled = true;

        addMessage(
            "user",
            text
        );

        showTyping(true);

        try {

            const response =
                await fetch(
                    endpoint,
                    {
                        method: "POST",

                        headers: {
                            "Content-Type":
                                "application/json",

                            "X-CSRF-TOKEN":
                                csrfToken
                        },

                        credentials: "same-origin",

                        body: JSON.stringify({

                            message: text,

                            conversation_id:
                                conversationId,

                            history:
                                messages
                                    .slice(-20)
                                    .map(item => ({
                                        role:
                                            item.role,
                                        content:
                                            item.content
                                    }))
                        })
                    }
                );


            if (!response.ok) {

                throw new Error(
                    `Server error: ${response.status}`
                );
            }


            const data =
                await response.json();


            if (
                !data ||
                data.success !== true ||
                !data.reply
            ) {

                throw new Error(
                    data?.error ||
                    "The AI did not return an answer."
                );
            }


            addMessage(
                "ai",
                data.reply
            );


            updateConversationList(text);

        } catch (error) {

            console.error(
                "AI request failed:",
                error
            );

            addMessage(
                "ai",
                "I could not connect to the farming AI service right now. Please check your internet connection and make sure the AI PHP endpoint is configured correctly."
            );

        } finally {

            showTyping(false);

            sendButton.disabled = false;

            messageInput.focus();
        }
    }


    /* =====================================================
       TYPING
    ===================================================== */

    function showTyping(show) {

        if (!typingIndicator) return;

        typingIndicator.hidden =
            !show;

        if (show) {
            scrollToBottom();
        }
    }


    /* =====================================================
       CHARACTER COUNT
    ===================================================== */

    function updateCharacterCount() {

        const length =
            messageInput.value.length;

        characterCount.textContent =
            `${length} / ${maxLength}`;

        if (length > maxLength * 0.9) {

            characterCount.style.color =
                "#c43d3d";

        } else {

            characterCount.style.color =
                "";
        }
    }


    /* =====================================================
       AUTO RESIZE TEXTAREA
    ===================================================== */

    function autoResize() {

        messageInput.style.height =
            "auto";

        messageInput.style.height =
            Math.min(
                messageInput.scrollHeight,
                160
            ) + "px";
    }


    /* =====================================================
       SUGGESTED QUESTIONS
    ===================================================== */

    document.addEventListener(
        "click",
        event => {

            const button =
                event.target.closest(
                    "[data-prompt]"
                );

            if (!button) return;

            const prompt =
                button.dataset.prompt;

            if (!prompt) return;

            messageInput.value =
                prompt;

            updateCharacterCount();

            autoResize();

            messageInput.focus();

        }
    );


    /* =====================================================
       TOPIC BUTTONS
    ===================================================== */

    document.querySelectorAll(
        ".topic-button"
    ).forEach(button => {

        button.addEventListener(
            "click",
            () => {

                const prompt =
                    button.dataset.prompt;

                if (!prompt) return;

                messageInput.value =
                    prompt;

                updateCharacterCount();

                autoResize();

                messageInput.focus();

                if (
                    window.innerWidth <= 992
                ) {
                    closeSidebar();
                }
            }
        );
    });


    /* =====================================================
       SIDEBAR
    ===================================================== */

    function openSidebar() {

        sidebar.classList.add(
            "open"
        );

        document.body.classList.add(
            "sidebar-open"
        );
    }


    function closeSidebar() {

        sidebar.classList.remove(
            "open"
        );

        document.body.classList.remove(
            "sidebar-open"
        );
    }


    openSidebarButton?.addEventListener(
        "click",
        openSidebar
    );


    closeSidebarButton?.addEventListener(
        "click",
        closeSidebar
    );


    /* =====================================================
       NEW CONVERSATION
    ===================================================== */

    function newConversation() {

        if (
            messages.length &&
            !confirm(
                "Start a new farming conversation?"
            )
        ) {
            return;
        }

        conversationId =
            generateId();

        messages = [];

        localStorage.setItem(
            "agro_ai_conversation_id",
            conversationId
        );

        chatMessages.innerHTML = "";

        if (welcomeScreen) {

            chatMessages.appendChild(
                welcomeScreen
            );

            welcomeScreen.hidden =
                false;
        }

        renderConversationList();

        closeSidebar();

        messageInput.focus();
    }


    newChatButton?.addEventListener(
        "click",
        newConversation
    );


    /* =====================================================
       CLEAR CHAT
    ===================================================== */

    clearChatButton?.addEventListener(
        "click",
        () => {

            if (!messages.length) {
                return;
            }

            if (
                !confirm(
                    "Clear this conversation?"
                )
            ) {
                return;
            }

            messages = [];

            localStorage.removeItem(
                `agro_ai_messages_${conversationId}`
            );

            chatMessages.innerHTML = "";

            if (welcomeScreen) {

                chatMessages.appendChild(
                    welcomeScreen
                );

                welcomeScreen.hidden =
                    false;
            }
        }
    );


    /* =====================================================
       CONVERSATION LIST
    ===================================================== */

    function updateConversationList(
        firstQuestion
    ) {

        const existing =
            conversations.find(
                item =>
                    item.id ===
                    conversationId
            );

        const title =
            firstQuestion.length > 45
                ? firstQuestion.substring(
                    0,
                    45
                ) + "..."
                : firstQuestion;

        if (existing) {

            existing.title =
                existing.title ||
                title;

        } else {

            conversations.unshift({

                id:
                    conversationId,

                title:
                    title,

                created:
                    Date.now()

            });
        }

        conversations =
            conversations.slice(
                0,
                30
            );

        saveConversationList();

        renderConversationList();
    }


    function renderConversationList() {

        if (!conversationList) {
            return;
        }

        conversationList.innerHTML =
            "";

        if (!conversations.length) {

            const empty =
                document.createElement(
                    "div"
                );

            empty.className =
                "conversation-empty";

            empty.textContent =
                "No conversations yet.";

            conversationList.appendChild(
                empty
            );

            return;
        }


        conversations.forEach(
            conversation => {

                const button =
                    document.createElement(
                        "button"
                    );

                button.type =
                    "button";

                button.className =
                    "conversation-item";

                if (
                    conversation.id ===
                    conversationId
                ) {

                    button.classList.add(
                        "active"
                    );
                }

                button.innerHTML =
                    `<i class="fa-regular fa-message"></i>
                     ${escapeHtml(
                         conversation.title
                     )}`;

                button.addEventListener(
                    "click",
                    () => {

                        loadConversationById(
                            conversation.id
                        );
                    }
                );

                conversationList.appendChild(
                    button
                );
            }
        );
    }


    /* =====================================================
       LOAD OLD CONVERSATION
    ===================================================== */

    function loadConversationById(
        id
    ) {

        conversationId =
            id;

        messages = [];

        chatMessages.innerHTML =
            "";

        const saved =
            localStorage.getItem(
                `agro_ai_messages_${id}`
            );

        if (saved) {

            try {

                messages =
                    JSON.parse(saved);

            } catch {

                messages = [];
            }
        }

        if (messages.length) {

            messages.forEach(
                message => {

                    renderMessage(
                        message.role,
                        message.content,
                        message.time,
                        false
                    );
                }
            );

        } else {

            chatMessages.appendChild(
                welcomeScreen
            );

            welcomeScreen.hidden =
                false;
        }

        localStorage.setItem(
            "agro_ai_conversation_id",
            conversationId
        );

        renderConversationList();

        closeSidebar();

        scrollToBottom();
    }


    /* =====================================================
       THEME
    ===================================================== */

    function loadTheme() {

        const theme =
            localStorage.getItem(
                "agro_ai_theme"
            ) || "light";

        document.documentElement
            .setAttribute(
                "data-theme",
                theme
            );

        updateThemeIcon(
            theme
        );
    }


    function updateThemeIcon(
        theme
    ) {

        const icon =
            themeButton?.querySelector(
                "i"
            );

        if (!icon) return;

        icon.className =
            theme === "dark"
                ? "fa-solid fa-sun"
                : "fa-solid fa-moon";
    }


    themeButton?.addEventListener(
        "click",
        () => {

            const current =
                document.documentElement
                    .getAttribute(
                        "data-theme"
                    ) ||
                "light";

            const next =
                current === "dark"
                    ? "light"
                    : "dark";

            document.documentElement
                .setAttribute(
                    "data-theme",
                    next
                );

            localStorage.setItem(
                "agro_ai_theme",
                next
            );

            updateThemeIcon(
                next
            );
        }
    );


    /* =====================================================
       VOICE INPUT
    ===================================================== */

    let recognition = null;

    const SpeechRecognition =
        window.SpeechRecognition ||
        window.webkitSpeechRecognition;

    if (SpeechRecognition) {

        recognition =
            new SpeechRecognition();

        recognition.lang =
            "en-US";

        recognition.continuous =
            false;

        recognition.interimResults =
            false;


        recognition.onstart =
            () => {

                voiceButton.classList.add(
                    "recording"
                );

                voiceButton.innerHTML =
                    '<i class="fa-solid fa-stop"></i>';
            };


        recognition.onresult =
            event => {

                const transcript =
                    event.results[0][0]
                        .transcript;

                messageInput.value +=
                    (
                        messageInput.value
                            ? " "
                            : ""
                    ) +
                    transcript;

                updateCharacterCount();

                autoResize();
            };


        recognition.onerror =
            () => {

                showToast(
                    "Voice input could not be used.",
                    "error"
                );
            };


        recognition.onend =
            () => {

                voiceButton.classList.remove(
                    "recording"
                );

                voiceButton.innerHTML =
                    '<i class="fa-solid fa-microphone"></i>';
            };


        voiceButton?.addEventListener(
            "click",
            () => {

                try {

                    recognition.start();

                } catch {

                    recognition.stop();
                }
            }
        );

    } else {

        voiceButton?.addEventListener(
            "click",
            () => {

                showToast(
                    "Voice input is not supported by this browser.",
                    "error"
                );
            }
        );
    }


    /* =====================================================
       TOAST
    ===================================================== */

    function showToast(
        message,
        type = "success"
    ) {

        let container =
            document.getElementById(
                "toastContainer"
            );

        if (!container) {

            container =
                document.createElement(
                    "div"
                );

            container.id =
                "toastContainer";

            container.className =
                "toast-container";

            document.body.appendChild(
                container
            );
        }

        const toast =
            document.createElement(
                "div"
            );

        toast.className =
            `toast toast-${type}`;

        toast.textContent =
            message;

        container.appendChild(
            toast
        );

        setTimeout(
            () => {

                toast.classList.add(
                    "hide"
                );

                setTimeout(
                    () => toast.remove(),
                    300
                );

            },
            3000
        );
    }


    /* =====================================================
       SEND BUTTON
    ===================================================== */

    sendButton?.addEventListener(
        "click",
        sendMessage
    );


    /* =====================================================
       ENTER TO SEND
    ===================================================== */

    messageInput?.addEventListener(
        "keydown",
        event => {

            if (
                event.key === "Enter" &&
                !event.shiftKey
            ) {

                event.preventDefault();

                sendMessage();
            }
        }
    );


    /* =====================================================
       TEXTAREA EVENTS
    ===================================================== */

    messageInput?.addEventListener(
        "input",
        () => {

            updateCharacterCount();

            autoResize();
        }
    );


    /* =====================================================
       INITIALIZE
    ===================================================== */

    loadTheme();

    renderConversationList();

    loadConversation();

    updateCharacterCount();

    autoResize();

});