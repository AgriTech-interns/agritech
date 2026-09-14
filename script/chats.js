"use strict";

const state = {
    currentUserId: Number(CHAT_CONFIG.currentUserId),
    activeUserId: null,
    activeUserName: "",
    messages: [],
    replyTo: null,
    contextMessageId: null,
    lastMessageId: 0,
    pollTimer: null,
    typingTimer: null,
    isTyping: false
};


/*
|--------------------------------------------------------------------------
| DOM
|--------------------------------------------------------------------------
*/

const app = document.querySelector(".chat-app");

const welcomeScreen =
    document.getElementById("welcomeScreen");

const activeChat =
    document.getElementById("activeChat");

const contactsList =
    document.getElementById("contactsList");

const messagesArea =
    document.getElementById("messagesArea");

const messageInput =
    document.getElementById("messageInput");

const sendBtn =
    document.getElementById("sendBtn");

const emojiBtn =
    document.getElementById("emojiBtn");

const emojiPicker =
    document.getElementById("emojiPicker");

const attachmentBtn =
    document.getElementById("attachmentBtn");

const attachmentInput =
    document.getElementById("attachmentInput");

const contactModal =
    document.getElementById("contactModal");

const contactForm =
    document.getElementById("contactForm");

const contactIdentifier =
    document.getElementById("contactIdentifier");

const contactResults =
    document.getElementById("contactResults");

const replyPreview =
    document.getElementById("replyPreview");

const replyText =
    document.getElementById("replyText");

const contextMenu =
    document.getElementById("contextMenu");


/*
|--------------------------------------------------------------------------
| Utility
|--------------------------------------------------------------------------
*/

function escapeHTML(value) {

    const div = document.createElement("div");

    div.textContent = value ?? "";

    return div.innerHTML;
}


function formatTime(dateString) {

    if (!dateString) {
        return "";
    }

    const date = new Date(
        dateString.replace(" ", "T")
    );

    if (Number.isNaN(date.getTime())) {
        return "";
    }

    return date.toLocaleTimeString([], {
        hour: "2-digit",
        minute: "2-digit"
    });
}


function formatDay(dateString) {

    const date = new Date(
        dateString.replace(" ", "T")
    );

    if (Number.isNaN(date.getTime())) {
        return "";
    }

    const today = new Date();

    if (
        date.toDateString() ===
        today.toDateString()
    ) {
        return "Today";
    }

    const yesterday = new Date();

    yesterday.setDate(
        yesterday.getDate() - 1
    );

    if (
        date.toDateString() ===
        yesterday.toDateString()
    ) {
        return "Yesterday";
    }

    return date.toLocaleDateString([], {
        day: "numeric",
        month: "long",
        year: "numeric"
    });
}


/*
|--------------------------------------------------------------------------
| Contacts
|--------------------------------------------------------------------------
*/

document
    .querySelectorAll(".contact-item")
    .forEach(item => {

        item.addEventListener("click", () => {

            const id =
                Number(item.dataset.id);

            const name =
                item.dataset.name;

            openChat(id, name);
        });
    });


async function openChat(userId, userName) {

    state.activeUserId = userId;

    state.activeUserName = userName;

    state.messages = [];

    state.lastMessageId = 0;

    welcomeScreen.style.display = "none";

    activeChat.classList.add("visible");

    app.classList.add("chat-open");

    document.getElementById("chatUserName")
        .textContent = userName;

    document.getElementById("chatAvatarLetter")
        .textContent =
        userName.charAt(0).toUpperCase();

    messagesArea.innerHTML =
        '<div class="message-loading">' +
        '<i class="fas fa-spinner fa-spin"></i>' +
        '</div>';

    await loadMessages();

    startPolling();

    updateUserStatus();
}


/*
|--------------------------------------------------------------------------
| Load Messages
|--------------------------------------------------------------------------
*/

