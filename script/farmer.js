/* ==========================================================================
   AgriTech Farmer Dashboard — farmer.js
   ==========================================================================
   This file makes farm2.php fully interactive and data-driven, pulling
   everything from the PHP/MySQL backend for the CURRENTLY LOGGED-IN farmer.

   AUTH MODEL
   ----------
   This file assumes PHP session-based auth: the farmer already logged in
   through a separate login.php which calls session_start() and sets
   $_SESSION['farmer_id']. Every fetch() below is same-origin with
   credentials included, so the PHP session cookie travels automatically.
   Your PHP endpoints should each start with:

       <?php
       session_start();
       header('Content-Type: application/json');
       if (empty($_SESSION['farmer_id'])) {
           http_response_code(401);
           echo json_encode(['error' => 'Not authenticated']);
           exit;
       }
       $farmerId = $_SESSION['farmer_id'];
       // ...use $farmerId in every query (WHERE farmer_id = ?) so a farmer
       // only ever sees/edits their own rows.

   If a request gets a 401 back, this file redirects to login.php.

   API CONTRACT (adjust CONFIG.API_BASE / paths below to match your files)
   -------------------------------------------------------------------------
   GET    api/auth/me.php                        -> farmer profile object
   POST   api/auth/logout.php                    -> destroys session

   GET    api/dashboard/stats.php                -> dashboard stat counters

   GET    api/farms.php                          -> list of farms
   POST   api/farms.php                          -> create farm
   PUT    api/farms.php?id=ID                    -> update farm
   DELETE api/farms.php?id=ID                    -> delete farm

   GET    api/crops.php                          -> list of crops
   POST   api/crops.php / PUT / DELETE            -> same pattern

   GET    api/activities.php / POST / DELETE      -> farm activities log

   GET    api/calendar_events.php?month=&year=    -> calendar events
   POST   api/calendar_events.php / DELETE

   GET    api/weather.php?location=buea           -> current + forecast + alerts

   GET    api/marketplace.php?search=&category=&price=  -> all farmers' listings
   GET    api/products.php / POST / PUT / DELETE   -> the logged-in farmer's own listings

   POST   api/orders.php                          -> place an order from the cart

   GET    api/experts.php?search=&availability=
   POST   api/experts/contact.php                 -> {expert_id, message}
   POST   api/experts/consultation.php            -> {expert_id, date, topic, notes}

   POST   api/ai_chat.php                         -> {message} => {reply}

   GET    api/conversations.php                   -> list of conversations
   GET    api/conversations.php?id=ID             -> messages in one conversation
   POST   api/messages.php                        -> {conversation_id, text}

   GET    api/notifications.php
   POST   api/notifications_mark_read.php         -> {id} or {all:true}

   GET    api/reports.php?type=production&period=month
   GET    api/expenses.php / POST / DELETE

   PUT    api/profile.php                         -> update profile
   PUT    api/settings.php                         -> update settings

   Every list endpoint is expected to return JSON: either a bare array,
   or {data: [...]}. Both shapes are handled by unwrap() below.
   ========================================================================== */

