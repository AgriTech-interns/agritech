<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>AgriTech | Admin</title>
 
<link rel="stylesheet" href="admin-dashboard.css">
</head>
<body>
  <div class="app">
    <aside class="sidebar">
      <div class="brand">
        <div class="brand-mark">A</div>
        <div class="brand-text">AgroPeak</div>
      </div>

      <div class="menu-group">
        <div class="menu-label">Main</div>
        <div class="nav-item active" data-section="overview"><span class="icon">▣</span>Overview</div>
        <div class="nav-item" data-section="farmers"><span class="icon">👩‍🌾</span>Farmers</div>
        <div class="nav-item" data-section="crops"><span class="icon">🌾</span>Crops</div>
        <div class="nav-item" data-section="orders"><span class="icon">📦</span>Orders</div>
        <div class="nav-item" data-section="products"><span class="icon">🧺</span>Products</div>
      </div>

      <div class="menu-group">
        <div class="menu-label">Operations</div>
        <div class="nav-item" data-section="messages"><span class="icon">💬</span>Messages</div>
        <div class="nav-item" data-section="reports"><span class="icon">📊</span>Reports</div>
        <div class="nav-item" data-section="alerts"><span class="icon">⚠️</span>Alerts</div>
        <div class="nav-item" data-section="settings"><span class="icon">⚙️</span>Settings</div>
      </div>
    </aside>

    <div class="shell">
      <header class="topbar">
        <div class="search-wrap">
          <span class="search-icon">⌕</span>
          <input id="globalSearch" type="text" placeholder="Search farmers, crops, orders, products..." />
        </div>

        <div class="top-actions">
          <button class="icon-button" type="button" title="Notifications">
            🔔
            <span class="notif-badge">12</span>
          </button>
          <button class="icon-button" type="button" title="Messages">
            ✉️
            <span class="notif-badge" style="background: var(--gold);">3</span>
          </button>

          <div class="admin-user">
            <div class="admin-avatar">AD</div>
            <div class="admin-meta">
              <strong>Admin</strong>
              <span>Platform manager</span>
            </div>
          </div>
        </div>
      </header>

      <main class="main">
        <section id="overview" class="section-panel">
          <div class="welcome-card">
            <div>
              <h2>Good morning, AgroPeak Admin</h2>
              <p>Overview of all farming activity, orders, and platform performance.</p>
            </div>
            <div class="leaf">🌿</div>
          </div>

          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-icon green">👩‍🌾</div>
              <div>
                <p class="stat-label">Farmers</p>
                <h3 id="farmersStat" class="stat-value">0</h3>
                <div class="trend">▲ 12.8% this month</div>
              </div>
            </div>

            <div class="stat-card">
              <div class="stat-icon gold">🌱</div>
              <div>
                <p class="stat-label">Active Crops</p>
                <h3 id="cropsStat" class="stat-value">0</h3>
                <div class="trend">▲ 8.4% this week</div>
              </div>
            </div>

            <div class="stat-card">
              <div class="stat-icon green">💰</div>
              <div>
                <p class="stat-label">Revenue</p>
                <h3 id="revenueStat" class="stat-value">$0</h3>
                <div class="trend">▲ 15.1% vs last month</div>
              </div>
            </div>

            <div class="stat-card">
              <div class="stat-icon red">📦</div>
              <div>
                <p class="stat-label">Orders</p>
                <h3 id="ordersStat" class="stat-value">0</h3>
                <div class="trend" style="color: var(--warning);">▼ 2.6% backlog</div>
              </div>
            </div>
          </div>

          <div class="row-grid">
            <div class="panel">
              <div class="panel-header">
                <h3>Platform performance</h3>
                <button class="btn-secondary" type="button">Export</button>
              </div>
              <div class="tiny-chart" id="performanceChart"></div>
            </div>

            <div class="panel">
              <div class="panel-header">
                <h3>Recent activity</h3>
              </div>
              <ul id="overviewActivity" class="activity-list"></ul>
            </div>
          </div>
        </section>

        <section id="farmers" class="section-panel hidden">
          <div class="toolbar">
            <div class="toolbar-actions">
              <input id="farmerSearch" class="search-box" type="text" placeholder="Search farmers" />
              <select id="farmerStatusFilter" class="filter">
                <option value="all">All status</option>
                <option value="active">Active</option>
                <option value="pending">Pending</option>
                <option value="suspended">Suspended</option>
              </select>
            </div>
            <button class="btn-primary" type="button" id="addFarmerBtn">+ Add farmer</button>
          </div>

          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Location</th>
                  <th>Email</th>
                  <th>Status</th>
                  <th>Joined</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="farmersTable"></tbody>
            </table>
          </div>
        </section>

        <section id="crops" class="section-panel hidden">
          <div class="toolbar">
            <div class="toolbar-actions">
              <input id="cropSearch" class="search-box" type="text" placeholder="Search crops" />
              <select id="cropStatusFilter" class="filter">
                <option value="all">All crop status</option>
                <option value="healthy">Healthy</option>
                <option value="growing">Growing</option>
                <option value="needs_attention">Needs attention</option>
                <option value="harvest_ready">Harvest ready</option>
              </select>
            </div>
            <button class="btn-primary" type="button" id="addCropBtn">+ Add crop</button>
          </div>

          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Crop</th>
                  <th>Farmer</th>
                  <th>Stage</th>
                  <th>Health</th>
                  <th>Yield</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="cropsTable"></tbody>
            </table>
          </div>
        </section>

        <section id="orders" class="section-panel hidden">
          <div class="toolbar">
            <div class="toolbar-actions">
              <input id="orderSearch" class="search-box" type="text" placeholder="Search orders" />
              <select id="orderStatusFilter" class="filter">
                <option value="all">All order status</option>
                <option value="paid">Paid</option>
                <option value="pending">Pending</option>
                <option value="shipped">Shipped</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <button class="btn-primary" type="button" id="addOrderBtn">+ New order</button>
          </div>

          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Order #</th>
                  <th>Customer</th>
                  <th>Item</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="ordersTable"></tbody>
            </table>
          </div>
        </section>

        <section id="products" class="section-panel hidden">
          <div class="toolbar">
            <div class="toolbar-actions">
              <input id="productSearch" class="search-box" type="text" placeholder="Search product" />
              <select id="productCategoryFilter" class="filter">
                <option value="all">All categories</option>
                <option value="seed">Seed</option>
                <option value="fertilizer">Fertilizer</option>
                <option value="equipment">Equipment</option>
                <option value="organic">Organic</option>
              </select>
            </div>
            <button class="btn-primary" type="button" id="addProductBtn">+ Add product</button>
          </div>

          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Category</th>
                  <th>Stock</th>
                  <th>Price</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="productsTable"></tbody>
            </table>
          </div>
        </section>

        <section id="messages" class="section-panel hidden">
          <div class="conversation-layout">
            <div class="chat-list">
              <h3 style="margin: 8px 10px 14px;">Inbox</h3>
              <div id="chatList"></div>
            </div>

            <div class="chat-window">
              <div class="chat-header">
                <div>
                  <strong id="chatRecipient">Support desk</strong>
                  <small id="chatStatus" style="color: var(--muted); display: block; margin-top: 4px;">Online now</small>
                </div>
                <button class="btn-secondary" type="button">View profile</button>
              </div>
              <div id="chatThread" class="chat-thread"></div>
              <div class="chat-compose">
                <input id="replyMessage" type="text" placeholder="Type your reply..." />
                <button class="btn-primary" type="button" id="sendReplyBtn">Send</button>
              </div>
            </div>
          </div>
        </section>

        <section id="reports" class="section-panel hidden">
          <div class="summary-box">
            <div class="box">
              <span>Total sales</span>
              <strong id="reportSales">$0</strong>
            </div>
            <div class="box">
              <span>Orders processed</span>
              <strong id="reportOrders">0</strong>
            </div>
            <div class="box">
              <span>Avg. basket</span>
              <strong id="reportBasket">$0</strong>
            </div>
          </div>

          <div class="kpi-grid">
            <div class="kpi-box">
              <span>Conversion rate</span>
              <strong>6.8%</strong>
            </div>
            <div class="kpi-box">
              <span>Retention</span>
              <strong>74%</strong>
            </div>
            <div class="kpi-box">
              <span>Active campaigns</span>
              <strong>9</strong>
            </div>
          </div>

          <div class="toolbar">
            <div class="toolbar-actions">
              <input class="filter" type="date" id="reportStart" />
              <input class="filter" type="date" id="reportEnd" />
            </div>
            <button class="btn-primary" type="button" id="exportCsvBtn">Export CSV</button>
          </div>
        </section>

        <section id="alerts" class="section-panel hidden">
          <div class="summary-box">
            <div class="box">
              <span>Low stock</span>
              <strong id="lowStockCount">0</strong>
            </div>
            <div class="box">
              <span>Pending orders</span>
              <strong id="pendingOrdersCount">0</strong>
            </div>
            <div class="box">
              <span>System alerts</span>
              <strong id="systemAlertsCount">0</strong>
            </div>
          </div>

          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Alert</th>
                  <th>Scope</th>
                  <th>Priority</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="alertsTable"></tbody>
            </table>
          </div>
        </section>

        <section id="settings" class="section-panel hidden">
          <div class="grid-2">
            <div class="panel">
              <div class="panel-header">
                <h3>Platform settings</h3>
              </div>
              <div style="display: grid; gap: 14px;">
                <label>
                  <div style="font-weight: 600; margin-bottom: 8px;">Platform name</div>
                  <input class="input" value="AgroPeak" />
                </label>
                <label>
                  <div style="font-weight: 600; margin-bottom: 8px;">Default currency</div>
                  <select class="select" style="width: 100%;">
                    <option selected>USD</option>
                    <option>KES</option>
                    <option>UGX</option>
                  </select>
                </label>
                <label>
                  <div style="font-weight: 600; margin-bottom: 8px;">Support email</div>
                  <input class="input" value="support@agropeak.com" />
                </label>
              </div>
            </div>

            <div class="panel">
              <div class="panel-header">
                <h3>System preferences</h3>
              </div>
              <div style="display: grid; gap: 14px;">
                <label style="display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                  <span>Email notifications</span>
                  <input type="checkbox" checked />
                </label>
                <label style="display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                  <span>SMS delivery alerts</span>
                  <input type="checkbox" checked />
                </label>
                <label style="display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                  <span>Auto back-up schedules</span>
                  <input type="checkbox" checked />
                </label>
                <button class="btn-primary" type="button">Save changes</button>
              </div>
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>

  <script>
    const STORAGE_KEY = 'agropeak_admin_dashboard_v1';

    const defaultData = {
      farmers: [
        { id: 1, name: 'Mary Wanjiku', location: 'Nakuru', email: 'mary@farmhub.co', status: 'active', joined: '2025-01-12' },
        { id: 2, name: 'John Kamau', location: 'Kiambu', email: 'john@farmhub.co', status: 'active', joined: '2025-02-19' },
        { id: 3, name: 'Amina Yusuf', location: 'Machakos', email: 'amina@farmhub.co', status: 'pending', joined: '2025-03-04' },
        { id: 4, name: 'Peter Njoroge', location: 'Nyeri', email: 'peter@farmhub.co', status: 'suspended', joined: '2024-12-09' }
      ],
      crops: [
        { id: 1, name: 'Maize', farmer: 'Mary Wanjiku', stage: 'growing', health: 'healthy', yield: '3.4 t/ha' },
        { id: 2, name: 'Tomato', farmer: 'John Kamau', stage: 'harvest_ready', health: 'healthy', yield: '2.1 t/ha' },
        { id: 3, name: 'Rice', farmer: 'Amina Yusuf', stage: 'needs_attention', health: 'needs_attention', yield: '1.7 t/ha' },
        { id: 4, name: 'Cabbage', farmer: 'Peter Njoroge', stage: 'growing', health: 'healthy', yield: '2.8 t/ha' }
      ],
      orders: [
        { id: 1001, customer: 'Daniel Mutua', item: 'Maize seed pack', total: 420, status: 'paid' },
        { id: 1002, customer: 'Catherine Muthoni', item: 'NPK fertilizer', total: 880, status: 'pending' },
        { id: 1003, customer: 'Joseph Kariuki', item: 'Drip irrigation set', total: 1250, status: 'shipped' },
        { id: 1004, customer: 'Lilian Achieng', item: 'Organic compost', total: 560, status: 'cancelled' }
      ],
      products: [
        { id: 1, name: 'Hybrid Maize Seed', category: 'seed', stock: 120, price: 180, status: 'In stock' },
        { id: 2, name: 'NPK 23:23:0', category: 'fertilizer', stock: 34, price: 420, status: 'Low stock' },
        { id: 3, name: 'Irrigation Kit', category: 'equipment', stock: 14, price: 920, status: 'Low stock' },
        { id: 4, name: 'Bio Compost', category: 'organic', stock: 72, price: 260, status: 'In stock' }
      ],
      messages: [
        { id: 1, sender: 'Support Desk', preview: 'Your compliance report is ready.', unread: 2, thread: [
          { sender: 'Support Desk', text: 'Your compliance report is ready for review.' },
          { sender: 'Admin', text: 'Great. Please share the summary for the board meeting.' },
          { sender: 'Support Desk', text: 'Will do. The document includes all active marketplace transactions.' }
        ] },
        { id: 2, sender: 'John Kamau', preview: 'Can we increase tomato seed supply?', unread: 1, thread: [
          { sender: 'John Kamau', text: 'Can we increase tomato seed supply this week?' },
          { sender: 'Admin', text: 'Yes, I will review the inventory and confirm by noon.' }
        ] },
        { id: 3, sender: 'Operations Team', preview: 'Rain warning issued for western region.', unread: 0, thread: [
          { sender: 'Operations Team', text: 'Rain warning issued for western region. Activate early alert messaging.' },
          { sender: 'Admin', text: 'Noted. We will notify the affected logistics partners.' }
        ] }
      ],
      alerts: [
        { id: 1, alert: 'Low stock: NPK 23:23:0', scope: 'Warehouse', priority: 'High', action: 'Restock' },
        { id: 2, alert: 'Late shipment to Nairobi', scope: 'Logistics', priority: 'Medium', action: 'Review' },
        { id: 3, alert: 'Market demand spike: maize', scope: 'Marketplace', priority: 'High', action: 'Promote' }
      ]
    };

    const state = { section: 'overview', data: loadData() };
    const money = value => `$${Number(value).toLocaleString()}`;

    function loadData() {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return structuredClone(defaultData);
      try {
        const parsed = JSON.parse(raw);
        return parsed && typeof parsed === 'object' ? parsed : structuredClone(defaultData);
      } catch {
        return structuredClone(defaultData);
      }
    }

    function saveData() { localStorage.setItem(STORAGE_KEY, JSON.stringify(state.data)); }

    function renderOverview() {
      const farmers = state.data.farmers.length;
      const crops = state.data.crops.length;
      const revenue = state.data.orders.reduce((sum, order) => sum + Number(order.total || 0), 0);
      const orders = state.data.orders.length;

      document.getElementById('farmersStat').textContent = farmers;
      document.getElementById('cropsStat').textContent = crops;
      document.getElementById('revenueStat').textContent = money(revenue);
      document.getElementById('ordersStat').textContent = orders;

      const chart = document.getElementById('performanceChart');
      const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'];
      const values = [52, 64, 48, 68, 74, 82, 90];
      chart.innerHTML = months.map((m, i) => `<div class="bar-col" style="height:${values[i]}%"><span>${m}</span></div>`).join('');

      const activities = [
        { title: 'New farmer approved', time: '12 mins ago', tone: 'success' },
        { title: 'Order #1003 shipped', time: '1 hr ago', tone: 'warning' },
        { title: 'Low stock notice: fertilizer', time: '3 hrs ago', tone: 'danger' },
        { title: 'Marketplace report exported', time: '5 hrs ago', tone: 'neutral' }
      ];

      document.getElementById('overviewActivity').innerHTML = activities.map(item => `
        <li class="activity-item">
          <div class="mini-icon">✓</div>
          <div class="activity-copy" style="flex:1;">
            <strong>${item.title}</strong>
            <small>${item.time}</small>
          </div>
          <span class="pill ${item.tone}">${item.tone === 'success' ? 'New' : item.tone === 'warning' ? 'In transit' : item.tone === 'danger' ? 'Action' : 'Info'}</span>
        </li>
      `).join('');
    }

    function renderFarmers() {
      const query = document.getElementById('farmerSearch').value.trim().toLowerCase();
      const filter = document.getElementById('farmerStatusFilter').value;
      const rows = state.data.farmers.filter(f => {
        const matchesText = !query || [f.name, f.location, f.email].join(' ').toLowerCase().includes(query);
        const matchesStatus = filter === 'all' || f.status === filter;
        return matchesText && matchesStatus;
      });

      document.getElementById('farmersTable').innerHTML = rows.length ? rows.map(f => `
        <tr>
          <td>${f.name}</td>
          <td>${f.location}</td>
          <td>${f.email}</td>
          <td><span class="pill ${f.status === 'active' ? 'success' : f.status === 'pending' ? 'warning' : 'danger'}">${f.status}</span></td>
          <td>${f.joined}</td>
          <td>
            <div class="td-actions">
              <button class="tiny-btn view" type="button">View</button>
              <button class="tiny-btn edit" type="button" data-action="toggle-farmer" data-id="${f.id}">Toggle</button>
              <button class="tiny-btn delete" type="button" data-action="delete-farmer" data-id="${f.id}">Delete</button>
            </div>
          </td>
        </tr>
      `).join('') : '<tr><td colspan="6" style="padding:16px; color: var(--muted);">No farmers found.</td></tr>';
    }

    function renderCrops() {
      const query = document.getElementById('cropSearch').value.trim().toLowerCase();
      const filter = document.getElementById('cropStatusFilter').value;
      const rows = state.data.crops.filter(c => {
        const matchesText = !query || [c.name, c.farmer].join(' ').toLowerCase().includes(query);
        const matchesStatus = filter === 'all' || c.health === filter || c.stage === filter;
        return matchesText && matchesStatus;
      });

      document.getElementById('cropsTable').innerHTML = rows.length ? rows.map(c => `
        <tr>
          <td>${c.name}</td>
          <td>${c.farmer}</td>
          <td>${c.stage.replace('_', ' ')}</td>
          <td><span class="pill ${c.health === 'healthy' ? 'success' : c.health === 'needs_attention' ? 'danger' : 'warning'}">${c.health.replace('_', ' ')}</span></td>
          <td>${c.yield}</td>
          <td>
            <div class="td-actions">
              <button class="tiny-btn edit" type="button" data-action="toggle-crop" data-id="${c.id}">Update</button>
              <button class="tiny-btn delete" type="button" data-action="delete-crop" data-id="${c.id}">Delete</button>
            </div>
          </td>
        </tr>
      `).join('') : '<tr><td colspan="6" style="padding:16px; color: var(--muted);">No crops found.</td></tr>';
    }

    function renderOrders() {
      const query = document.getElementById('orderSearch').value.trim().toLowerCase();
      const filter = document.getElementById('orderStatusFilter').value;
      const rows = state.data.orders.filter(o => {
        const matchesText = !query || [o.customer, o.item, String(o.id)].join(' ').toLowerCase().includes(query);
        const matchesFilter = filter === 'all' || o.status === filter;
        return matchesText && matchesFilter;
      });

      document.getElementById('ordersTable').innerHTML = rows.length ? rows.map(o => `
        <tr>
          <td>#${o.id}</td>
          <td>${o.customer}</td>
          <td>${o.item}</td>
          <td>${money(o.total)}</td>
          <td><span class="pill ${o.status === 'paid' ? 'success' : o.status === 'pending' ? 'warning' : o.status === 'shipped' ? 'neutral' : 'danger'}">${o.status}</span></td>
          <td>
            <div class="td-actions">
              <button class="tiny-btn view" type="button">View</button>
              <button class="tiny-btn edit" type="button" data-action="toggle-order" data-id="${o.id}">Advance</button>
            </div>
          </td>
        </tr>
      `).join('') : '<tr><td colspan="6" style="padding:16px; color: var(--muted);">No orders found.</td></tr>';
    }

    function renderProducts() {
      const query = document.getElementById('productSearch').value.trim().toLowerCase();
      const filter = document.getElementById('productCategoryFilter').value;
      const rows = state.data.products.filter(p => {
        const matchesText = !query || [p.name, p.category].join(' ').toLowerCase().includes(query);
        const matchesFilter = filter === 'all' || p.category === filter;
        return matchesText && matchesFilter;
      });

      document.getElementById('productsTable').innerHTML = rows.length ? rows.map(p => `
        <tr>
          <td>${p.name}</td>
          <td>${p.category}</td>
          <td>${p.stock}</td>
          <td>${money(p.price)}</td>
          <td><span class="pill ${p.stock <= 20 ? 'danger' : 'success'}">${p.stock <= 20 ? 'Low stock' : 'In stock'}</span></td>
          <td>
            <div class="td-actions">
              <button class="tiny-btn edit" type="button" data-action="update-product" data-id="${p.id}">Edit</button>
              <button class="tiny-btn delete" type="button" data-action="delete-product" data-id="${p.id}">Delete</button>
            </div>
          </td>
        </tr>
      `).join('') : '<tr><td colspan="6" style="padding:16px; color: var(--muted);">No products found.</td></tr>';
    }

    function renderMessages() {
      const current = state.data.messages[0];
      const list = document.getElementById('chatList');
      list.innerHTML = state.data.messages.map((msg) => `
        <div class="chat-item ${msg.id === current.id ? 'active' : ''}" data-message-id="${msg.id}">
          <div class="chat-avatar">${msg.sender.split(' ').map(part => part[0]).slice(0, 2).join('').toUpperCase()}</div>
          <div class="chat-meta">
            <strong>${msg.sender}</strong>
            <small>${msg.preview}</small>
          </div>
          ${msg.unread ? `<span class="notif-badge" style="position:static; min-width: 22px; height:22px; margin-left:8px;">${msg.unread}</span>` : ''}
        </div>
      `).join('');

      const threadEl = document.getElementById('chatThread');
      document.getElementById('chatRecipient').textContent = current.sender;
      threadEl.innerHTML = current.thread.map(msg => `
        <div class="message ${msg.sender === 'Admin' ? 'outgoing' : 'incoming'}">${msg.text}</div>
      `).join('');
    }

    function renderAlerts() {
      document.getElementById('lowStockCount').textContent = state.data.products.filter(p => p.stock <= 20).length;
      document.getElementById('pendingOrdersCount').textContent = state.data.orders.filter(o => o.status === 'pending').length;
      document.getElementById('systemAlertsCount').textContent = state.data.alerts.length;

      document.getElementById('alertsTable').innerHTML = state.data.alerts.map(alert => `
        <tr>
          <td>${alert.alert}</td>
          <td>${alert.scope}</td>
          <td><span class="pill ${alert.priority === 'High' ? 'danger' : 'warning'}">${alert.priority}</span></td>
          <td><button class="tiny-btn edit" type="button">${alert.action}</button></td>
        </tr>
      `).join('');
    }

    function renderReports() {
      const totalSales = state.data.orders.reduce((sum, o) => sum + Number(o.total || 0), 0);
      const totalOrders = state.data.orders.length;
      const avgBasket = totalOrders ? totalSales / totalOrders : 0;
      document.getElementById('reportSales').textContent = money(totalSales);
      document.getElementById('reportOrders').textContent = totalOrders;
      document.getElementById('reportBasket').textContent = money(avgBasket);
    }

    function renderAll() {
      renderOverview();
      renderFarmers();
      renderCrops();
      renderOrders();
      renderProducts();
      renderMessages();
      renderReports();
      renderAlerts();
      updateSectionVisibility();
    }

    function updateSectionVisibility() {
      document.querySelectorAll('.section-panel').forEach(section => {
        section.classList.toggle('hidden', section.id !== state.section);
      });
      document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.toggle('active', item.dataset.section === state.section);
      });
    }

    document.querySelectorAll('.nav-item').forEach(item => {
      item.addEventListener('click', () => {
        state.section = item.dataset.section;
        updateSectionVisibility();
      });
    });

    document.getElementById('globalSearch').addEventListener('input', () => {
      renderFarmers();
      renderCrops();
      renderOrders();
      renderProducts();
    });

    document.getElementById('farmerSearch').addEventListener('input', renderFarmers);
    document.getElementById('farmerStatusFilter').addEventListener('change', renderFarmers);
    document.getElementById('cropSearch').addEventListener('input', renderCrops);
    document.getElementById('cropStatusFilter').addEventListener('change', renderCrops);
    document.getElementById('orderSearch').addEventListener('input', renderOrders);
    document.getElementById('orderStatusFilter').addEventListener('change', renderOrders);
    document.getElementById('productSearch').addEventListener('input', renderProducts);
    document.getElementById('productCategoryFilter').addEventListener('change', renderProducts);

    document.getElementById('addFarmerBtn').addEventListener('click', () => {
      const name = prompt('Farmer name:'); if (!name) return;
      const location = prompt('Location:');
      const email = prompt('Email:');
      state.data.farmers.unshift({
        id: Date.now(), name, location: location || 'Unknown', email: email || 'unknown@example.com', status: 'active', joined: new Date().toISOString().slice(0, 10)
      });
      saveData(); renderAll();
    });

    document.getElementById('addCropBtn').addEventListener('click', () => {
      const name = prompt('Crop name:'); const farmer = prompt('Farmer name:'); if (!name || !farmer) return;
      state.data.crops.unshift({ id: Date.now(), name, farmer, stage: 'growing', health: 'healthy', yield: '2.0 t/ha' });
      saveData(); renderAll();
    });

    document.getElementById('addOrderBtn').addEventListener('click', () => {
      const customer = prompt('Customer name:'); const item = prompt('Item name:'); const total = Number(prompt('Total amount:') || 0); if (!customer || !item) return;
      state.data.orders.unshift({ id: Date.now(), customer, item, total, status: 'pending' });
      saveData(); renderAll();
    });

    document.getElementById('addProductBtn').addEventListener('click', () => {
      const name = prompt('Product name:'); const category = prompt('Category (seed/fertilizer/equipment/organic):'); const stock = Number(prompt('Stock quantity:') || 0); const price = Number(prompt('Unit price:') || 0); if (!name || !category) return;
      state.data.products.unshift({ id: Date.now(), name, category, stock, price, status: stock <= 20 ? 'Low stock' : 'In stock' });
      saveData(); renderAll();
    });

    document.addEventListener('click', (event) => {
      const target = event.target.closest('[data-action]');
      if (!target) return;

      const { action, id } = target.dataset;
      const numericId = Number(id);

      if (action === 'toggle-farmer') {
        const farmer = state.data.farmers.find(f => f.id === numericId); if (!farmer) return; farmer.status = farmer.status === 'active' ? 'suspended' : 'active';
      }
      if (action === 'delete-farmer') state.data.farmers = state.data.farmers.filter(f => f.id !== numericId);
      if (action === 'toggle-crop') {
        const crop = state.data.crops.find(c => c.id === numericId); if (!crop) return;
        const order = ['growing', 'healthy', 'needs_attention', 'harvest_ready']; const idx = order.indexOf(crop.health || crop.stage); crop.health = order[(idx + 1) % order.length];
      }
      if (action === 'delete-crop') state.data.crops = state.data.crops.filter(c => c.id !== numericId);
      if (action === 'toggle-order') {
        const order = state.data.orders.find(o => o.id === numericId); if (!order) return;
        const cycle = ['pending', 'paid', 'shipped', 'cancelled']; const idx = cycle.indexOf(order.status); order.status = cycle[(idx + 1) % cycle.length];
      }
      if (action === 'update-product') {
        const product = state.data.products.find(p => p.id === numericId); if (!product) return;
        const nextStock = Number(prompt('New stock quantity:', product.stock) || product.stock); product.stock = nextStock; product.status = nextStock <= 20 ? 'Low stock' : 'In stock';
      }
      if (action === 'delete-product') state.data.products = state.data.products.filter(p => p.id !== numericId);

      saveData(); renderAll();
    });

    document.getElementById('sendReplyBtn').addEventListener('click', () => {
      const input = document.getElementById('replyMessage'); const value = input.value.trim(); if (!value) return;
      const current = state.data.messages[0];
      current.thread.push({ sender: 'Admin', text: value });
      current.preview = value;
      input.value = '';
      renderMessages(); saveData();
    });

    document.getElementById('exportCsvBtn').addEventListener('click', () => {
      const rows = [['Order #', 'Customer', 'Item', 'Total', 'Status'], ...state.data.orders.map(o => [o.id, o.customer, o.item, o.total, o.status])];
      const csv = rows.map(row => row.join(',')).join('\n');
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a'); a.href = url; a.download = 'agropeak-report.csv'; a.click(); URL.revokeObjectURL(url);
    });

    document.addEventListener('click', (event) => {
      const chatItem = event.target.closest('.chat-item');
      if (!chatItem) return;
      const id = Number(chatItem.dataset.messageId);
      const selected = state.data.messages.find(msg => msg.id === id);
      if (!selected) return;
      state.data.messages = state.data.messages.map(msg => msg.id === id ? { ...msg, unread: 0 } : msg);
      renderMessages(); saveData();
    });

    renderAll();
  </script>
</body>
</html>