async function loadMessages() {

    if (!state.activeUserId) {
        return;
    }

    try {

        const url =
            `${CHAT_CONFIG.getMessagesUrl}` +
            `?action=get_messages` +
            `&user_id=${state.activeUserId}` +
            `&last_id=0`;

        const response =
            await fetch(url, {
                credentials: "same-origin"
            });

        const data =
            await response.json();

        if (!data.success) {
            throw new Error(data.message);
        }

        state.messages =
            data.messages || [];

        if (state.messages.length) {
            state.lastMessageId =
                Number(
                    state.messages[
                        state.messages.length - 1
                    ].id
                );
        }

        renderMessages();

        if (data.status) {
            updateChatStatus(data.status);
        }

        markMessagesSeen();

    } catch (error) {

        console.error(error);

        messagesArea.innerHTML = `
            <div class="empty-contacts">
                <i class="fas fa-exclamation-circle"></i>
                <p>Unable to load messages.</p>
            </div>
        `;
    }
}


/*
|--------------------------------------------------------------------------
| Poll Messages
|--------------------------------------------------------------------------
*/

function startPolling() {
    stopPolling();
    state.pollTimer =
        setInterval(async () => {
            if (!state.activeUserId) {
                return;
            }
            await pollMessages();
            await updateUserStatus();
        }, 2500);
}


function stopPolling() {
    if (state.pollTimer) {
        clearInterval(
            state.pollTimer
        );
        state.pollTimer = null;
    }
}


async function pollMessages() {

    try {

        const url =
            `${CHAT_CONFIG.getMessagesUrl}` +
            `?action=get_messages` +
            `&user_id=${state.activeUserId}` +
            `&last_id=${state.lastMessageId}`;

        const response =
            await fetch(url, {
                credentials: "same-origin"
            });

        const data =
            await response.json();

        if (!data.success) {
            return;
        }

        if (
            data.messages &&
            data.messages.length
        ) {

            state.messages.push(
                ...data.messages
            );

            state.lastMessageId =
                Number(
                    data.messages[
                        data.messages.length - 1
                    ].id
                );

            renderMessages();
            markMessagesSeen();
        }

        if (data.status) {
            updateChatStatus(data.status);
        }

    } catch (error) {
        console.error(error);
    }
}


/*
|--------------------------------------------------------------------------
| Render
|--------------------------------------------------------------------------
*/

function renderMessages() {
    messagesArea.innerHTML = "";
    let lastDay = "";
    state.messages.forEach(message => {
        const day =
            formatDay(message.created_at);
        if (day !== lastDay) {
            const dayElement =
                document.createElement("div");
            dayElement.className =
                "message-day";
            dayElement.innerHTML =
                `<span>${escapeHTML(day)}</span>`;
            messagesArea.appendChild(
                dayElement
            );

            lastDay = day;
        }

        messagesArea.appendChild(
            createMessageElement(message)
        );
    });

    scrollMessages();
}


function createMessageElement(message) {

    const own =
        Number(message.sender_id) ===
        state.currentUserId;

    const row =
        document.createElement("div");

    row.className =
        `message-row ${own ? "own" : "other"}`;

    row.dataset.id =
        message.id;

    const bubble =
        document.createElement("div");

    bubble.className =
        "message-bubble";

    let html = "";


    /*
    | Reply
    */

    if (message.reply_to_text) {

        html += `
            <div class="reply-message">
                <strong>
                    ${escapeHTML(
                        message.reply_to_name || "Message"
                    )}
                </strong>

                <p>
                    ${escapeHTML(
                        message.reply_to_text
                    )}
                </p>
            </div>
        `;
    }


    /*
    | Attachment
    */

    if (
        message.attachment_data &&
        message.message_type === "image"
    ) {

        html += `
            <div class="message-attachment">
                <img
                    src="${message.attachment_data}"
                    alt="Image"
                    loading="lazy"
                >
            </div>
        `;
    }

    else if (
        message.attachment_name
    ) {

        html += `
            <div class="file-attachment">
                <i class="fas fa-file"></i>

                <span>
                    ${escapeHTML(
                        message.attachment_name
                    )}
                </span>
            </div>
        `;
    }


    /*
    | Message
    */

    if (
        message.message &&
        !message.is_deleted
    ) {

        html += `
            <span class="message-text">
                ${escapeHTML(message.message)}
            </span>
        `;
    }

    else if (message.is_deleted) {

        html += `
            <span class="message-text">
                <i>
                    This message was deleted
                </i>
            </span>
        `;
    }


    /*
    | Meta
    */

    html += `
        <span class="message-meta">

            <span class="message-time">
                ${formatTime(message.created_at)}
            </span>

            ${
                message.is_edited
                    ? '<span class="message-edited">edited</span>'
                    : ''
            }

            ${
                own
                    ? `
                        <span class="message-status
                            ${
                                message.seen_at
                                    ? "seen"
                                    : ""
                            }">

                            ${
                                message.seen_at
                                    ? "✓✓"
                                    : message.delivered_at
                                        ? "✓✓"
                                        : "✓"
                            }

                        </span>
                    `
                    : ""
            }

        </span>
    `;

    bubble.innerHTML = html;


    /*
    | Reaction
    */

    if (message.reaction) {

        const reaction =
            document.createElement("span");

        reaction.className =
            "message-reaction";

        reaction.textContent =
            message.reaction;

        bubble.appendChild(
            reaction
        );
    }


    row.appendChild(bubble);


    /*
    | Right click menu
    */

    row.addEventListener(
        "contextmenu",
        event => {

            event.preventDefault();

            showContextMenu(
                event.clientX,
                event.clientY,
                message.id
            );
        }
    );


    /*
    | Long press
    */

    let pressTimer;

    row.addEventListener(
        "touchstart",
        event => {

            pressTimer =
                setTimeout(() => {

                    const touch =
                        event.touches[0];

                    showContextMenu(
                        touch.clientX,
                        touch.clientY,
                        message.id
                    );

                }, 600);
        }
    );

    row.addEventListener(
        "touchend",
        () => {
            clearTimeout(pressTimer);
        }
    );

    return row;
}