(function () {
  'use strict';

  /* ------------------------------------------------------------------ *
   * CONFIG
   * ------------------------------------------------------------------ */
  const CONFIG = {
    API_BASE: 'api/'
  };

  /* ------------------------------------------------------------------ *
   * WEATHER CONFIG — real-time weather via OpenWeatherMap
   * ------------------------------------------------------------------ *
   * 1) Create a free account at https://openweathermap.org/api
   * 2) Copy your API key and paste it between the quotes below.
   * 3) That's it — city search and live weather both use this key.
   * ------------------------------------------------------------------ */
  const WEATHER_API_KEY = 'ce4c055278ee2a7d43ff9b225b73a665';           // <-- put your OpenWeatherMap API key here
  const WEATHER_API_BASE = 'https://api.openweathermap.org';

  // Fallback location used only before the farmer searches/picks a city.
  let selectedWeatherLocation = { name: 'Buea, Cameroon', lat: 4.1560, lon: 9.2632 };

  const ENDPOINT = {
    me: 'auth/me.php',
    logout: 'auth/logout.php',
    stats: 'dashboard/stats.php',
    farms: 'farms.php',
    crops: 'crops.php',
    activities: 'activities.php',
    events: 'calendar_events.php',
    weather: 'weather.php',
    marketplace: 'marketplace.php',
    products: 'products.php',
    orders: 'orders.php',
    experts: 'experts.php',
    expertContact: 'experts/contact.php',
    expertConsultation: 'experts/consultation.php',
    aiChat: 'ai_chat.php',
    conversations: 'conversations.php',
    messages: 'messages.php',
    notifications: 'notifications.php',
    notificationsMarkRead: 'notifications_mark_read.php',
    reports: 'reports.php',
    expenses: 'expenses.php',
    profile: 'profile.php',
    settings: 'settings.php',
  };

  /* ------------------------------------------------------------------ *
   * STATE
   * ------------------------------------------------------------------ */
  const State = {
    farmer: null,
    farms: [],
    crops: [],
    activities: [],
    events: [],
    marketplace: [],
    myProducts: [],
    experts: [],
    conversations: [],
    activeConversationId: null,
    notifications: [],
    expenses: [],
    cart: JSON.parse(localStorage.getItem('agritech_cart') || '[]'),
    calendarCursor: new Date(),
    cropView: 'grid',
  };

  /* ------------------------------------------------------------------ *
   * GENERIC HELPERS
   * ------------------------------------------------------------------ */
  function $(sel, root) { return (root || document).querySelector(sel); }
  function $all(sel, root) { return Array.from((root || document).querySelectorAll(sel)); }

  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  }

  function formatMoney(n) {
    const num = Number(n) || 0;
    return num.toLocaleString('en-US') + ' XAF';
  }

  function formatDate(d) {
    if (!d) return '—';
    const date = new Date(d);
    if (isNaN(date)) return d;
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
  }

  function unwrap(json) {
    if (Array.isArray(json)) return json;
    if (json && Array.isArray(json.data)) return json.data;
    return json;
  }

  function debounce(fn, wait) {
    let t;
    return function (...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), wait);
    };
  }

  /* ------------------------------------------------------------------ *
   * API LAYER
   * ------------------------------------------------------------------ */
  async function apiFetch(path, options = {}) {
    const url = CONFIG.API_BASE + path;
    const opts = Object.assign({ credentials: 'same-origin' }, options);
    opts.headers = Object.assign({}, options.headers);
    if (options.body && !(options.body instanceof FormData)) {
      opts.headers['Content-Type'] = 'application/json';
    }

    let res;
    try {
      res = await fetch(url, opts);
    } catch (err) {
      toast('Network error — check your connection.', 'error');
      throw err;
    }

    if (res.status === 401) {
      toast('Session expired. Redirecting to login…', 'error');
      setTimeout(() => { window.location.href = CONFIG.LOGIN_PAGE; }, 1200);
      throw new Error('Not authenticated');
    }

    let json = null;
    try { json = await res.json(); } catch (e) { /* empty body is fine */ }

    if (!res.ok) {
      const msg = (json && (json.error || json.message)) || `Request failed (${res.status})`;
      toast(msg, 'error');
      throw new Error(msg);
    }
    return json;
  }

  const api = {
    get: (path) => apiFetch(path, { method: 'GET' }),
    post: (path, body) => apiFetch(path, { method: 'POST', body: JSON.stringify(body || {}) }),
    put: (path, body) => apiFetch(path, { method: 'PUT', body: JSON.stringify(body || {}) }),
    del: (path) => apiFetch(path, { method: 'DELETE' }),
  };

  /* ------------------------------------------------------------------ *
   * TOASTS
   * ------------------------------------------------------------------ */
  function toast(message, type) {
    const container = $('#toastContainer');
    if (!container) { console.log('[toast]', type, message); return; }
    const el = document.createElement('div');
    el.className = 'toast toast-' + (type || 'info');
    const icon = type === 'error' ? 'fa-circle-exclamation'
      : type === 'success' ? 'fa-circle-check' : 'fa-circle-info';
    el.innerHTML = `<i class="fa-solid ${icon}"></i><span>${escapeHtml(message)}</span>`;
    container.appendChild(el);
    requestAnimationFrame(() => el.classList.add('show'));
    setTimeout(() => {
      el.classList.remove('show');
      setTimeout(() => el.remove(), 300);
    }, 3500);
  }

  /* ------------------------------------------------------------------ *
   * MODALS
   * ------------------------------------------------------------------ */
  function openModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('active');
    document.body.classList.add('modal-open');
  }

  function closeModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('active');
    if (!$all('.modal.active').length) document.body.classList.remove('modal-open');
  }

  function bindModalDismissals() {
    $all('[data-close-modal]').forEach((btn) => {
      btn.addEventListener('click', () => closeModal(btn.getAttribute('data-close-modal')));
    });
    $all('.modal').forEach((modal) => {
      modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal(modal.id);
      });
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') $all('.modal.active').forEach((m) => closeModal(m.id));
    });
  }

  let pendingDelete = null;
  function confirmDelete(message, onConfirm) {
    $('#confirmDeleteMessage').textContent = message;
    pendingDelete = onConfirm;
    openModal('confirmDeleteModal');
  }
  function bindConfirmDelete() {
    $('#confirmDeleteBtn').addEventListener('click', async () => {
      if (typeof pendingDelete === 'function') {
        try { await pendingDelete(); } catch (e) { /* toast already shown */ }
      }
      pendingDelete = null;
      closeModal('confirmDeleteModal');
    });
  }

  /* ------------------------------------------------------------------ *
   * NAVIGATION / SECTION SWITCHING
   * ------------------------------------------------------------------ */
  const SECTION_TITLES = {
    dashboard: 'Dashboard', myfarm: 'My Farm', crops: 'Crops', weather: 'Weather',
    activities: 'Farm Activities', calendar: 'Farm Calendar', marketplace: 'Marketplace',
    cart: 'Shopping Cart', myproducts: 'My Products', experts: 'Agricultural Experts',
    aiassistant: 'AI Farming Assistant', messages: 'Messages', notifications: 'Notifications',
    reports: 'Reports & Analytics', expenses: 'Expenses', profile: 'My Profile',
    settings: 'Settings', help: 'Help Center',
  };

  const SECTION_LOADERS = {
    dashboard: loadDashboard,
    myfarm: loadFarms,
    crops: () => Promise.all([loadCrops(), loadFarms(true)]),
    weather: loadWeather,
    activities: () => Promise.all([loadActivities(), loadFarms(true), loadCrops(true)]),
    calendar: loadCalendar,
    marketplace: loadMarketplace,
    cart: renderCart,
    myproducts: loadMyProducts,
    experts: loadExperts,
    messages: loadConversations,
    notifications: loadNotifications,
    reports: loadReports,
    expenses: () => Promise.all([loadExpenses(), loadFarms(true)]),
    profile: renderProfile,
  };

  const loadedSections = new Set();

  function goToSection(section, opts = {}) {
    if (!SECTION_TITLES[section]) section = 'dashboard';

    $all('.content-section').forEach((s) => s.classList.toggle('active', s.id === section));
    $all('.nav-item[data-section]').forEach((li) => li.classList.toggle('active', li.dataset.section === section));
    $all('.mobile-nav-item[data-section]').forEach((a) => a.classList.toggle('active', a.dataset.section === section));

    const titleEl = $('#pageTitle');
    if (titleEl) titleEl.textContent = SECTION_TITLES[section];

    if (location.hash.slice(1) !== section) history.replaceState(null, '', '#' + section);

    closeMobileSidebar();

    if (opts.force || !loadedSections.has(section)) {
      const loader = SECTION_LOADERS[section];
      if (loader) {
        loadedSections.add(section);
        Promise.resolve(loader()).catch(() => loadedSections.delete(section));
      }
    }
  }

  function bindNavigation() {
    $all('[data-section]').forEach((el) => {
      el.addEventListener('click', (e) => {
        if (el.tagName === 'A') e.preventDefault();
        goToSection(el.getAttribute('data-section'));
      });
    });
    window.addEventListener('hashchange', () => {
      goToSection(location.hash.slice(1) || 'dashboard');
    });
  }

  /* ------------------------------------------------------------------ *
   * SIDEBAR / TOPBAR CHROME
   * ------------------------------------------------------------------ */
  function closeMobileSidebar() {
    $('#appWrapper').classList.remove('sidebar-open');
    $('#sidebarOverlay').classList.remove('active');
  }

  function bindChrome() {
    $('#sidebarToggleBtn').addEventListener('click', () => {
      $('#appWrapper').classList.add('sidebar-open');
      $('#sidebarOverlay').classList.add('active');
    });
    $('#sidebarCloseBtn').addEventListener('click', closeMobileSidebar);
    $('#sidebarOverlay').addEventListener('click', closeMobileSidebar);

    $('#themeToggleBtn').addEventListener('click', () => {
      const body = document.getElementById('body');
      const next = body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      body.setAttribute('data-theme', next);
      localStorage.setItem('agrotech_theme', next);
      const icon = $('#themeToggleBtn i');
      icon.className = next === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
      const radio = document.getElementById(next === 'dark' ? 'darkModeRadio' : 'lightModeRadio');
      if (radio) radio.checked = true;
    });

    const savedTheme = localStorage.getItem('agrotech_theme');
    if (savedTheme) {
      document.getElementById('body').setAttribute('data-theme', savedTheme);
      const icon = $('#themeToggleBtn i');
      if (icon) icon.className = savedTheme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
      const radio = document.getElementById(savedTheme === 'dark' ? 'darkModeRadio' : 'lightModeRadio');
      if (radio) radio.checked = true;
    }

    $all('input[name="themeMode"]').forEach((r) => {
      r.addEventListener('change', () => {
        if (r.checked) {
          document.getElementById('body').setAttribute('data-theme', r.value);
          localStorage.setItem('agrotech_theme', r.value);
        }
      });
    });

    $('#messageBtn').addEventListener('click', () => goToSection('messages'));
    $('#notificationBtn').addEventListener('click', () => goToSection('notifications'));
    $('#topbarProfile').addEventListener('click', () => goToSection('profile'));

    $('#currentDate').textContent = new Date().toLocaleDateString('en-US', {
      weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
    });
    $('#footerYear').textContent = new Date().getFullYear();

    $('#logoutBtn').addEventListener('click', async (e) => {
      e.preventDefault();
      try { await api.post(ENDPOINT.logout); } catch (e2) { /* ignore, still redirect */ }
      window.location.href = CONFIG.LOGIN_PAGE;
    });

    const globalSearch = $('#globalSearchInput');
    if (globalSearch) {
      globalSearch.addEventListener('input', debounce(() => {
        const q = globalSearch.value.trim();
        const activeSection = $('.content-section.active');
        if (!activeSection) return;
        const localSearch = $('input[id$="SearchInput"]', activeSection);
        if (localSearch && localSearch !== globalSearch) {
          localSearch.value = q;
          localSearch.dispatchEvent(new Event('input'));
        }
      }, 250));
    }
  }

  /* ------------------------------------------------------------------ *
   * SELECT POPULATION HELPERS
   * ------------------------------------------------------------------ */
  function populateSelect(select, items, { valueKey = 'id', labelKey = 'name', placeholder } = {}) {
    if (!select) return;
    const current = select.value;
    select.innerHTML = '';
    if (placeholder) {
      const opt = document.createElement('option');
      opt.value = 'all';
      opt.textContent = placeholder;
      select.appendChild(opt);
    }
    items.forEach((item) => {
      const opt = document.createElement('option');
      opt.value = item[valueKey];
      opt.textContent = item[labelKey];
      select.appendChild(opt);
    });
    if ([...select.options].some((o) => o.value === current)) select.value = current;
  }

  function refreshFarmSelects() {
    const farmOpts = State.farms.map((f) => ({ id: f.id, name: f.name }));
    populateSelect($('#cropFarmFilter'), farmOpts, { placeholder: 'All Farms' });
    populateSelect($('#cropFarmInput'), farmOpts);
    populateSelect($('#editCropFarmInput'), farmOpts);
    populateSelect($('#activityFarmInput'), farmOpts, { placeholder: 'Any Farm' });
    populateSelect($('#expenseFarmInput'), farmOpts);
  }

  function refreshCropSelects() {
    const cropOpts = State.crops.map((c) => ({ id: c.id, name: c.name + (c.variety ? ' (' + c.variety + ')' : '') }));
    populateSelect($('#activityCropInput'), cropOpts, { placeholder: 'Any Crop' });
  }


  /* ==================================================================== *
   * DASHBOARD
   * ==================================================================== */
  async function loadDashboard() {
    try {
      const stats = await api.get(ENDPOINT.stats);
      const map = {
        statTotalFarmsValue: stats.farms,
        statTotalCropsValue: stats.crops,
        statActiveCropsValue: stats.active_crops,
        statHarvestReadyValue: stats.harvest_ready,
        statMyProductsValue: stats.my_products,
        statPendingOrdersValue: stats.pending_orders,
      };
      Object.entries(map).forEach(([id, val]) => {
        const el = document.getElementById(id);
        if (el && val !== undefined) el.textContent = val;
      });
    } catch (e) { /* toast already shown by apiFetch */}

    try {
      const weather = await fetchLiveWeather(selectedWeatherLocation);
      if (weather) {
        $('#dashboardWeatherBody').innerHTML =
          `<p>${escapeHtml(weather.location || '')} — ${escapeHtml(String(weather.temp ?? '')) }°C, ${escapeHtml(weather.condition || '')}.</p>`;
      }
    } catch (e) { /* ignore — live weather is best-effort on the dashboard */ }

    try {
      const activities = unwrap(await api.get(ENDPOINT.activities));
      const recent = (activities || []).slice(0, 5);
      $('#dashboardActivityBody').innerHTML = recent.length
        ? recent.map((a) => `<div class="mini-row"><i class="fa-solid fa-circle-check"></i> <span>${escapeHtml(a.type)}</span> <small>${formatDate(a.date)}</small></div>`).join('')
        : '<p class="muted">No recent activity.</p>';
    } catch (e) { /* ignore */ }

    try {
      const notifs = unwrap(await api.get(ENDPOINT.notifications));
      const recent = (notifs || []).slice(0, 5);
      $('#dashboardNotifBody').innerHTML = recent.length
        ? recent.map((n) => `<div class="mini-row"><i class="fa-solid fa-bell"></i> <span>${escapeHtml(n.message || n.title)}</span></div>`).join('')
        : '<p class="muted">You are all caught up.</p>';
    } catch (e) { /* ignore */ }
  }

  function bindQuickActions() {
    $('#qaAddFarm').addEventListener('click', () => openModal('addFarmModal'));
    $('#qaAddCrop').addEventListener('click', () => { goToSection('crops'); openModal('addCropModal'); });
    $('#qaRecordActivity').addEventListener('click', () => { goToSection('activities'); openModal('addActivityModal'); });
    $('#qaAddProduct').addEventListener('click', () => { goToSection('myproducts'); openModal('addProductModal'); });
    $('#qaFindExpert').addEventListener('click', () => goToSection('experts'));
    $('#qaViewWeather').addEventListener('click', () => goToSection('weather'));
    $('#qaCreateReport').addEventListener('click', () => { goToSection('reports'); openModal('generateReportModal'); });
  }

  /* ==================================================================== *
   * MY FARM
   * ==================================================================== */
  async function loadFarms(silentIfLoaded) {
    if (silentIfLoaded && State.farms.length) { refreshFarmSelects(); return State.farms; }
    const farms = unwrap(await api.get(ENDPOINT.farms));
    State.farms = farms || [];
    refreshFarmSelects();
    renderFarms();
    return State.farms;
  }

  function renderFarms() {
    const search = ($('#farmSearchInput').value || '').toLowerCase();
    const status = $('#farmStatusFilter').value;
    const list = State.farms.filter((f) => {
      const matchesSearch = !search || f.name.toLowerCase().includes(search) || (f.location || '').toLowerCase().includes(search);
      const matchesStatus = status === 'all' || f.status === status;
      return matchesSearch && matchesStatus;
    });

    const container = $('#farmCardsContainer');
    $('#farmEmptyState').hidden = list.length !== 0;
    container.innerHTML = list.map((f) => `
      <div class="farm-card" data-id="${f.id}">
        <div class="farm-card-header">
          <h4>${escapeHtml(f.name)}</h4>
          <span class="status-pill ${escapeHtml(f.status || '')}">${escapeHtml(f.status || '')}</span>
        </div>
        <p><i class="fa-solid fa-location-dot"></i> ${escapeHtml(f.location || '—')}</p>
        <p><i class="fa-solid fa-ruler-combined"></i> ${escapeHtml(String(f.size ?? '—'))} ha &middot; ${escapeHtml(f.soil_type || '—')}</p>
        <p><i class="fa-solid fa-wheat-awn"></i> ${escapeHtml(f.main_crop || '—')}</p>
        <div class="farm-card-actions">
          <button class="btn-icon" data-action="view"><i class="fa-solid fa-eye"></i></button>
          <button class="btn-icon" data-action="edit"><i class="fa-solid fa-pen"></i></button>
          <button class="btn-icon danger" data-action="delete"><i class="fa-solid fa-trash"></i></button>
        </div>
      </div>`).join('');
  }

  function bindFarms() {
    $('#addFarmBtn').addEventListener('click', () => openModal('addFarmModal'));
    $('#emptyStateAddFarmBtn').addEventListener('click', () => openModal('addFarmModal'));
    $('#farmSearchInput').addEventListener('input', debounce(renderFarms, 200));
    $('#farmStatusFilter').addEventListener('change', renderFarms);

    $('#addFarmForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const payload = {
        name: $('#farmNameInput').value.trim(),
        location: $('#farmLocationInput').value.trim(),
        size: Number($('#farmSizeInput').value) || 0,
        soil_type: $('#farmSoilTypeInput').value,
        irrigation: $('#farmIrrigationInput').value,
        main_crop: $('#farmMainCropInput').value.trim(),
        status: $('#farmStatusInput').value,
      };
      await api.post(ENDPOINT.farms, payload);
      e.target.reset();
      closeModal('addFarmModal');
      toast('Farm added.', 'success');
      await loadFarms();
      loadDashboard();
    });

    $('#editFarmForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const id = $('#editFarmId').value;
      const payload = {
        name: $('#editFarmNameInput').value.trim(),
        location: $('#editFarmLocationInput').value.trim(),
        size: Number($('#editFarmSizeInput').value) || 0,
        soil_type: $('#editFarmSoilTypeInput').value,
        irrigation: $('#editFarmIrrigationInput').value,
        main_crop: $('#editFarmMainCropInput').value.trim(),
        status: $('#editFarmStatusInput').value,
      };
      await api.put(ENDPOINT.farms + '?id=' + encodeURIComponent(id), payload);
      closeModal('editFarmModal');
      toast('Farm updated.', 'success');
      await loadFarms();
    });

    $('#farmCardsContainer').addEventListener('click', (e) => {
      const btn = e.target.closest('button[data-action]');
      if (!btn) return;
      const card = e.target.closest('.farm-card');
      const id = card.dataset.id;
      const farm = State.farms.find((f) => String(f.id) === String(id));
      if (!farm) return;

      if (btn.dataset.action === 'view') {
        $('#viewFarmModalBody').innerHTML = `
          <div class="detail-grid">
            <p><strong>Name:</strong> ${escapeHtml(farm.name)}</p>
            <p><strong>Location:</strong> ${escapeHtml(farm.location || '—')}</p>
            <p><strong>Size:</strong> ${escapeHtml(String(farm.size ?? '—'))} ha</p>
            <p><strong>Soil Type:</strong> ${escapeHtml(farm.soil_type || '—')}</p>
            <p><strong>Irrigation:</strong> ${escapeHtml(farm.irrigation || '—')}</p>
            <p><strong>Main Crop:</strong> ${escapeHtml(farm.main_crop || '—')}</p>
            <p><strong>Status:</strong> ${escapeHtml(farm.status || '—')}</p>
          </div>`;
        openModal('viewFarmModal');
      } else if (btn.dataset.action === 'edit') {
        $('#editFarmId').value = farm.id;
        $('#editFarmNameInput').value = farm.name || '';
        $('#editFarmLocationInput').value = farm.location || '';
        $('#editFarmSizeInput').value = farm.size || '';
        $('#editFarmSoilTypeInput').value = farm.soil_type || 'Loamy';
        $('#editFarmIrrigationInput').value = farm.irrigation || 'Drip Irrigation';
        $('#editFarmMainCropInput').value = farm.main_crop || '';
        $('#editFarmStatusInput').value = farm.status || 'active';
        openModal('editFarmModal');
      } else if (btn.dataset.action === 'delete') {
        confirmDelete(`Delete farm "${farm.name}"? This cannot be undone.`, async () => {
          await api.del(ENDPOINT.farms + '?id=' + encodeURIComponent(farm.id));
          toast('Farm deleted.', 'success');
          await loadFarms();
          loadDashboard();
        });
      }
    });
  }

  /* ==================================================================== *
   * CROPS
   * ==================================================================== */
  async function loadCrops(silentIfLoaded) {
    if (silentIfLoaded && State.crops.length) { refreshCropSelects(); return State.crops; }
    const crops = unwrap(await api.get(ENDPOINT.crops));
    State.crops = crops || [];
    refreshCropSelects();
    renderCrops();
    return State.crops;
  }

  function farmName(farmId) {
    const f = State.farms.find((x) => String(x.id) === String(farmId));
    return f ? f.name : '—';
  }

  function filteredCrops() {
    const search = ($('#cropSearchInput').value || '').toLowerCase();
    const farm = $('#cropFarmFilter').value;
    const stage = $('#cropStageFilter').value;
    return State.crops.filter((c) => {
      const matchesSearch = !search || c.name.toLowerCase().includes(search) || (c.variety || '').toLowerCase().includes(search);
      const matchesFarm = farm === 'all' || String(c.farm_id) === String(farm);
      const matchesStage = stage === 'all' || c.stage === stage;
      return matchesSearch && matchesFarm && matchesStage;
    });
  }

  function renderCrops() {
    const list = filteredCrops();
    $('#cropEmptyState').hidden = list.length !== 0;

    if (State.cropView === 'grid') {
      $('#cropCardsContainer').hidden = false;
      $('#cropTableWrapper').hidden = true;
      $('#cropCardsContainer').innerHTML = list.map((c) => `
        <div class="crop-card" data-id="${c.id}">
          <div class="crop-card-header">
            <h4>${escapeHtml(c.name)}${c.variety ? ' — ' + escapeHtml(c.variety) : ''}</h4>
            <span class="health-pill ${escapeHtml(c.health || '')}">${escapeHtml(c.health || '')}</span>
          </div>
          <p><i class="fa-solid fa-tractor"></i> ${escapeHtml(farmName(c.farm_id))}</p>
          <p><i class="fa-solid fa-seedling"></i> Stage: ${escapeHtml(c.stage || '—')}</p>
          <p><i class="fa-solid fa-calendar"></i> Planted ${formatDate(c.planting_date)}</p>
          <div class="crop-card-actions">
            <button class="btn-icon" data-action="view"><i class="fa-solid fa-eye"></i></button>
            <button class="btn-icon" data-action="edit"><i class="fa-solid fa-pen"></i></button>
            <button class="btn-icon danger" data-action="delete"><i class="fa-solid fa-trash"></i></button>
          </div>
        </div>`).join('');
    } else {
      $('#cropCardsContainer').hidden = true;
      $('#cropTableWrapper').hidden = false;
      $('#cropTableBody').innerHTML = list.map((c) => `
        <tr data-id="${c.id}">
          <td>${escapeHtml(c.name)}</td>
          <td>${escapeHtml(c.variety || '—')}</td>
          <td>${escapeHtml(farmName(c.farm_id))}</td>
          <td>${formatDate(c.planting_date)}</td>
          <td>${formatDate(c.harvest_date)}</td>
          <td>${escapeHtml(c.stage || '—')}</td>
          <td>${escapeHtml(c.quantity || '—')}</td>
          <td>${escapeHtml(c.health || '—')}</td>
          <td>${escapeHtml(c.progress ? c.progress + '%' : '—')}</td>
          <td>
            <button class="btn-icon" data-action="view"><i class="fa-solid fa-eye"></i></button>
            <button class="btn-icon" data-action="edit"><i class="fa-solid fa-pen"></i></button>
            <button class="btn-icon danger" data-action="delete"><i class="fa-solid fa-trash"></i></button>
          </td>
        </tr>`).join('');
    }
  }

  function bindCrops() {
    $('#addCropBtn').addEventListener('click', () => openModal('addCropModal'));
    $('#emptyStateAddCropBtn').addEventListener('click', () => openModal('addCropModal'));
    $('#cropSearchInput').addEventListener('input', debounce(renderCrops, 200));
    $('#cropFarmFilter').addEventListener('change', renderCrops);
    $('#cropStageFilter').addEventListener('change', renderCrops);
    $('#cropGridViewBtn').addEventListener('click', () => {
      State.cropView = 'grid';
      $('#cropGridViewBtn').classList.add('active');
      $('#cropTableViewBtn').classList.remove('active');
      renderCrops();
    });
    $('#cropTableViewBtn').addEventListener('click', () => {
      State.cropView = 'table';
      $('#cropTableViewBtn').classList.add('active');
      $('#cropGridViewBtn').classList.remove('active');
      renderCrops();
    });

    $('#addCropForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const payload = {
        name: $('#cropNameInput').value.trim(),
        variety: $('#cropVarietyInput').value.trim(),
        farm_id: $('#cropFarmInput').value,
        planting_date: $('#cropPlantingDateInput').value,
        harvest_date: $('#cropHarvestDateInput').value,
        stage: $('#cropStageInput').value,
        quantity: $('#cropQuantityInput').value.trim(),
        health: $('#cropHealthInput').value,
      };
      await api.post(ENDPOINT.crops, payload);
      e.target.reset();
      closeModal('addCropModal');
      toast('Crop added.', 'success');
      await loadCrops();
      loadDashboard();
    });

    $('#editCropForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const id = $('#editCropId').value;
      const payload = {
        name: $('#editCropNameInput').value.trim(),
        variety: $('#editCropVarietyInput').value.trim(),
        farm_id: $('#editCropFarmInput').value,
        planting_date: $('#editCropPlantingDateInput').value,
        harvest_date: $('#editCropHarvestDateInput').value,
        stage: $('#editCropStageInput').value,
        quantity: $('#editCropQuantityInput').value.trim(),
        health: $('#editCropHealthInput').value,
      };
      await api.put(ENDPOINT.crops + '?id=' + encodeURIComponent(id), payload);
      closeModal('editCropModal');
      toast('Crop updated.', 'success');
      await loadCrops();
    });

    function handleCropAction(e) {
      const btn = e.target.closest('button[data-action]');
      if (!btn) return;
      const row = e.target.closest('[data-id]');
      const id = row.dataset.id;
      const crop = State.crops.find((c) => String(c.id) === String(id));
      if (!crop) return;

      if (btn.dataset.action === 'view') {
        $('#viewCropModalBody').innerHTML = `
          <div class="detail-grid">
            <p><strong>Crop:</strong> ${escapeHtml(crop.name)}</p>
            <p><strong>Variety:</strong> ${escapeHtml(crop.variety || '—')}</p>
            <p><strong>Farm:</strong> ${escapeHtml(farmName(crop.farm_id))}</p>
            <p><strong>Planted:</strong> ${formatDate(crop.planting_date)}</p>
            <p><strong>Expected Harvest:</strong> ${formatDate(crop.harvest_date)}</p>
            <p><strong>Stage:</strong> ${escapeHtml(crop.stage || '—')}</p>
            <p><strong>Quantity:</strong> ${escapeHtml(crop.quantity || '—')}</p>
            <p><strong>Health:</strong> ${escapeHtml(crop.health || '—')}</p>
          </div>`;
        openModal('viewCropModal');
      } else if (btn.dataset.action === 'edit') {
        $('#editCropId').value = crop.id;
        $('#editCropNameInput').value = crop.name || '';
        $('#editCropVarietyInput').value = crop.variety || '';
        $('#editCropFarmInput').value = crop.farm_id || '';
        $('#editCropPlantingDateInput').value = (crop.planting_date || '').slice(0, 10);
        $('#editCropHarvestDateInput').value = (crop.harvest_date || '').slice(0, 10);
        $('#editCropStageInput').value = crop.stage || 'seedling';
        $('#editCropQuantityInput').value = crop.quantity || '';
        $('#editCropHealthInput').value = crop.health || 'good';
        openModal('editCropModal');
      } else if (btn.dataset.action === 'delete') {
        confirmDelete(`Delete crop "${crop.name}"?`, async () => {
          await api.del(ENDPOINT.crops + '?id=' + encodeURIComponent(crop.id));
          toast('Crop deleted.', 'success');
          await loadCrops();
          loadDashboard();
        });
      }
    }
    $('#cropCardsContainer').addEventListener('click', handleCropAction);
    $('#cropTableBody').addEventListener('click', handleCropAction);
  }

  /* ==================================================================== *
   * WEATHER
   * ==================================================================== */
  let weatherSearchTimer = null;

  // Swaps the old static <select id="weatherLocationSelect"> for a free-text
  // search box with live city suggestions, so the farmer never types
  // coordinates or picks from a fixed list.
  function ensureWeatherSearchUI() {
    const host = $('#weatherLocationSelect');
    if (!host || host.dataset.upgraded === 'true') return;
    host.dataset.upgraded = 'true';

    const wrapper = document.createElement('div');
    wrapper.className = 'weather-city-search';
    wrapper.style.position = 'relative';
    wrapper.style.display = 'inline-block';
    wrapper.style.minWidth = '220px';

    const input = document.createElement('input');
    input.type = 'text';
    input.id = 'weatherCityInput';
    input.placeholder = 'Search for a city…';
    input.autocomplete = 'off';
    input.value = selectedWeatherLocation.name;
    input.style.cssText = 'width:100%;padding:8px 10px;border:1px solid #ccc;border-radius:6px;';

    const suggestions = document.createElement('ul');
    suggestions.id = 'weatherCitySuggestions';
    suggestions.style.cssText = 'position:absolute;top:100%;left:0;right:0;z-index:20;background:#fff;'
      + 'border:1px solid #ddd;border-radius:6px;max-height:220px;overflow-y:auto;display:none;'
      + 'list-style:none;margin:4px 0 0;padding:0;box-shadow:0 4px 12px rgba(0,0,0,.12);';

    wrapper.appendChild(input);
    wrapper.appendChild(suggestions);
    host.replaceWith(wrapper);

    input.addEventListener('input', () => {
      clearTimeout(weatherSearchTimer);
      const q = input.value.trim();
      if (q.length < 2) { suggestions.style.display = 'none'; suggestions.innerHTML = ''; return; }
      weatherSearchTimer = setTimeout(() => searchCities(q, suggestions, input), 300);
    });

    document.addEventListener('click', (e) => {
      if (!wrapper.contains(e.target)) suggestions.style.display = 'none';
    });
  }

  // Live city lookup — OpenWeatherMap's geocoding endpoint. This is what
  // powers "search" instead of manual entry.
  async function searchCities(query, suggestions, input) {
    if (WEATHER_API_KEY === ' ') {
      suggestions.innerHTML = '<li style="padding:8px 12px;color:#c0392b;">Add your API key in farm2.js (WEATHER_API_KEY) first.</li>';
      suggestions.style.display = 'block';
      return;
    }

    ce4c055278ee2a7d43ff9b225b73a665
    try {
      const url = `${WEATHER_API_BASE}/geo/1.0/direct?q=${encodeURIComponent(query)}&limit=5&appid=${WEATHER_API_KEY}`;
      const res = await fetch(url);
      const results = await res.json();
      if (!Array.isArray(results) || !results.length) {
        suggestions.innerHTML = '<li style="padding:8px 12px;color:#888;">No cities found</li>';
        suggestions.style.display = 'block';
        return;
      }
      suggestions.innerHTML = results.map((c, i) => {
        const label = [c.name, c.state, c.country].filter(Boolean).join(', ');
        return `<li data-i="${i}" style="padding:8px 12px;cursor:pointer;">${escapeHtml(label)}</li>`;
      }).join('');
      suggestions.style.display = 'block';

      $all('li', suggestions).forEach((li) => {
        li.addEventListener('click', () => {
          const c = results[Number(li.dataset.i)];
          const label = [c.name, c.state, c.country].filter(Boolean).join(', ');
          input.value = label;
          selectedWeatherLocation = { name: label, lat: c.lat, lon: c.lon };
          suggestions.style.display = 'none';
          loadWeather();
        });
      });
    } catch (err) {
      toast('City search failed — check your connection or API key.', 'error');
    }
  }

  function mapWeatherIcon(owmIcon) {
    if (!owmIcon) return 'fa-cloud-sun';
    if (owmIcon.startsWith('01')) return 'fa-sun';
    if (owmIcon.startsWith('02') || owmIcon.startsWith('03')) return 'fa-cloud-sun';
    if (owmIcon.startsWith('04')) return 'fa-cloud';
    if (owmIcon.startsWith('09') || owmIcon.startsWith('10')) return 'fa-cloud-rain';
    if (owmIcon.startsWith('11')) return 'fa-bolt';
    if (owmIcon.startsWith('13')) return 'fa-snowflake';
    if (owmIcon.startsWith('50')) return 'fa-smog';
    return 'fa-cloud-sun';
  }

  // Fetches real-time current conditions + 5-day forecast for a
  // {name, lat, lon} location and shapes it into the view-model this file
  // already knows how to render.
  async function fetchLiveWeather(location) {
    if (WEATHER_API_KEY === 'INPUT API KEY') {
      throw new Error('Missing WEATHER_API_KEY');
    }
    const { lat, lon, name } = location;
    const [currentRes, forecastRes] = await Promise.all([
      fetch(`${WEATHER_API_BASE}/data/2.5/weather?lat=${lat}&lon=${lon}&units=metric&appid=${WEATHER_API_KEY}`),
      fetch(`${WEATHER_API_BASE}/data/2.5/forecast?lat=${lat}&lon=${lon}&units=metric&appid=${WEATHER_API_KEY}`),
    ]);
    const current = await currentRes.json();
    const forecast = await forecastRes.json();
    if (!currentRes.ok) throw new Error(current.message || 'Weather request failed');

    const daily = {};
    (forecast.list || []).forEach((entry) => {
      const day = entry.dt_txt.split(' ')[0];
      if (!daily[day]) daily[day] = { temps: [], icon: entry.weather && entry.weather[0] && entry.weather[0].icon };
      daily[day].temps.push(entry.main.temp);
    });
    const forecastDays = Object.keys(daily).slice(0, 5).map((day) => ({
      day: new Date(day).toLocaleDateString('en-US', { weekday: 'short' }),
      high: Math.round(Math.max(...daily[day].temps)),
      low: Math.round(Math.min(...daily[day].temps)),
      icon: mapWeatherIcon(daily[day].icon),
    }));

    return {
      location: name,
      temp: current.main ? Math.round(current.main.temp) : undefined,
      condition: current.weather && current.weather[0] ? current.weather[0].description : '',
      humidity: current.main ? current.main.humidity : undefined,
      wind: current.wind && current.wind.speed !== undefined ? Math.round(current.wind.speed) + ' m/s' : '—',
      rain_chance: (forecast.list && forecast.list[0] && forecast.list[0].pop !== undefined)
        ? Math.round(forecast.list[0].pop * 100) : undefined,
      cloud: current.clouds ? current.clouds.all : undefined,
      uv: '—', // UV index needs OpenWeatherMap's One Call API (separate plan)
      sunrise: current.sys && current.sys.sunrise
        ? new Date(current.sys.sunrise * 1000).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '—',
      sunset: current.sys && current.sys.sunset
        ? new Date(current.sys.sunset * 1000).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '—',
      forecast: forecastDays,
      alerts: [],
    };
  }

  async function loadWeather() {
    ensureWeatherSearchUI();

    if (WEATHER_API_KEY === 'INPUT API KEY') {
      toast('Add your OpenWeatherMap API key in farm2.js (WEATHER_API_KEY) to load live weather.', 'error');
      return;
    }

    let w;
    try {
      w = await fetchLiveWeather(selectedWeatherLocation);
    } catch (err) {
      toast('Could not load live weather data.', 'error');
      return;
    }
    if (!w) return;

    $('#weatherLocationText').textContent = w.location || '';
    $('#weatherTempValue').textContent = (w.temp !== undefined ? w.temp + '°C' : '—');
    $('#weatherConditionText').textContent = w.condition || '';
    $('#weatherHumidity').textContent = w.humidity !== undefined ? w.humidity + '%' : '—';
    $('#weatherWind').textContent = w.wind || '—';
    $('#weatherRainChance').textContent = w.rain_chance !== undefined ? w.rain_chance + '%' : '—';
    $('#weatherCloud').textContent = w.cloud !== undefined ? w.cloud + '%' : '—';
    $('#weatherUV').textContent = w.uv || '—';
    $('#weatherSunrise').textContent = w.sunrise || '—';
    $('#weatherSunset').textContent = w.sunset || '—';

    const forecast = w.forecast || [];
    $('#forecastRow').innerHTML = forecast.map((d) => `
      <div class="forecast-day">
        <p>${escapeHtml(d.day || '')}</p>
        <i class="fa-solid ${escapeHtml(d.icon || 'fa-cloud-sun')}"></i>
        <p>${escapeHtml(String(d.high ?? ''))}° / ${escapeHtml(String(d.low ?? ''))}°</p>
      </div>`).join('');

    if (Array.isArray(w.alerts)) {
      $all('.alert-card', $('#weatherAlertsGrid')).forEach((card) => {
        card.style.display = w.alerts.includes(card.dataset.alert) ? '' : 'none';
      });
    }
  }

  function bindWeather() {
    ensureWeatherSearchUI();
  }

  /* ==================================================================== *
   * ACTIVITIES
   * ==================================================================== */
  async function loadActivities() {
    const activities = unwrap(await api.get(ENDPOINT.activities));
    State.activities = activities || [];
    renderActivities();
    return State.activities;
  }

  function cropName(cropId) {
    const c = State.crops.find((x) => String(x.id) === String(cropId));
    return c ? c.name : '—';
  }

  function renderActivities() {
    const search = ($('#activitySearchInput').value || '').toLowerCase();
    const type = $('#activityTypeFilter').value;
    const list = State.activities.filter((a) => {
      const matchesSearch = !search || (a.type || '').toLowerCase().includes(search);
      const matchesType = type === 'all' || a.type === type;
      return matchesSearch && matchesType;
    });
    $('#activityEmptyState').hidden = list.length !== 0;
    $('#activitiesTableBody').innerHTML = list.map((a) => `
      <tr data-id="${a.id}">
        <td>${escapeHtml(a.type)}</td>
        <td>${escapeHtml(cropName(a.crop_id))}</td>
        <td>${escapeHtml(farmName(a.farm_id))}</td>
        <td>${formatDate(a.date)}</td>
        <td>${a.cost ? formatMoney(a.cost) : '—'}</td>
        <td><span class="status-pill ${escapeHtml(a.status || '')}">${escapeHtml(a.status || '')}</span></td>
        <td><button class="btn-icon danger" data-action="delete"><i class="fa-solid fa-trash"></i></button></td>
      </tr>`).join('');
  }

  function bindActivities() {
    $('#addActivityBtn').addEventListener('click', () => openModal('addActivityModal'));
    $('#activitySearchInput').addEventListener('input', debounce(renderActivities, 200));
    $('#activityTypeFilter').addEventListener('change', renderActivities);

    $('#addActivityForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const payload = {
        type: $('#activityTypeInput').value,
        crop_id: $('#activityCropInput').value || null,
        farm_id: $('#activityFarmInput').value || null,
        date: $('#activityDateInput').value,
        cost: Number($('#activityCostInput').value) || 0,
        status: $('#activityStatusInput').value,
      };
      await api.post(ENDPOINT.activities, payload);
      e.target.reset();
      closeModal('addActivityModal');
      toast('Activity recorded.', 'success');
      await loadActivities();
      loadDashboard();
    });

    $('#activitiesTableBody').addEventListener('click', (e) => {
      const btn = e.target.closest('button[data-action="delete"]');
      if (!btn) return;
      const id = e.target.closest('tr').dataset.id;
      const activity = State.activities.find((a) => String(a.id) === String(id));
      confirmDelete('Delete this activity record?', async () => {
        await api.del(ENDPOINT.activities + '?id=' + encodeURIComponent(id));
        toast('Activity deleted.', 'success');
        await loadActivities();
      });
    });
  }

  /* ==================================================================== *
   * CALENDAR
   * ==================================================================== */
  async function loadCalendar() {
    const cursor = State.calendarCursor;
    const month = cursor.getMonth() + 1;
    const year = cursor.getFullYear();
    const events = unwrap(await api.get(ENDPOINT.events + `?month=${month}&year=${year}`));
    State.events = events || [];
    renderCalendar();
  }

  function renderCalendar() {
    const cursor = State.calendarCursor;
    $('#currentMonthLabel').textContent = cursor.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

    const year = cursor.getFullYear();
    const month = cursor.getMonth();
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const eventsByDay = {};
    State.events.forEach((ev) => {
      const d = new Date(ev.date).getDate();
      (eventsByDay[d] = eventsByDay[d] || []).push(ev);
    });

    let cells = '';
    for (let i = 0; i < firstDay; i++) cells += '<div class="calendar-cell empty"></div>';
    for (let day = 1; day <= daysInMonth; day++) {
      const dayEvents = eventsByDay[day] || [];
      const isToday = new Date().toDateString() === new Date(year, month, day).toDateString();
      cells += `<div class="calendar-cell${isToday ? ' today' : ''}${dayEvents.length ? ' has-event' : ''}">
        <span class="cell-day">${day}</span>
        ${dayEvents.slice(0, 2).map((ev) => `<span class="cell-event" title="${escapeHtml(ev.title)}">${escapeHtml(ev.title)}</span>`).join('')}
      </div>`;
    }
    $('#calendarGrid').innerHTML = cells;

    const upcoming = [...State.events]
      .filter((ev) => new Date(ev.date) >= new Date(new Date().toDateString()))
      .sort((a, b) => new Date(a.date) - new Date(b.date))
      .slice(0, 6);
    $('#upcomingEventsList').innerHTML = upcoming.length
      ? upcoming.map((ev) => `
        <div class="upcoming-event-item">
          <div class="upcoming-event-date">${formatDate(ev.date)}</div>
          <div><strong>${escapeHtml(ev.title)}</strong><br><small>${escapeHtml(ev.type || '')}</small></div>
        </div>`).join('')
      : '<p class="muted">No upcoming events.</p>';
  }

  function bindCalendar() {
    $('#addEventBtn').addEventListener('click', () => openModal('addEventModal'));
    $('#prevMonthBtn').addEventListener('click', () => {
      State.calendarCursor.setMonth(State.calendarCursor.getMonth() - 1);
      loadCalendar();
    });
    $('#nextMonthBtn').addEventListener('click', () => {
      State.calendarCursor.setMonth(State.calendarCursor.getMonth() + 1);
      loadCalendar();
    });
    $('#addEventForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const payload = {
        title: $('#eventTitleInput').value.trim(),
        type: $('#eventTypeInput').value,
        date: $('#eventDateInput').value,
        notes: $('#eventNotesInput').value.trim(),
      };
      await api.post(ENDPOINT.events, payload);
      e.target.reset();
      closeModal('addEventModal');
      toast('Event added.', 'success');
      await loadCalendar();
    });
  }

  /* ==================================================================== *
   * MARKETPLACE + CART
   * ==================================================================== */
  async function loadMarketplace() {
    const search = $('#marketplaceSearchInput').value.trim();
    const category = $('#marketplaceCategoryFilter').value;
    const price = $('#marketplacePriceFilter').value;
    const qs = new URLSearchParams({ search, category, price }).toString();
    const products = unwrap(await api.get(ENDPOINT.marketplace + '?' + qs));
    State.marketplace = products || [];
    renderMarketplace();
    updateCartBadge();
  }

  function renderMarketplace() {
    $('#marketplaceGrid').innerHTML = State.marketplace.map((p) => `
      <div class="product-card" data-id="${p.id}">
        <img src="${escapeHtml(p.image_url || 'https://via.placeholder.com/300x200?text=Product')}" alt="${escapeHtml(p.name)}">
        <div class="product-card-body">
          <h4>${escapeHtml(p.name)}</h4>
          <p class="product-price">${formatMoney(p.price)}</p>
          <p><i class="fa-solid fa-location-dot"></i> ${escapeHtml(p.location || '—')}</p>
          <p><i class="fa-solid fa-box"></i> ${escapeHtml(p.quantity || '—')}</p>
          <button class="btn-primary full-width" data-action="add-to-cart"><i class="fa-solid fa-cart-plus"></i> Add to Cart</button>
        </div>
      </div>`).join('');
  }

  function saveCart() {
    localStorage.setItem('agrotech_cart', JSON.stringify(State.cart));
    updateCartBadge();
  }

  function updateCartBadge() {
    const count = State.cart.reduce((sum, item) => sum + item.qty, 0);
    $('#cartCountBadge').textContent = count;
  }

  function renderCart() {
    const container = $('#cartItemsList');
    $('#cartEmptyState').hidden = State.cart.length !== 0;
    container.innerHTML = State.cart.map((item) => `
      <div class="cart-item" data-id="${item.id}">
        <img src="${escapeHtml(item.image_url || 'https://via.placeholder.com/80')}" alt="${escapeHtml(item.name)}">
        <div class="cart-item-info">
          <h4>${escapeHtml(item.name)}</h4>
          <p>${formatMoney(item.price)}</p>
        </div>
        <div class="cart-item-qty">
          <button data-action="dec">-</button>
          <span>${item.qty}</span>
          <button data-action="inc">+</button>
        </div>
        <button class="btn-icon danger" data-action="remove"><i class="fa-solid fa-trash"></i></button>
      </div>`).join('');

    const subtotal = State.cart.reduce((sum, i) => sum + i.qty * i.price, 0);
    const delivery = State.cart.length ? 2000 : 0;
    $('#cartSubtotal').textContent = formatMoney(subtotal);
    $('#cartDelivery').textContent = formatMoney(delivery);
    $('#cartTotal').textContent = formatMoney(subtotal + delivery);
  }

  function bindMarketplace() {
    $('#marketplaceSearchInput').addEventListener('input', debounce(loadMarketplace, 300));
    $('#marketplaceCategoryFilter').addEventListener('change', loadMarketplace);
    $('#marketplacePriceFilter').addEventListener('change', loadMarketplace);

    $('#marketplaceGrid').addEventListener('click', (e) => {
      const btn = e.target.closest('button[data-action="add-to-cart"]');
      if (!btn) return;
      const id = e.target.closest('.product-card').dataset.id;
      const product = State.marketplace.find((p) => String(p.id) === String(id));
      if (!product) return;
      const existing = State.cart.find((i) => String(i.id) === String(id));
      if (existing) existing.qty += 1;
      else State.cart.push({ id: product.id, name: product.name, price: product.price, image_url: product.image_url, qty: 1 });
      saveCart();
      toast(`${product.name} added to cart.`, 'success');
    });

    $('#viewCartBtn').addEventListener('click', () => goToSection('cart'));

    $('#cartItemsList').addEventListener('click', (e) => {
      const btn = e.target.closest('button[data-action]');
      if (!btn) return;
      const id = e.target.closest('.cart-item').dataset.id;
      const item = State.cart.find((i) => String(i.id) === String(id));
      if (!item) return;
      if (btn.dataset.action === 'inc') item.qty += 1;
      if (btn.dataset.action === 'dec') item.qty = Math.max(1, item.qty - 1);
      if (btn.dataset.action === 'remove') State.cart = State.cart.filter((i) => String(i.id) !== String(id));
      saveCart();
      renderCart();
    });

    $('#clearCartBtn').addEventListener('click', () => {
      State.cart = [];
      saveCart();
      renderCart();
    });

    $('#placeOrderBtn').addEventListener('click', async () => {
      if (!State.cart.length) { toast('Your cart is empty.', 'error'); return; }
      const payload = {
        items: State.cart.map((i) => ({ product_id: i.id, quantity: i.qty })),
        delivery_fee: 2000,
      };
      await api.post(ENDPOINT.orders, payload);
      State.cart = [];
      saveCart();
      renderCart();
      toast('Order placed successfully!', 'success');
      loadDashboard();
    });
  }

  /* ==================================================================== *
   * MY PRODUCTS
   * ==================================================================== */
  async function loadMyProducts() {
    const products = unwrap(await api.get(ENDPOINT.products));
    State.myProducts = products || [];
    renderMyProducts();
  }

  function filteredMyProducts() {
    const search = ($('#myProductsSearchInput').value || '').toLowerCase();
    const status = $('#myProductsStatusFilter').value;
    return State.myProducts.filter((p) => {
      const matchesSearch = !search || p.name.toLowerCase().includes(search);
      const matchesStatus = status === 'all' || p.status === status;
      return matchesSearch && matchesStatus;
    });
  }

  function renderMyProducts() {
    const list = filteredMyProducts();
    $('#myProductsEmptyState').hidden = list.length !== 0;
    $('#myProductsTableBody').innerHTML = list.map((p) => `
      <tr data-id="${p.id}">
        <td><img class="table-thumb" src="${escapeHtml(p.image_url || 'https://via.placeholder.com/48')}" alt=""></td>
        <td>${escapeHtml(p.name)}</td>
        <td>${escapeHtml(p.category || '—')}</td>
        <td>${formatMoney(p.price)}</td>
        <td>${escapeHtml(p.quantity || '—')}</td>
        <td>${escapeHtml(p.location || '—')}</td>
        <td><span class="status-pill ${escapeHtml(p.status || '')}">${escapeHtml(p.status || 'active')}</span></td>
        <td>
          <button class="btn-icon" data-action="view"><i class="fa-solid fa-eye"></i></button>
          <button class="btn-icon" data-action="edit"><i class="fa-solid fa-pen"></i></button>
          <button class="btn-icon danger" data-action="delete"><i class="fa-solid fa-trash"></i></button>
        </td>
      </tr>`).join('');
  }

  function bindMyProducts() {
    $('#addProductBtn').addEventListener('click', () => openModal('addProductModal'));
    $('#myProductsSearchInput').addEventListener('input', debounce(renderMyProducts, 200));
    $('#myProductsStatusFilter').addEventListener('change', renderMyProducts);

    $('#addProductForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const payload = {
        name: $('#productNameInput').value.trim(),
        category: $('#productCategoryInput').value,
        price: Number($('#productPriceInput').value) || 0,
        quantity: $('#productQuantityInput').value.trim(),
        location: $('#productLocationInput').value.trim(),
        image_url: $('#productImageInput').value.trim(),
      };
      await api.post(ENDPOINT.products, payload);
      e.target.reset();
      closeModal('addProductModal');
      toast('Product listed.', 'success');
      await loadMyProducts();
      loadDashboard();
    });

    $('#editProductForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const id = $('#editProductId').value;
      const payload = {
        name: $('#editProductNameInput').value.trim(),
        category: $('#editProductCategoryInput').value,
        price: Number($('#editProductPriceInput').value) || 0,
        quantity: $('#editProductQuantityInput').value.trim(),
        location: $('#editProductLocationInput').value.trim(),
        image_url: $('#editProductImageInput').value.trim(),
      };
      await api.put(ENDPOINT.products + '?id=' + encodeURIComponent(id), payload);
      closeModal('editProductModal');
      toast('Product updated.', 'success');
      await loadMyProducts();
    });

    $('#myProductsTableBody').addEventListener('click', (e) => {
      const btn = e.target.closest('button[data-action]');
      if (!btn) return;
      const id = e.target.closest('tr').dataset.id;
      const product = State.myProducts.find((p) => String(p.id) === String(id));
      if (!product) return;

      if (btn.dataset.action === 'view') {
        $('#viewProductModalBody').innerHTML = `
          <div class="detail-grid">
            <img class="detail-image" src="${escapeHtml(product.image_url || 'https://via.placeholder.com/300x200')}" alt="">
            <p><strong>Name:</strong> ${escapeHtml(product.name)}</p>
            <p><strong>Category:</strong> ${escapeHtml(product.category || '—')}</p>
            <p><strong>Price:</strong> ${formatMoney(product.price)}</p>
            <p><strong>Quantity:</strong> ${escapeHtml(product.quantity || '—')}</p>
            <p><strong>Location:</strong> ${escapeHtml(product.location || '—')}</p>
            <p><strong>Status:</strong> ${escapeHtml(product.status || 'active')}</p>
          </div>`;
        openModal('viewProductModal');
      } else if (btn.dataset.action === 'edit') {
        $('#editProductId').value = product.id;
        $('#editProductNameInput').value = product.name || '';
        $('#editProductCategoryInput').value = product.category || 'maize';
        $('#editProductPriceInput').value = product.price || '';
        $('#editProductQuantityInput').value = product.quantity || '';
        $('#editProductLocationInput').value = product.location || '';
        $('#editProductImageInput').value = product.image_url || '';
        openModal('editProductModal');
      } else if (btn.dataset.action === 'delete') {
        confirmDelete(`Delete product "${product.name}"?`, async () => {
          await api.del(ENDPOINT.products + '?id=' + encodeURIComponent(product.id));
          toast('Product deleted.', 'success');
          await loadMyProducts();
          loadDashboard();
        });
      }
    });
  }

  /* ==================================================================== *
   * EXPERTS
   * ==================================================================== */
  async function loadExperts() {
    const search = $('#expertSearchInput').value.trim();
    const availability = $('#expertAvailabilityFilter').value;
    const qs = new URLSearchParams({ search, availability }).toString();
    const experts = unwrap(await api.get(ENDPOINT.experts + '?' + qs));
    State.experts = experts || [];
    renderExperts();
  }

  function renderExperts() {
    $('#expertsGrid').innerHTML = State.experts.map((ex) => `
      <div class="expert-card" data-id="${ex.id}">
        <img src="${escapeHtml(ex.avatar_url || 'https://i.pravatar.cc/100')}" alt="${escapeHtml(ex.name)}">
        <h4>${escapeHtml(ex.name)}</h4>
        <p>${escapeHtml(ex.specialization || '')}</p>
        <span class="status-pill ${ex.availability === 'available' ? 'active' : 'fallow'}">${escapeHtml(ex.availability || '')}</span>
        <div class="expert-card-actions">
          <button class="btn-secondary" data-action="contact">Contact</button>
          <button class="btn-primary" data-action="consult">Request Consultation</button>
        </div>
      </div>`).join('');
  }

  function bindExperts() {
    $('#expertSearchInput').addEventListener('input', debounce(loadExperts, 300));
    $('#expertAvailabilityFilter').addEventListener('change', loadExperts);

    $('#expertsGrid').addEventListener('click', (e) => {
      const btn = e.target.closest('button[data-action]');
      if (!btn) return;
      const id = e.target.closest('.expert-card').dataset.id;
      if (btn.dataset.action === 'contact') {
        $('#contactExpertId').value = id;
        $('#contactExpertMessageInput').value = '';
        openModal('contactExpertModal');
      } else if (btn.dataset.action === 'consult') {
        $('#consultationExpertId').value = id;
        $('#requestConsultationForm').reset();
        openModal('requestConsultationModal');
      }
    });

    $('#contactExpertForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      await api.post(ENDPOINT.expertContact, {
        expert_id: $('#contactExpertId').value,
        message: $('#contactExpertMessageInput').value.trim(),
      });
      closeModal('contactExpertModal');
      toast('Message sent to expert.', 'success');
    });

    $('#requestConsultationForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      await api.post(ENDPOINT.expertConsultation, {
        expert_id: $('#consultationExpertId').value,
        date: $('#consultationDateInput').value,
        topic: $('#consultationTopicInput').value.trim(),
        notes: $('#consultationNotesInput').value.trim(),
      });
      closeModal('requestConsultationModal');
      toast('Consultation requested.', 'success');
    });
  }

  /* ==================================================================== *
   * AI ASSISTANT
   * ==================================================================== */
  function addChatBubble(text, from) {
    const history = $('#aiChatHistory');
    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble ' + (from === 'user' ? 'from-user' : 'from-ai');
    bubble.textContent = text;
    history.appendChild(bubble);
    history.scrollTop = history.scrollHeight;
  }

  async function sendAiMessage(text) {
    if (!text.trim()) return;
    addChatBubble(text, 'user');
    $('#aiChatInput').value = '';
    const typing = document.createElement('div');
    typing.className = 'chat-bubble from-ai typing';
    typing.textContent = '…';
    $('#aiChatHistory').appendChild(typing);
    try {
      const res = await api.post(ENDPOINT.aiChat, { message: text });
      typing.remove();
      addChatBubble((res && res.reply) || "Sorry, I couldn't process that.", 'ai');
    } catch (e) {
      typing.remove();
    }
  }

  function bindAiAssistant() {
    $('#aiSendBtn').addEventListener('click', () => sendAiMessage($('#aiChatInput').value));
    $('#aiChatInput').addEventListener('keydown', (e) => {
      if (e.key === 'Enter') sendAiMessage($('#aiChatInput').value);
    });
    $all('.suggested-question-btn').forEach((btn) => {
      btn.addEventListener('click', () => sendAiMessage(btn.textContent));
    });
    $('#clearChatBtn').addEventListener('click', () => {
      $('#aiChatHistory').innerHTML = '';
    });
  }


  /* ==================================================================== *
   * NOTIFICATIONS
   * ==================================================================== */
  async function loadNotifications() {
    const notifications = unwrap(await api.get(ENDPOINT.notifications));
    State.notifications = notifications || [];
    renderNotifications();
    updateNotificationBadges();
  }

  function updateNotificationBadges() {
    const unread = State.notifications.filter((n) => !n.read).length;
    ['#notificationBadge', '#navNotifBadge'].forEach((sel) => {
      const el = $(sel);
      if (el) el.textContent = unread;
    });
  }

  function renderNotifications() {
    const type = $('#notificationTypeFilter').value;
    const list = State.notifications.filter((n) => type === 'all' || n.type === type);
    $('#notificationsEmptyState').hidden = list.length !== 0;
    $('#notificationsList').innerHTML = list.map((n) => `
      <div class="notification-item${n.read ? '' : ' unread'}" data-id="${n.id}">
        <i class="fa-solid fa-bell"></i>
        <div>
          <p>${escapeHtml(n.message || n.title)}</p>
          <small>${formatDate(n.created_at)}</small>
        </div>
      </div>`).join('');
  }

  function bindNotifications() {
    $('#notificationTypeFilter').addEventListener('change', renderNotifications);

    $('#markAllReadBtn').addEventListener('click', async () => {
      await api.post(ENDPOINT.notificationsMarkRead, { all: true });
      State.notifications.forEach((n) => { n.read = true; });
      renderNotifications();
      updateNotificationBadges();
      toast('All notifications marked as read.', 'success');
    });

    $('#notificationsList').addEventListener('click', async (e) => {
      const item = e.target.closest('.notification-item');
      if (!item) return;
      const id = item.dataset.id;
      const n = State.notifications.find((x) => String(x.id) === String(id));
      if (n && !n.read) {
        await api.post(ENDPOINT.notificationsMarkRead, { id });
        n.read = true;
        renderNotifications();
        updateNotificationBadges();
      }
    });
  }

  /* ==================================================================== *
   * REPORTS
   * ==================================================================== */
  function renderBarChart(container, series) {
    if (!series || !series.length) { container.innerHTML = '<p class="muted">No data for this period.</p>'; return; }
    const max = Math.max(...series.map((s) => s.value), 1);
    container.innerHTML = series.map((s) => `
      <div class="chart-bar-row">
        <span class="chart-bar-label">${escapeHtml(s.label)}</span>
        <div class="chart-bar-track"><div class="chart-bar-fill" style="width:${Math.round((s.value / max) * 100)}%"></div></div>
        <span class="chart-bar-value">${escapeHtml(String(s.value))}</span>
      </div>`).join('');
  }

  const REPORT_CHARTS = {
    production: 'cropProductionChart',
    harvest: 'harvestQuantityChart',
    expenses: 'farmExpensesChart',
    sales: 'productSalesChart',
    revenue: 'revenueChart',
    profit: 'profitChart',
  };

  async function loadReports() {
    const period = $('#reportTimeFilter').value;
    await Promise.all(Object.entries(REPORT_CHARTS).map(async ([type, containerId]) => {
      try {
        const res = await api.get(ENDPOINT.reports + `?type=${type}&period=${period}`);
        renderBarChart(document.getElementById(containerId), (res && res.series) || []);
      } catch (e) {
        renderBarChart(document.getElementById(containerId), []);
      }
    }));
  }

  function bindReports() {
    $('#reportTimeFilter').addEventListener('change', loadReports);
    $('#printReportBtn').addEventListener('click', () => window.print());
    $('#generateReportBtn').addEventListener('click', () => openModal('generateReportModal'));
    $('#generateReportForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const type = $('#reportTypeInput').value;
      const period = $('#reportPeriodInput').value;
      const res = await api.get(ENDPOINT.reports + `?type=${type}&period=${period}`);
      closeModal('generateReportModal');
      $('#reportTimeFilter').value = period;
      const containerId = REPORT_CHARTS[type];
      if (containerId) renderBarChart(document.getElementById(containerId), (res && res.series) || []);
      toast('Report generated.', 'success');
    });
  }

  /* ==================================================================== *
   * EXPENSES
   * ==================================================================== */
  async function loadExpenses() {
    const expenses = unwrap(await api.get(ENDPOINT.expenses));
    State.expenses = expenses || [];
    renderExpenses();
  }

  function renderExpenses() {
    const search = ($('#expenseSearchInput').value || '').toLowerCase();
    const category = $('#expenseCategoryFilter').value;
    const list = State.expenses.filter((ex) => {
      const matchesSearch = !search || (ex.description || '').toLowerCase().includes(search);
      const matchesCat = category === 'all' || ex.category === category;
      return matchesSearch && matchesCat;
    });
    $('#expensesEmptyState').hidden = list.length !== 0;
    $('#expensesTableBody').innerHTML = list.map((ex) => `
      <tr data-id="${ex.id}">
        <td>${escapeHtml(ex.description)}</td>
        <td>${escapeHtml(ex.category)}</td>
        <td>${formatMoney(ex.amount)}</td>
        <td>${formatDate(ex.date)}</td>
        <td>${escapeHtml(farmName(ex.farm_id))}</td>
        <td>${escapeHtml(ex.notes || '—')}</td>
        <td><button class="btn-icon danger" data-action="delete"><i class="fa-solid fa-trash"></i></button></td>
      </tr>`).join('');
  }

  function bindExpenses() {
    $('#addExpenseBtn').addEventListener('click', () => openModal('addExpenseModal'));
    $('#expenseSearchInput').addEventListener('input', debounce(renderExpenses, 200));
    $('#expenseCategoryFilter').addEventListener('change', renderExpenses);

    $('#addExpenseForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const payload = {
        description: $('#expenseDescriptionInput').value.trim(),
        category: $('#expenseCategoryInput').value,
        amount: Number($('#expenseAmountInput').value) || 0,
        date: $('#expenseDateInput').value,
        farm_id: $('#expenseFarmInput').value || null,
        notes: $('#expenseNotesInput').value.trim(),
      };
      await api.post(ENDPOINT.expenses, payload);
      e.target.reset();
      closeModal('addExpenseModal');
      toast('Expense recorded.', 'success');
      await loadExpenses();
    });

    $('#expensesTableBody').addEventListener('click', (e) => {
      const btn = e.target.closest('button[data-action="delete"]');
      if (!btn) return;
      const id = e.target.closest('tr').dataset.id;
      confirmDelete('Delete this expense?', async () => {
        await api.del(ENDPOINT.expenses + '?id=' + encodeURIComponent(id));
        toast('Expense deleted.', 'success');
        await loadExpenses();
      });
    });
  }

  /* ==================================================================== *
   * SETTINGS / HELP (mostly client-side, optionally persisted)
   * ==================================================================== */
  function bindSettings() {
    const toggles = ['notifWeatherToggle', 'notifCropToggle', 'notifMarketplaceToggle', 'notifExpertToggle'];
    toggles.forEach((id) => {
      const el = document.getElementById(id);
      if (!el) return;
      el.addEventListener('change', () => saveSettings());
    });
    $('#languageSelect').addEventListener('change', () => saveSettings());

    function saveSettings() {
      const payload = {
        theme: document.getElementById('body').getAttribute('data-theme') || 'light',
        notif_weather: $('#notifWeatherToggle').checked,
        notif_crop: $('#notifCropToggle').checked,
        notif_marketplace: $('#notifMarketplaceToggle').checked,
        notif_expert: $('#notifExpertToggle').checked,
        language: $('#languageSelect').value,
      };
      api.put(ENDPOINT.settings, payload).then(() => toast('Settings saved.', 'success')).catch(() => {});
    }
  }

  function bindHelp() {
    $all('.faq-question').forEach((btn) => {
      btn.addEventListener('click', () => {
        btn.parentElement.classList.toggle('open');
      });
    });
    const contactBtn = $('#contactSupportBtn');
    if (contactBtn) {
      contactBtn.addEventListener('click', () => {
        goToSection('messages');
      });
    }
  }

  /* ==================================================================== *
   * INIT
   * ==================================================================== */
  async function init() {
    bindModalDismissals();
    bindConfirmDelete();
    bindNavigation();
    bindChrome();
    bindQuickActions();
    bindProfile();
    bindFarms();
    bindCrops();
    bindWeather();
    bindActivities();
    bindCalendar();
    bindMarketplace();
    bindMyProducts();
    bindExperts();
    bindAiAssistant();
    bindMessages();
    bindNotifications();
    bindReports();
    bindExpenses();
    bindSettings();
    bindHelp();

    updateCartBadge();

    try {
      await loadFarmerSession();
    } catch (e) {
      // apiFetch already redirects to login on 401; stop further loading.
      return;
    }

    const startSection = location.hash.slice(1) || 'dashboard';
    goToSection(startSection, { force: true });
  }

  document.addEventListener('DOMContentLoaded', init);
})();