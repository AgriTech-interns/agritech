/**
 * AgriConnect front-end logic.
 * Talks to the PHP endpoints in /pages via fetch() and renders the chat UI.
 */

const app = document.querySelector('.app');
const currentUserId = parseInt(app.dataset.userId, 10);

const contactListEl = document.getElementById('contactList');
const chatEmpty = document.getElementById('chatEmpty');
const chatActive = document.getElementById('chatActive');
const chatContactName = document.getElementById('chatContactName');
const chatContactStatus = document.getElementById('chatContactStatus');
const messagesEl = document.getElementById('messages');
const messageForm = document.getElementById('messageForm');
const messageInput = document.getElementById('messageInput');
const addContactBtn = document.getElementById('addContactBtn');
const newContactPhone = document.getElementById('newContactPhone');
const addContactMsg = document.getElementById('addContactMsg');

let activeContactId = null;
let pollTimer = null;

// ---------- CONTACTS ----------

async function loadContacts() {
    try {
        const res = await fetch('get_contacts.php');
        const data = await res.json();
        renderContacts(data.contacts || []);
    } catch (err) {
        console.error('Failed to load contacts', err);
    }
}

function renderContacts(contacts) {
    contactListEl.innerHTML = '';

    if (contacts.length === 0) {
        contactListEl.innerHTML = '<li class="small-msg">No contacts yet. Add one above.</li>';
        return;
    }

    contacts.forEach(c => {
        const li = document.createElement('li');
        li.className = 'contact-item' + (c.id == activeContactId ? ' active' : '');
        li.dataset.id = c.id;
        li.innerHTML = `
            <span class="avatar">🧑‍🌾</span>
            <div class="contact-info">
                <strong>${escapeHtml(c.full_name)}</strong>
                <span>${escapeHtml(c.last_message || c.status || 'Say hello 👋')}</span>
            </div>
            <span class="online-dot ${c.is_online == 1 ? 'online' : ''}"></span>
        `;
        li.addEventListener('click', () => openChat(c.id, c.full_name, c.status));
        contactListEl.appendChild(li);
    });
}

addContactBtn.addEventListener('click', async () => {
    const phone = newContactPhone.value.trim();
    if (!phone) return;

    addContactMsg.textContent = 'Adding...';
    try {
        const res = await fetch('add_contact.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ phone_number: phone }),
        });
        const data = await res.json();

        if (data.error) {
            addContactMsg.textContent = data.error;
        } else {
            addContactMsg.textContent = `${data.contact_name} added ✅`;
            newContactPhone.value = '';
            loadContacts();
        }
    } catch (err) {
        addContactMsg.textContent = 'Something went wrong.';
    }
});

// ---------- CHAT ----------

function openChat(contactId, name, status) {
    activeContactId = contactId;
    chatEmpty.style.display = 'none';
    chatActive.style.display = 'flex';
    chatContactName.textContent = name;
    chatContactStatus.textContent = status || 'Available';

    document.querySelectorAll('.contact-item').forEach(el => {
        el.classList.toggle('active', el.dataset.id == contactId);
    });

    loadMessages();

    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(loadMessages, 3000);
}

async function loadMessages() {
    if (!activeContactId) return;
    try {
        const res = await fetch(`get_messages.php?contact_id=${activeContactId}`);
        const data = await res.json();
        renderMessages(data.messages || []);
    } catch (err) {
        console.error('Failed to load messages', err);
    }
}

function renderMessages(messages) {
    const wasAtBottom =
        messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight < 60;

    messagesEl.innerHTML = messages.map(m => {
        const out = m.sender_id == currentUserId;
        return `
            <div class="bubble ${out ? 'out' : 'in'}">
                ${escapeHtml(m.message)}
                <time>${formatTime(m.created_at)}</time>
            </div>
        `;
    }).join('');

    if (wasAtBottom) {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }
}

messageForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const text = messageInput.value.trim();
    if (!text || !activeContactId) return;

    messageInput.value = '';

    try {
        await fetch('send_message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ contact_id: activeContactId, message: text }),
        });
        loadMessages();
        loadContacts();
    } catch (err) {
        console.error('Failed to send message', err);
    }
});

// ---------- HELPERS ----------

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

function formatTime(datetime) {
    if (!datetime) return '';
    const d = new Date(datetime.replace(' ', 'T'));
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

// ---------- INIT ----------
loadContacts();
setInterval(loadContacts, 8000);