function scrollMessages() {

    messagesArea.scrollTop =
        messagesArea.scrollHeight;
}


/*
|--------------------------------------------------------------------------
| Send Message
|--------------------------------------------------------------------------
*/

async function sendMessage() {

    const text =
        messageInput.value.trim();

    const files =
        Array.from(
            attachmentInput.files
        );

    if (
        !text &&
        !files.length
    ) {
        return;
    }

    if (!state.activeUserId) {
        return;
    }

    sendBtn.disabled = true;


    try {

        /*
        | Send text
        */

        if (text) {

            await sendSingleMessage({
                message: text,
                message_type: "text",
                reply_to:
                    state.replyTo
                        ? state.replyTo.id
                        : ""
            });
        }


        /*
        | Send files
        */

        for (const file of files) {

            await sendFile(file);
        }


        messageInput.value = "";

        attachmentInput.value = "";

        messageInput.style.height = "auto";

        cancelReply();

        await loadMessages();

    } catch (error) {

        console.error(error);

        alert(
            error.message ||
            "Unable to send message."
        );

    } finally {

        sendBtn.disabled = false;
    }
}


async function sendSingleMessage(data) {

    const formData =
        new FormData();

    formData.append(
        "action",
        "send_message"
    );

    formData.append(
        "receiver_id",
        state.activeUserId
    );

    formData.append(
        "message",
        data.message || ""
    );

    formData.append(
        "message_type",
        data.message_type || "text"
    );

    formData.append(
        "reply_to",
        data.reply_to || ""
    );

    const response =
        await fetch(
            CHAT_CONFIG.sendMessageUrl,
            {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            }
        );

    const result =
        await response.json();

    if (!result.success) {
        throw new Error(
            result.message ||
            "Message could not be sent."
        );
    }

    return result;
}


