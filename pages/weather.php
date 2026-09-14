<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, viewport-fit=cover"
    />
    <title>AgriTech | Farm Weather Intelligence</title>
    <meta
      name="description"
      content="Agricultural weather platform: forecasts, planting/irrigation/spraying advice, alerts, farm management and an AI assistant."
    />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"
    />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
   <link rel="stylesheet" href="../Assets/weather.css">
  </head>
  <body>
    <div class="app">
      <nav class="sidenav">
        <div class="brand">
          <div class="brand-mark">🌾</div>
          <div>
            <div class="brand-name">AgriWeather</div>
            <div class="brand-sub">Farm Intelligence</div>
          </div>
        </div>
        <div class="nav-item active" data-view="dashboard">
          <span class="ic">🏠</span>Dashboard
        </div>
        <div class="nav-item" data-view="forecast">
          <span class="ic">📅</span>Forecast
        </div>
        <div class="nav-item" data-view="farm">
          <span class="ic">🚜</span>Farm
        </div>
        <div class="nav-item" data-view="advice">
          <span class="ic">🌿</span>Agri Advice
        </div>
        <div class="nav-item" data-view="map">
          <span class="ic">🗺️</span>Weather Map
        </div>
        <div class="nav-item" data-view="assistant">
          <span class="ic">💬</span>AI Assistant
        </div>
        <div class="nav-item" data-view="alerts">
          <span class="ic">⚠️</span>Alerts
        </div>
        <div class="nav-item" data-view="history">
          <span class="ic">📊</span>History
        </div>
        <div class="nav-item" data-view="calendar">
          <span class="ic">🗓️</span>Calendar
        </div>
        <div class="nav-divider"></div>
        <div class="nav-item" data-view="settings">
          <span class="ic">⚙️</span>Settings
        </div>
        <div class="nav-farm">
          <b>Green Valley Farm</b>
          3 fields · 3.4 ha tracked
        </div>
      </nav>

      <main class="main">
        <div class="topbar">
          <div class="location-picker">
            <div class="loc-btn" id="locBtn">
              <span class="pin">📍</span>
              <div>
                <div class="loc-name" id="locName">—</div>
                <div class="loc-sub" id="locSub">—</div>
              </div>
              <span class="loc-chevron">▾</span>
            </div>
            <div class="loc-panel" id="locPanel">
              <input
                type="text"
                id="locSearch"
                placeholder="Search city, village, farm or coordinates…"
              />
              <div class="loc-list" id="locList"></div>
              <button class="loc-use-gps" id="useGpsBtn">
                📍 Use my current location
              </button>
            </div>
          </div>
          <div class="topbar-actions">
            <button class="icon-btn" id="unitToggle" title="Toggle units">
              °C
            </button>
            <button class="icon-btn" id="notifBtn" title="Alerts">
              🔔<span
                class="dot"
                id="alertBadgeCount"
                style="display: none"
              ></span>
            </button>
          </div>
        </div>

        <!-- ============ DASHBOARD ============ -->
        <section class="view active" id="view-dashboard">
          <div class="horizon">
            <svg id="horizonSvg"  viewBox="0 0 700 50"   preserveAspectRatio="none"></svg>
            <span class="horizon-label">Live conditions</span>
            <span class="horizon-label right" id="horizonDate"></span>
          </div>

          <div class="hero">
            <div class="hero-card">
              <div class="hero-top">
                <div class="hero-loc" id="heroLoc">—</div>
                <div class="hero-time" id="heroTime">—</div>
              </div>
              <div class="hero-temp-row">
                <div class="hero-icon" id="heroIcon">☀️</div>
                <div>
                  <div class="hero-temp" id="heroTemp">—</div>
                  <div class="hero-cond" id="heroCond">—</div>
                  <div class="hero-feels" id="heroFeels">—</div>
                </div>
              </div>
              <div class="hero-stats" id="heroStats"></div>
            </div>
            <div class="hero-side">
              <div class="mini-card">
                <div class="mini-title">☀️ Sunrise → Sunset</div>
                <div class="sun-arc-wrap">
                  <svg id="sunArcSvg" viewBox="0 0 280 60"   preserveAspectRatio="none"></svg>
                </div>
                <div class="sun-times" id="sunTimes"></div>
              </div>
              <div
                class="mini-card" style="
                  background: linear-gradient(
                    135deg,
                    var(--leaf-100),
                    var(--white)
                  );
                ">
                <div class="mini-title">🌿 Today at a glance</div>
                <p
                  style="
                    font-size: 13px;
                    line-height: 1.5;
                    color: var(--ink-700);
                  "
                  id="aiSummaryText">
                  Loading…
                </p>
              </div>
            </div>
          </div>

          <div class="detail-grid" id="detailGrid"></div>

          <div class="section-head">
            <h2>Hourly forecast</h2>
            <span class="hint">Scroll for the next 24 hours →</span>
          </div>
          <div class="hourly-strip" id="hourlyStrip"></div>

          <div class="section-head">
            <h2>Weather risk alerts</h2>
            <span class="hint">Most important first</span>
          </div>
          <div
            class="grid-auto"
            id="alertsPreview"
            style="display: flex; flex-direction: column; gap: 10px"></div>
        </section>

        <!-- ============ FORECAST ============ -->
        <section class="view" id="view-forecast">
          <div class="section-head">
            <h2>10-day forecast</h2>
            <span class="hint">Tap a day to expand</span>
          </div>
          <div class="daily-list" id="dailyList"></div>
        </section>

        <!-- ============ FARM ============ -->
        <section class="view" id="view-farm">
          <div class="section-head">
            <h2>Farm management</h2>
            <span class="hint">Fields, crops and planting dates</span>
          </div>
          <div
            id="farmList"
            style="display: flex; flex-direction: column; gap: 18px"
          ></div>
        </section>

        <!-- ============ ADVICE ============ -->
        <section class="view" id="view-advice">
          <div class="section-head">
            <h2>Agricultural weather intelligence</h2>
            <span class="hint">Weather, translated into farm decisions</span>
          </div>
          <div class="grid-2">
            <div class="advice-card" id="advicePlant"></div>
            <div class="advice-card" id="adviceIrrigate"></div>
            <div class="advice-card" id="adviceSpray"></div>
            <div class="advice-card" id="adviceHarvest"></div>
          </div>

          <div class="section-head">
            <h2>Rainfall intelligence</h2>
            <span class="hint">Next 12 hours</span>
          </div>
          <div class="card" style="padding: 20px 22px">
            <div class="rain-timeline" id="rainTimeline"></div>
            <div
              class="detail-grid"
              style="grid-template-columns: repeat(3, 1fr)"
              id="rainStats"></div>
            <div
              class="rain-alert"
              id="rainAlertBox"
              style="display: none"></div>
          </div>

          <div class="section-head">
            <h2>Cloud intelligence</h2>
            <span class="hint">Detected from current conditions</span>
          </div>
          <div class="grid-2">
            <div class="card" style="padding: 20px 22px">
              <div class="cloud-visual">
                <svg
                  id="cloudSvg"
                  viewBox="0 0 360 130"
                  preserveAspectRatio="none"></svg>
              </div>
              <div class="cloud-facts" id="cloudFacts"></div>
              <p
                style="
                  font-size: 12.5px;
                  color: var(--ink-700);
                  margin-top: 12px;
                "
                id="cloudEffect"></p>
              <label class="upload-zone" for="cloudUpload">
                📷 Upload a sky photo for AI cloud identification (demo)
                <input type="file" id="cloudUpload" accept="image/*" />
              </label>
            </div>
            <div class="card" style="padding: 20px 22px">
              <div class="mini-title" style="margin-bottom: 10px">
                Crop-specific weather advice
              </div>
              <div class="crop-picker" id="cropPicker"></div>
              <div id="cropAdviceBody"></div>
            </div>
          </div>
        </section>

        <!-- ============ MAP ============ -->
        <section class="view" id="view-map">
          <div class="section-head">
            <h2>Weather map</h2>
            <span class="hint">Switch layers · saved farms are pinned</span>
          </div>
          <div class="map-shell">
            <div class="map-layers" id="mapLayers"></div>
            <div id="leafletMap"></div>
            <div class="map-legend">
              🟢 Low &nbsp; 🟡 Moderate &nbsp; 🔴 High
            </div>
          </div>
        </section>

        <!-- ============ ASSISTANT ============ -->
        <section class="view" id="view-assistant">
          <div class="section-head">
            <h2>AgriWeather AI</h2>
            <span class="hint"
              >Rule-based demo assistant · answers from current forecast
              data</span
            >
          </div>
          <div class="chat-shell">
            <div class="chat-head">
              <div class="chat-avatar">🌾</div>
              <div>
                <div class="chat-title">AgriWeather AI</div>
                <div class="chat-sub">
                  Using conditions for <span id="chatLocName">—</span>
                </div>
              </div>
            </div>
            <div class="chat-body" id="chatBody"></div>
            <div class="suggest-row">
              <div class="suggest-chip">Can I plant maize tomorrow?</div>
              <div class="suggest-chip">Should I spray today?</div>
              <div class="suggest-chip">Will I need irrigation this week?</div>
              <div class="suggest-chip">Is rain expected tomorrow?</div>
              <div class="suggest-chip">What's dangerous for cocoa?</div>
            </div>
            <form class="chat-input-row" id="chatForm">
              <input
                type="text"
                id="chatInput"
                placeholder="Ask about planting, spraying, irrigation…"
                autocomplete="off"/>
              <button class="chat-send" type="submit">➤</button>
            </form>
          </div>
        </section>

        <!-- ============ ALERTS ============ -->
        <section class="view" id="view-alerts">
          <div class="section-head">
            <h2>Weather risk alerts</h2>
            <span class="hint"
              >🟢 Normal · 🟡 Advisory · 🟠 Warning · 🔴 Severe</span
            >
          </div>
          <div
            id="alertsView"
            style="display: flex; flex-direction: column; gap: 12px"
          ></div>
        </section>

        <!-- ============ HISTORY ============ -->
        <section class="view" id="view-history">
          <div class="section-head"><h2>Historical weather</h2></div>
          <div class="hist-tabs">
            <div class="hist-tab" data-range="7d">Last 7 days</div>
            <div class="hist-tab active" data-range="30d">Last 30 days</div>
            <div class="hist-tab" data-range="3m">Last 3 months</div>
            <div class="hist-tab" data-range="12m">Last 12 months</div>
          </div>
          <div class="chart-card">
            <canvas id="histChart" height="90"></canvas>
            <div class="hist-stats" id="histStats"></div>
          </div>
        </section>

        <!-- ============ CALENDAR ============ -->
        <section class="view" id="view-calendar">
          <div class="section-head">
            <h2>Weather-based farm calendar</h2>
            <span class="hint">Next 7 days</span>
          </div>
          <div class="cal-grid" id="calGrid"></div>
        </section>

        <!-- ============ SETTINGS ============ -->
        <section class="view" id="view-settings">
          <div class="section-head"><h2>Settings</h2></div>
          <div class="grid-2">
            <div class="card settings" style="padding: 6px 22px">
              <div class="settings-row">
                <div>
                  <div class="settings-label">Rain alerts</div>
                  <div class="settings-hint">
                    Notify when rain is expected within 2 hours
                  </div>
                </div>
                <div class="toggle on" data-label="rain alerts"><i></i></div>
              </div>
              <div class="settings-row">
                <div>
                  <div class="settings-label">Wind advisories</div>
                  <div class="settings-hint">
                    Notify before strong wind affects spraying
                  </div>
                </div>
                <div class="toggle on" data-label="wind advisories">
                  <i></i>
                </div>
              </div>
              <div class="settings-row">
                <div>
                  <div class="settings-label">Heat & drought warnings</div>
                  <div class="settings-hint">
                    Multi-day dry or extreme heat outlook
                  </div>
                </div>
                <div class="toggle on" data-label="heat warnings"><i></i></div>
              </div>
              <div class="settings-row">
                <div>
                  <div class="settings-label">Frost warnings</div>
                  <div class="settings-hint">
                    Overnight temperature drop risk
                  </div>
                </div>
                <div class="toggle on" data-label="frost warnings"><i></i></div>
              </div>
              <div class="settings-row">
                <div>
                  <div class="settings-label">Spraying window alerts</div>
                  <div class="settings-hint">
                    Best daily spraying window, each morning
                  </div>
                </div>
                <div class="toggle" data-label="spraying alerts"><i></i></div>
              </div>
            </div>
            <div class="card" style="padding: 20px 22px">
              <div class="api-box">
                <div class="ttl">⚙ Weather API configuration</div>
                This build runs on seeded demo data so every screen is
                explorable without a key. Paste a provider key below to switch
                the app to live data — every renderer already expects this exact
                shape.
                <input
                  type="text"
                  id="apiKeyInput"  placeholder="Paste weather provider API key…"/>
                <div style="display: flex; gap: 8px; margin-top: 10px">
                  <button class="btn-primary" id="saveApiKey" style="flex: 1">
                    Save key for this session
                  </button>
                </div>
                <div class="api-status">
                  <span class="d"></span> Demo data active — no live key
                  configured
                </div>
              </div>
            </div>
          </div>
        </section>
      </main>
    </div>

    <nav class="bottom-nav">
      <div class="bn-item active" data-view="dashboard">
        <span class="ic">🏠</span>Home
      </div>
      <div class="bn-item" data-view="forecast">
        <span class="ic">📅</span>Forecast
      </div>
      <div class="bn-item" data-view="advice">
        <span class="ic">🌿</span>Advice
      </div>
      <div class="bn-item" data-view="assistant">
        <span class="ic">💬</span>AI
      </div>
      <div class="bn-item" data-view="alerts">
        <span class="ic">⚠️</span>Alerts
      </div>
      <div class="bn-item" data-view="settings">
        <span class="ic">⚙️</span>More
      </div>
    </nav>

    <div class="toast-wrap" id="toastWrap"></div>

    <script src="../scripts/weather.js"></script>

</body>
</html>