async function sendFile(file) {

    if (
        file.size >
        10 * 1024 * 1024
    ) {

        throw new Error(
            `${file.name} is larger than 10MB.`
        );
    }

    /*
    | Images are converted to data URLs.
    | This keeps the project within the requested
    | six-file structure.
    */

    if (file.type.startsWith("image/")) {

        const data =
            await fileToDataURL(file);

        const formData =
            new FormData();

        formData.append(
            "action",
            "send_message"
        );

        formData.append(
            "receiver_id",
            state.activeUserId
        );

        formData.append(
            "message",
            ""
        );

        formData.append(
            "message_type",
            "image"
        );

        formData.append(
            "attachment_name",
            file.name
        );

        formData.append(
            "attachment_data",
            data
        );

        const response =
            await fetch(
                CHAT_CONFIG.sendMessageUrl,
                {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                }
            );

        const result =
            await response.json();

        if (!result.success) {
            throw new Error(
                result.message
            );
        }

        return;
    }


    /*
    | Non-image files
    */

    const data =
        await fileToDataURL(file);

    const formData =
        new FormData();

    formData.append(
        "action",
        "send_message"
    );

    formData.append(
        "receiver_id",
        state.activeUserId
    );

    formData.append(
        "message",
        ""
    );

    formData.append(
        "message_type",
        "file"
    );

    formData.append(
        "attachment_name",
        file.name
    );

    formData.append(
        "attachment_data",
        data
    );

    const response =
        await fetch(
            CHAT_CONFIG.sendMessageUrl,
            {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            }
        );

    const result =
        await response.json();

    if (!result.success) {
        throw new Error(
            result.message
        );
    }
}


function fileToDataURL(file) {

    return new Promise(
        (resolve, reject) => {

            const reader =
                new FileReader();

            reader.onload = () =>
                resolve(reader.result);

            reader.onerror =
                reject;

            reader.readAsDataURL(file);
        }
    );
}


/*
|--------------------------------------------------------------------------
| Reply
|--------------------------------------------------------------------------
*/

function setReply(message) {

    state.replyTo =
        message;

    replyText.textContent =
        message.message ||
        "Attachment";

    replyPreview.classList.add(
        "visible"
    );

    messageInput.focus();
}


function cancelReply() {

    state.replyTo = null;

    replyPreview.classList.remove(
        "visible"
    );
}


/*
|--------------------------------------------------------------------------
| Context Menu
|--------------------------------------------------------------------------
*/

function showContextMenu(
    x,
    y,
    messageId
) {

    state.contextMessageId =
        Number(messageId);

    contextMenu.style.left =
        `${Math.min(
            x,
            window.innerWidth - 180
        )}px`;

    contextMenu.style.top =
        `${Math.min(
            y,
            window.innerHeight - 250
        )}px`;

    contextMenu.classList.add(
        "visible"
    );
}


function hideContextMenu() {

    contextMenu.classList.remove(
        "visible"
    );

    state.contextMessageId =
        null;
}


contextMenu.addEventListener(
    "click",
    async event => {

        const button =
            event.target.closest(
                "button"
            );

        if (!button) {
            return;
        }

        const action =
            button.dataset.action;

        const message =
            state.messages.find(
                m =>
                    Number(m.id) ===
                    Number(
                        state.contextMessageId
                    )
            );

        hideContextMenu();

        if (!message) {
            return;
        }


        if (action === "reply") {

            setReply(message);
        }


        else if (action === "copy") {

            if (message.message) {

                await navigator.clipboard
                    .writeText(
                        message.message
                    );
            }
        }


        else if (action === "edit") {

            if (
                Number(message.sender_id) !==
                state.currentUserId
            ) {
                return;
            }

            const edited =
                prompt(
                    "Edit message:",
                    message.message
                );

            if (
                edited !== null &&
                edited.trim()
            ) {

                await modifyMessage(
                    "edit_message",
                    message.id,
                    edited.trim()
                );
            }
        }


        else if (action === "delete") {

            if (
                Number(message.sender_id) !==
                state.currentUserId
            ) {
                return;
            }

            if (
                confirm(
                    "Delete this message?"
                )
            ) {

                await modifyMessage(
                    "delete_message",
                    message.id
                );
            }
        }


        else if (action === "react") {

            const reaction =
                prompt(
                    "Enter an emoji reaction:",
                    "❤️"
                );

            if (reaction) {

                await modifyMessage(
                    "react_message",
                    message.id,
                    reaction
                );
            }
        }
    }
);


/*
|--------------------------------------------------------------------------
| Modify message
|--------------------------------------------------------------------------
*/

async function modifyMessage(
    action,
    messageId,
    value = ""
) {

    const formData =
        new FormData();

    formData.append(
        "action",
        action
    );

    formData.append(
        "message_id",
        messageId
    );

    if (action === "edit_message") {

        formData.append(
            "message",
            value
        );
    }

    if (action === "react_message") {

        formData.append(
            "reaction",
            value
        );
    }

    const response =
        await fetch(
            CHAT_CONFIG.sendMessageUrl,
            {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            }
        );

    const result =
        await response.json();

    if (!result.success) {

        alert(
            result.message ||
            "Operation failed."
        );

        return;
    }

    await loadMessages();
}


/*
|--------------------------------------------------------------------------
| Read receipts
|--------------------------------------------------------------------------
*/

async function markMessagesSeen() {

    if (!state.activeUserId) {
        return;
    }

    const formData =
        new FormData();

    formData.append(
        "action",
        "mark_seen"
    );

    formData.append(
        "user_id",
        state.activeUserId
    );

    try {

        await fetch(
            CHAT_CONFIG.sendMessageUrl,
            {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            }
        );

    } catch (error) {

        console.error(error);
    }
}


/*
|--------------------------------------------------------------------------
| Typing
|--------------------------------------------------------------------------
*/

messageInput.addEventListener(
    "input",
    () => {

        messageInput.style.height =
            "auto";

        messageInput.style.height =
            Math.min(
                messageInput.scrollHeight,
                120
            ) + "px";

        setTyping(true);

        clearTimeout(
            state.typingTimer
        );

        state.typingTimer =
            setTimeout(() => {

                setTyping(false);

            }, 1500);
    }
);


async function setTyping(value) {

    if (
        !state.activeUserId ||
        state.isTyping === value
    ) {
        return;
    }

    state.isTyping = value;

    const formData =
        new FormData();

    formData.append(
        "action",
        "typing"
    );

    formData.append(
        "typing_to",
        state.activeUserId
    );

    formData.append(
        "value",
        value ? "1" : "0"
    );

    try {

        await fetch(
            CHAT_CONFIG.sendMessageUrl,
            {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            }
        );

    } catch (error) {
        console.error(error);
    }
}


/*
|--------------------------------------------------------------------------
| User status
|--------------------------------------------------------------------------
*/

async function updateUserStatus() {

    if (!state.activeUserId) {
        return;
    }

    try {

        const url =
            `${CHAT_CONFIG.getMessagesUrl}` +
            `?action=status` +
            `&user_id=${state.activeUserId}`;

        const response =
            await fetch(url, {
                credentials: "same-origin"
            });

        const data =
            await response.json();

        if (
            data.success &&
            data.status
        ) {

            updateChatStatus(
                data.status
            );
        }

    } catch (error) {
        console.error(error);
    }
}


function updateChatStatus(status) {

    const statusText =
        document.getElementById(
            "chatUserStatus"
        );

    const dot =
        document.getElementById(
            "chatOnlineDot"
        );

    if (status.is_typing) {

        statusText.textContent =
            "typing...";

        dot.classList.add(
            "online"
        );

        document
            .getElementById(
                "typingIndicator"
            )
            .classList.add(
                "visible"
            );

        return;
    }

    document
        .getElementById(
            "typingIndicator"
        )
        .classList.remove(
            "visible"
        );

    if (Number(status.is_online) === 1) {

        statusText.textContent =
            "online";

        dot.classList.add(
            "online"
        );

    } else {

        dot.classList.remove(
            "online"
        );

        statusText.textContent =
            status.last_seen
                ? `last seen ${formatTime(
                    status.last_seen
                )}`
                : "offline";
    }
}


/*
|--------------------------------------------------------------------------
| Emoji
|--------------------------------------------------------------------------
*/

emojiBtn.addEventListener(
    "click",
    () => {

        emojiPicker.classList.toggle(
            "visible"
        );
    }
);


document
    .querySelectorAll(".emoji-btn")
    .forEach(button => {

        button.addEventListener(
            "click",
            () => {

                messageInput.value +=
                    button.textContent;

                messageInput.focus();

                emojiPicker.classList.remove(
                    "visible"
                );
            }
        );
    });


/*
|--------------------------------------------------------------------------
| Attachments
|--------------------------------------------------------------------------
*/

attachmentBtn.addEventListener(
    "click",
    () => {

        attachmentInput.click();
    }
);


/*
|--------------------------------------------------------------------------
| Send
|--------------------------------------------------------------------------
*/

sendBtn.addEventListener(
    "click",
    sendMessage
);


messageInput.addEventListener(
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


/*
|--------------------------------------------------------------------------
| Reply cancel
|--------------------------------------------------------------------------
*/

document
    .getElementById("cancelReply")
    .addEventListener(
        "click",
        cancelReply
    );


/*
|--------------------------------------------------------------------------
| Contact modal
|--------------------------------------------------------------------------
*/

function openContactModal() {

    contactModal.classList.add(
        "visible"
    );

    contactIdentifier.focus();
}


function closeContactModal() {

    contactModal.classList.remove(
        "visible"
    );

    contactIdentifier.value = "";

    contactResults.innerHTML = "";
}


document
    .getElementById("newContactBtn")
    .addEventListener(
        "click",
        openContactModal
    );


document
    .getElementById("emptyAddContact")
    ?.addEventListener(
        "click",
        openContactModal
    );


document
    .getElementById("closeContactModal")
    .addEventListener(
        "click",
        closeContactModal
    );


contactModal.addEventListener(
    "click",
    event => {

        if (
            event.target ===
            contactModal
        ) {

            closeContactModal();
        }
    }
);


/*
|--------------------------------------------------------------------------
| Contact search
|--------------------------------------------------------------------------
*/

let contactSearchTimer;

contactIdentifier.addEventListener(
    "input",
    () => {

        clearTimeout(
            contactSearchTimer
        );

        const value =
            contactIdentifier.value.trim();

        if (!value) {

            contactResults.innerHTML =
                "";

            return;
        }

        contactSearchTimer =
            setTimeout(
                () => searchContacts(value),
                400
            );
    }
);


async function searchContacts(value) {

    const formData =
        new FormData();

    formData.append(
        "action",
        "search"
    );

    formData.append(
        "identifier",
        value
    );

    try {

        const response =
            await fetch(
                CHAT_CONFIG.addContactUrl,
                {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                }
            );

        const data =
            await response.json();

        if (
            !data.success ||
            !data.users?.length
        ) {

            contactResults.innerHTML =
                "<p>No users found.</p>";

            return;
        }

        contactResults.innerHTML =
            data.users.map(user => `

                <div class="contact-item"
                     style="border:1px solid #e2e8e4;
                            border-radius:8px;
                            margin-bottom:6px;">

                    <div class="contact-avatar">

                        ${escapeHTML(
                            user.fullname
                                .charAt(0)
                                .toUpperCase()
                        )}

                    </div>

                    <div class="contact-info">

                        <strong>
                            ${escapeHTML(
                                user.fullname
                            )}
                        </strong>

                        <small>
                            ${escapeHTML(
                                user.role || ""
                            )}
                        </small>

                    </div>

                    <button
                        class="primary-btn select-contact"
                        data-id="${user.id}"
                    >
                        Add
                    </button>

                </div>

            `).join("");

    } catch (error) {

        console.error(error);

        contactResults.innerHTML =
            "<p>Search failed.</p>";
    }
}


/*
|--------------------------------------------------------------------------
| Add Contact
|--------------------------------------------------------------------------
*/

contactResults.addEventListener(
    "click",
    async event => {

        const button =
            event.target.closest(
                ".select-contact"
            );

        if (!button) {
            return;
        }

        const contactId =
            Number(button.dataset.id);

        const formData =
            new FormData();

        formData.append(
            "action",
            "add_contact"
        );

        formData.append(
            "contact_id",
            contactId
        );

        try {

            const response =
                await fetch(
                    CHAT_CONFIG.addContactUrl,
                    {
                        method: "POST",
                        body: formData,
                        credentials: "same-origin"
                    }
                );

            const data =
                await response.json();

            if (!data.success) {

                alert(data.message);

                return;
            }

            location.reload();

        } catch (error) {

            console.error(error);

            alert(
                "Unable to add contact."
            );
        }
    }
);


contactForm.addEventListener(
    "submit",
    event => {

        event.preventDefault();

        const firstResult =
            contactResults.querySelector(
                ".select-contact"
            );

        if (firstResult) {
            firstResult.click();
        }
    }
);


/*
|--------------------------------------------------------------------------
| Contact filtering
|--------------------------------------------------------------------------
*/

const contactSearch =
    document.getElementById(
        "contactSearch"
    );

contactSearch.addEventListener(
    "input",
    () => {

        const query =
            contactSearch.value
                .toLowerCase()
                .trim();

        document
            .querySelectorAll(
                ".contact-item"
            )
            .forEach(item => {

                const name =
                    item.dataset.name
                        ?.toLowerCase() || "";

                item.style.display =
                    name.includes(query)
                        ? "flex"
                        : "none";
            });
    }
);


/*
|--------------------------------------------------------------------------
| Search button
|--------------------------------------------------------------------------
*/

document
    .getElementById("searchBtn")
    .addEventListener(
        "click",
        () => {

            document
                .getElementById(
                    "searchBox"
                )
                .classList.toggle(
                    "visible"
                );

            contactSearch.focus();
        }
    );


/*
|--------------------------------------------------------------------------
| Message search
|--------------------------------------------------------------------------
*/

document
    .getElementById(
        "messageSearchBtn"
    )
    .addEventListener(
        "click",
        () => {

            document
                .getElementById(
                    "messageSearch"
                )
                .classList.add(
                    "visible"
                );
        }
    );


document
    .getElementById(
        "closeMessageSearch"
    )
    .addEventListener(
        "click",
        () => {

            document
                .getElementById(
                    "messageSearch"
                )
                .classList.remove(
                    "visible"
                );

            renderMessages();
        }
    );


document
    .getElementById(
        "messageSearchInput"
    )
    .addEventListener(
        "input",
        event => {

            const query =
                event.target.value
                    .toLowerCase()
                    .trim();

            if (!query) {

                renderMessages();

                return;
            }

            const filtered =
                state.messages.filter(
                    message =>
                        (
                            message.message ||
                            ""
                        )
                        .toLowerCase()
                        .includes(query)
                );

            messagesArea.innerHTML = "";

            filtered.forEach(
                message => {

                    messagesArea.appendChild(
                        createMessageElement(
                            message
                        )
                    );
                }
            );
        }
    );


/*
|--------------------------------------------------------------------------
| Back buttons
|--------------------------------------------------------------------------
*/

document
    .getElementById("chatBack")
    .addEventListener(
        "click",
        closeChat
    );


document
    .getElementById("mobileBack")
    ?.addEventListener(
        "click",
        closeChat
    );


function closeChat() {

    stopPolling();

    state.activeUserId = null;

    activeChat.classList.remove(
        "visible"
    );

    welcomeScreen.style.display =
        "flex";

    app.classList.remove(
        "chat-open"
    );
}


/*
|--------------------------------------------------------------------------
| Voice/video placeholders
|--------------------------------------------------------------------------
*/

document
    .getElementById("voiceCallBtn")
    .addEventListener(
        "click",
        () => {

            alert(
                "Voice calling requires WebRTC signaling. " +
                "The chat messaging system is ready for it."
            );
        }
    );


document
    .getElementById("videoCallBtn")
    .addEventListener(
        "click",
        () => {

            alert(
                "Video calling requires WebRTC signaling. " +
                "The chat messaging system is ready for it."
            );
        }
    );


/*
|--------------------------------------------------------------------------
| Chat menu
|--------------------------------------------------------------------------
*/

document
    .getElementById("chatMenuBtn")
    .addEventListener(
        "click",
        () => {

            alert(
                "Chat options: Search messages, " +
                "mute, clear chat and contact information."
            );
        }
    );


/*
|--------------------------------------------------------------------------
| Hide context menu
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "click",
    event => {

        if (
            !event.target.closest(
                ".context-menu"
            )
        ) {

            hideContextMenu();
        }

        if (
            !event.target.closest(
                ".emoji-picker"
            ) &&
            !event.target.closest(
                "#emojiBtn"
            )
        ) {

            emojiPicker.classList.remove(
                "visible"
            );
        }
    }
);


/*
|--------------------------------------------------------------------------
| Mark current user offline when leaving
|--------------------------------------------------------------------------
*/

window.addEventListener(
    "beforeunload",
    () => {

        const data =
            new URLSearchParams();

        data.append(
            "action",
            "offline"
        );

        navigator.sendBeacon(
            CHAT_CONFIG.sendMessageUrl,
            data
        );
    }
);