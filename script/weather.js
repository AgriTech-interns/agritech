  /* ==========================================================================
   AgriWeather — application logic
   -----------------------------------------------------------------------
   DATA LAYER
   Every weather value in this app is produced by the functions in the
   "DATA SOURCE" block below. Right now they are seeded, deterministic mock
   generators (clearly labeled DEMO DATA in the UI) so the interface is
   fully explorable with no key. To go live, replace the bodies of
   fetchCurrent / fetchHourly / fetchDaily / fetchHistory / fetchAlerts
   with real calls to your weather provider (see the Settings → API panel
   for where a key is stored) — every renderer below consumes the same
   shape, so nothing else needs to change.
   ========================================================================== */

      (function () {
        "use strict";

        /* ---------------------------- STATE -------------------------------- */
        const State = {
          useMetric: true,
          locations: [
            {
              name: "Green Valley Farm",
              region: "Buea, South-West, Cameroon",
              lat: 4.156,
              lon: 9.2432,
            },
            {
              name: "Riverbend Plot",
              region: "Bafoussam, West, Cameroon",
              lat: 5.4737,
              lon: 10.4179,
            },
            {
              name: "North Ridge Fields",
              region: "Garoua, North, Cameroon",
              lat: 9.3265,
              lon: 13.3958,
            },
          ],
          activeLocationIdx: 0,
          crops: [
            "Maize",
            "Cocoa",
            "Cassava",
            "Rice",
            "Tomatoes",
            "Beans",
            "Plantain",
            "Banana",
            "Potato",
            "Vegetables",
          ],
          activeCrop: "Maize",
          farms: [
            {
              name: "Green Valley Farm",
              location: "Buea, South-West",
              size: "3.4 ha",
              soil: "Loamy clay",
              fields: [
                {
                  name: "Field 1",
                  crop: "Maize",
                  size: "2 ha",
                  planted: "10 Jun 2026",
                  harvest: "Est. 20 Sep 2026",
                  irrigation: "Drip",
                },
                {
                  name: "Field 2",
                  crop: "Tomatoes",
                  size: "1 ha",
                  planted: "2 Jul 2026",
                  harvest: "Est. 5 Oct 2026",
                  irrigation: "Sprinkler",
                },
                {
                  name: "Field 3",
                  crop: "Beans",
                  size: "0.4 ha",
                  planted: "18 Jul 2026",
                  harvest: "Est. 30 Sep 2026",
                  irrigation: "Rain-fed",
                },
              ],
            },
          ],
          alerts: [],
          notifications: {
            rain: true,
            wind: true,
            heat: true,
            frost: true,
            spray: true,
          },
          chat: [],
          mapLayer: "temperature",
        };

        const $ = (sel, root = document) => root.querySelector(sel);
        const $$ = (sel, root = document) =>
          Array.from(root.querySelectorAll(sel));

        /* ---------------------------- UTIL ---------------------------------- */
        function seedFromString(str) {
          let h = 0;
          for (let i = 0; i < str.length; i++) {
            h = (h << 5) - h + str.charCodeAt(i);
            h |= 0;
          }
          return Math.abs(h);
        }
        function mulberry32(a) {
          return function () {
            a |= 0;
            a = (a + 0x6d2b79f5) | 0;
            let t = Math.imul(a ^ (a >>> 15), 1 | a);
            t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
            return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
          };
        }
        function rnd(seed) {
          return mulberry32(seed);
        }
        function pad(n) {
          return n < 10 ? "0" + n : "" + n;
        }
        function fmtHour(h) {
          const hh = ((h % 24) + 24) % 24;
          const ampm = hh < 12 ? "AM" : "PM";
          let h12 = hh % 12;
          if (h12 === 0) h12 = 12;
          return h12 + ampm;
        }
        function dayName(offset) {
          const d = new Date();
          d.setDate(d.getDate() + offset);
          return d.toLocaleDateString(undefined, { weekday: "short" });
        }
        function dayDate(offset) {
          const d = new Date();
          d.setDate(d.getDate() + offset);
          return d.toLocaleDateString(undefined, {
            month: "short",
            day: "numeric",
          });
        }
        function cToF(c) {
          return Math.round((c * 9) / 5 + 32);
        }
        function tempDisplay(c) {
          return State.useMetric ? Math.round(c) + "°C" : cToF(c) + "°F";
        }
        function kmhDisplay(v) {
          return Math.round(v) + " km/h";
        }
        function windDir(deg) {
          const dirs = [
            "N",
            "NNE",
            "NE",
            "ENE",
            "E",
            "ESE",
            "SE",
            "SSE",
            "S",
            "SSW",
            "SW",
            "WSW",
            "W",
            "WNW",
            "NW",
            "NNW",
          ];
          return dirs[Math.round(deg / 22.5) % 16];
        }

        const CONDS = [
          { key: "clear", label: "Clear sky", icon: "☀️" },
          { key: "partly", label: "Partly cloudy", icon: "⛅" },
          { key: "cloudy", label: "Cloudy", icon: "☁️" },
          { key: "rain", label: "Light rain", icon: "🌦️" },
          { key: "heavy_rain", label: "Heavy rain", icon: "🌧️" },
          { key: "storm", label: "Thunderstorm", icon: "⛈️" },
          { key: "fog", label: "Fog", icon: "🌫️" },
          { key: "wind", label: "Windy", icon: "💨" },
          { key: "haze", label: "Haze", icon: "🌤️" },
        ];
        function condFor(rng, rainBias) {
          const r = rng();
          if (r < 0.06 * rainBias) return CONDS[5];
          if (r < 0.16 * rainBias) return CONDS[4];
          if (r < 0.32 * rainBias) return CONDS[3];
          if (r < 0.4) return CONDS[6];
          if (r < 0.46) return CONDS[7];
          if (r < 0.48) return CONDS[8];
          if (r < 0.7) return CONDS[1];
          if (r < 0.86) return CONDS[2];
          return CONDS[0];
        }

        /* ---------------------------- DATA SOURCE ---------------------------- */
        // Deterministic per-location "climate" seed so the same farm always looks
        // self-consistent while different farms clearly differ from each other.
        function climateSeed(loc) {
          return seedFromString(
            loc.name + loc.lat.toFixed(2) + loc.lon.toFixed(2),
          );
        }

        function fetchCurrent(loc) {
          const rng = rnd(
            climateSeed(loc) ^ Math.floor(Date.now() / (1000 * 60 * 30)),
          );
          const baseTemp = 21 + (Math.abs(loc.lat) < 6 ? 6 : 2) + rng() * 6;
          const cond = condFor(rng, 1);
          const humidity = Math.round(55 + rng() * 35);
          const wind = 4 + rng() * 22;
          return {
            tempC: baseTemp,
            feelsC: baseTemp + (humidity > 75 ? 2.4 : -0.6),
            cond,
            humidity,
            windKmh: wind,
            windDeg: Math.round(rng() * 360),
            pressure: Math.round(1004 + rng() * 18),
            visibilityKm:
              cond.key === "fog"
                ? +(1 + rng() * 3).toFixed(1)
                : +(8 + rng() * 7).toFixed(1),
            uv:
              cond.key === "clear"
                ? Math.round(7 + rng() * 4)
                : Math.round(2 + rng() * 5),
            dewPointC: Math.round(baseTemp - (100 - humidity) / 5),
            cloudPct:
              cond.key === "clear"
                ? Math.round(rng() * 15)
                : cond.key === "cloudy"
                  ? Math.round(70 + rng() * 30)
                  : Math.round(30 + rng() * 45),
            precipPct: ["rain", "heavy_rain", "storm"].includes(cond.key)
              ? Math.round(55 + rng() * 40)
              : Math.round(rng() * 35),
            sunrise: "05:52",
            sunset: "18:14",
            updated: new Date(),
          };
        }

        function fetchHourly(loc) {
          const rng = rnd(climateSeed(loc) ^ 7331);
          const hours = [];
          const now = new Date().getHours();
          let rainBias = 1;
          for (let i = 0; i < 24; i++) {
            const h = now + i;
            // build a loose rain "front" moving through for realism
            const front =
              Math.sin((i / 24) * Math.PI * 2 + (climateSeed(loc) % 7)) > 0.45
                ? 1.8
                : 0.7;
            const cond = condFor(rng, front);
            const temp =
              22 + Math.sin(((h - 6) / 24) * Math.PI * 2) * 6 + rng() * 2;
            const rainPct = ["rain", "heavy_rain", "storm"].includes(cond.key)
              ? Math.round(45 + rng() * 50)
              : Math.round(rng() * 30);
            hours.push({
              hourOffset: i,
              label: i === 0 ? "Now" : fmtHour(h),
              cond,
              tempC: temp,
              rainPct,
              rainMm:
                rainPct > 40
                  ? +(rng() * 12 + 1).toFixed(1)
                  : +(rng() * 1.5).toFixed(1),
              windKmh: 5 + rng() * 20,
              humidity: Math.round(50 + rng() * 40),
            });
          }
          return hours;
        }

        function fetchDaily(loc, days = 10) {
          const rng = rnd(climateSeed(loc) ^ 9151);
          const out = [];
          for (let i = 0; i < days; i++) {
            const cond = condFor(rng, i % 3 === 0 ? 1.6 : 0.9);
            const max = 24 + rng() * 8;
            const min = max - (7 + rng() * 5);
            const rainPct = ["rain", "heavy_rain", "storm"].includes(cond.key)
              ? Math.round(50 + rng() * 45)
              : Math.round(rng() * 35);
            out.push({
              offset: i,
              cond,
              maxC: max,
              minC: min,
              rainPct,
              rainMm:
                rainPct > 40
                  ? +(rng() * 22 + 2).toFixed(1)
                  : +(rng() * 2).toFixed(1),
              windKmh: 6 + rng() * 18,
              humidity: Math.round(48 + rng() * 40),
              uv: Math.round(4 + rng() * 7),
              sunrise: "05:5" + (i % 5),
              sunset: "18:1" + (i % 5),
            });
          }
          return out;
        }

        function fetchHistory(loc, range) {
          const days = { "7d": 7, "30d": 30, "3m": 90, "12m": 365 }[range];
          const rng = rnd(climateSeed(loc) ^ 4242 ^ days);
          const labels = [];
          const temps = [];
          const rain = [];
          const humidity = [];
          const wind = [];
          const step = days <= 30 ? 1 : days <= 90 ? 3 : 12;
          for (let i = days; i >= 0; i -= step) {
            const d = new Date();
            d.setDate(d.getDate() - i);
            labels.push(
              days <= 30
                ? d.toLocaleDateString(undefined, {
                    month: "short",
                    day: "numeric",
                  })
                : d.toLocaleDateString(undefined, { month: "short" }),
            );
            const seasonal = Math.sin((d.getMonth() / 12) * Math.PI * 2) * 3;
            temps.push(+(24 + seasonal + rng() * 6 - 3).toFixed(1));
            rain.push(+(rng() * rng() * 40).toFixed(1));
            humidity.push(Math.round(55 + rng() * 35));
            wind.push(+(6 + rng() * 14).toFixed(1));
          }
          return { labels, temps, rain, humidity, wind };
        }

        function fetchAlerts(loc, current, daily) {
          const rng = rnd(
            climateSeed(loc) ^ 8811 ^ Math.floor(Date.now() / (1000 * 60 * 60)),
          );
          const alerts = [];
          const heavyDay = daily.find((d) => d.rainMm > 18);
          if (heavyDay) {
            alerts.push({
              sev: heavyDay.rainMm > 30 ? "severe" : "warning",
              icon: "🌧️",
              title: "Heavy rainfall expected",
              body: `${heavyDay.rainMm} mm forecast on ${dayName(heavyDay.offset)}. Newly planted fields may see waterlogging — check drainage channels and clear blocked furrows before it arrives.`,
            });
          }
          if (current.windKmh > 28) {
            alerts.push({
              sev: "warning",
              icon: "💨",
              title: "Strong winds",
              body: `Sustained wind near ${kmhDisplay(current.windKmh)}. Delay pesticide or fertilizer spraying — spray drift risk is high, and staked crops should be checked for support.`,
            });
          }
          const dryStreak = daily.slice(0, 5).every((d) => d.rainMm < 2);
          if (dryStreak && current.humidity < 45) {
            alerts.push({
              sev: "advisory",
              icon: "☀️",
              title: "Dry spell developing",
              body: "Little to no rain expected over the next 5 days with low humidity. Monitor topsoil moisture and plan irrigation for shallow-rooted crops.",
            });
          }
          if (current.tempC > 33) {
            alerts.push({
              sev: "warning",
              icon: "🌡️",
              title: "Extreme heat",
              body: `Daytime temperature near ${tempDisplay(current.tempC)}. Irrigate in early morning or evening to reduce evaporation loss and heat stress on flowering crops.`,
            });
          }
          if (current.tempC < 11) {
            alerts.push({
              sev: "severe",
              icon: "❄️",
              title: "Frost risk overnight",
              body: "Temperatures may fall low enough to damage tender seedlings. Cover sensitive crops or delay transplanting until conditions warm.",
            });
          }
          if (current.humidity > 82) {
            alerts.push({
              sev: "advisory",
              icon: "💧",
              title: "High humidity — disease watch",
              body: "Prolonged leaf wetness raises fungal and bacterial disease risk, especially for tomatoes and beans. Inspect leaves regularly and improve field airflow where possible.",
            });
          }
          if (current.uv >= 9) {
            alerts.push({
              sev: "advisory",
              icon: "🔆",
              title: "High UV radiation",
              body: "UV index is very high around midday. Schedule fieldwork for early morning or late afternoon and stay hydrated.",
            });
          }
          if (current.cond.key === "fog") {
            alerts.push({
              sev: "advisory",
              icon: "🌫️",
              title: "Low visibility",
              body: "Fog may reduce visibility for early morning fieldwork and machinery use. Delay spraying until the air clears — droplets won't settle properly in fog.",
            });
          }
          if (alerts.length === 0) {
            alerts.push({
              sev: "normal",
              icon: "🟢",
              title: "No significant risks detected",
              body: "Conditions over the coming days look within normal range for your fields. Routine monitoring is all that's needed.",
            });
          }
          return alerts;
        }

        /* ---------------------------- AGRI INTELLIGENCE ---------------------------- */
        function analyzePlanting(current, daily) {
          const next3 = daily.slice(0, 3);
          const heavyRainSoon = next3.some((d) => d.rainMm > 20);
          const veryDry =
            next3.every((d) => d.rainMm < 1) && current.humidity < 40;
          const tempOk = current.tempC > 16 && current.tempC < 34;
          let verdict = "good",
            label = "Good conditions for planting";
          const reasons = [];
          if (heavyRainSoon) {
            verdict = "bad";
            label = "Delay planting";
            reasons.push({ t: "b", text: "Heavy rain within 3 days" });
          } else reasons.push({ t: "g", text: "No damaging rain expected" });
          if (veryDry) {
            verdict = verdict === "bad" ? "bad" : "caution";
            if (label === "Good conditions for planting")
              label = "Delay until soil moisture improves";
            reasons.push({ t: "b", text: "Low soil moisture likely" });
          } else reasons.push({ t: "g", text: "Adequate moisture expected" });
          if (!tempOk) {
            verdict = "caution";
            reasons.push({ t: "a", text: "Temperature outside ideal range" });
          } else
            reasons.push({
              t: "g",
              text: `Temperature ${tempDisplay(current.tempC)} is favorable`,
            });
          if (current.windKmh > 25)
            reasons.push({ t: "a", text: "Breezy — protect young seedlings" });
          return { verdict, label, reasons };
        }
        function analyzeIrrigation(current, daily) {
          const next2 = daily.slice(0, 2);
          const rainComing = next2.some((d) => d.rainMm > 8);
          const soilDry =
            current.humidity < 50 &&
            daily.slice(0, 3).every((d) => d.rainMm < 3);
          let verdict, label, detail;
          if (rainComing) {
            verdict = "good";
            label = "Irrigation not needed";
            detail = `Rain expected within 48 hours (~${Math.max(...next2.map((d) => d.rainMm))} mm) — hold off and let it do the work.`;
          } else if (soilDry) {
            verdict = "bad";
            label = "Irrigation recommended";
            detail =
              "No meaningful rain in sight and humidity is low. Crop water demand is likely exceeding supply — irrigate soon.";
          } else {
            verdict = "caution";
            label = "Irrigate tomorrow morning";
            detail =
              "Conditions are moderate. A light early-morning irrigation will minimize evaporation loss while topping up soil moisture.";
          }
          return { verdict, label, detail };
        }
        function analyzeSpray(current, hourly) {
          const windowHours = hourly.slice(0, 10).map((h) => {
            let score = 100;
            if (h.windKmh > 18) score -= 45;
            else if (h.windKmh > 12) score -= 20;
            if (h.rainPct > 35) score -= 45;
            if (h.humidity > 88) score -= 15;
            if (h.tempC > 32) score -= 15;
            return { h, score };
          });
          const best = windowHours.filter((x) => x.score >= 60);
          let level = "b";
          const avgScore =
            windowHours.reduce((a, x) => a + x.score, 0) / windowHours.length;
          if (avgScore >= 68) level = "g";
          else if (avgScore >= 45) level = "a";
          else level = "b";
          let windowText =
            "No favorable spraying window in the next 10 hours — wind or rain risk is too high.";
          if (best.length) {
            windowText = `Good conditions for spraying between ${best[0].h.label === "Now" ? "now" : best[0].h.label} and ${best[best.length - 1].h.label}.`;
          }
          return {
            level,
            windowText,
            avgScore: Math.round(avgScore),
            segs: windowHours
              .slice(0, 8)
              .map((x) => (x.score >= 60 ? "g" : x.score >= 40 ? "a" : "b")),
          };
        }
        function analyzeHarvest(daily) {
          const next4 = daily.slice(0, 4);
          const rainy = next4.find((d) => d.rainMm > 10);
          if (rainy) {
            return {
              verdict: "caution",
              label: "Rain expected — consider harvesting earlier",
              detail: `${rainy.rainMm} mm forecast on ${dayName(rainy.offset)}. Wet conditions can delay drying, lower grain quality, and increase mold risk — bring harvest forward where crops are ready.`,
            };
          }
          return {
            verdict: "good",
            label: "Good harvesting conditions",
            detail:
              "Dry, stable weather is expected for the next several days — a good window for harvesting and field drying.",
          };
        }

        const CLOUD_TYPES = [
          {
            name: "Cumulus",
            alt: "Low (500–2,000 m)",
            effect: "Fair weather, low rain risk",
          },
          {
            name: "Stratocumulus",
            alt: "Low (500–2,000 m)",
            effect: "Overcast, occasional light drizzle",
          },
          {
            name: "Nimbostratus",
            alt: "Low–mid (900–3,000 m)",
            effect: "Steady, prolonged rain likely",
          },
          {
            name: "Cumulonimbus",
            alt: "Vertical (600–12,000 m)",
            effect: "Thunderstorm potential — high",
          },
          {
            name: "Altocumulus",
            alt: "Mid (2,000–6,000 m)",
            effect: "Possible showers later today",
          },
          {
            name: "Altostratus",
            alt: "Mid (2,000–6,000 m)",
            effect: "Widespread light rain within hours",
          },
          {
            name: "Cirrus",
            alt: "High (6,000–12,000 m)",
            effect: "Fair now — front may approach in 24–48h",
          },
          {
            name: "Stratus",
            alt: "Low (0–1,500 m)",
            effect: "Overcast, fog possible at ground level",
          },
        ];
        function analyzeCloud(current) {
          const rng = rnd(
            seedFromString(current.cond.key) ^
              Math.floor(Date.now() / (1000 * 60 * 20)),
          );
          let pick;
          if (current.cond.key === "storm") pick = CLOUD_TYPES[3];
          else if (current.cond.key === "heavy_rain") pick = CLOUD_TYPES[2];
          else if (current.cond.key === "rain") pick = CLOUD_TYPES[5];
          else if (current.cond.key === "cloudy") pick = CLOUD_TYPES[1];
          else if (current.cond.key === "clear") pick = CLOUD_TYPES[6];
          else pick = CLOUD_TYPES[Math.floor(rng() * CLOUD_TYPES.length)];
          const stormRisk =
            pick.name === "Cumulonimbus"
              ? "HIGH"
              : pick.name === "Nimbostratus"
                ? "MODERATE"
                : "LOW";
          return { type: pick, coverage: current.cloudPct, stormRisk };
        }

        const CROP_NOTES = {
          Maize: {
            risk: "Fungal & stem borer risk",
            note: "High humidity and frequent rainfall raise fungal disease pressure (leaf blight, rust). Ensure good row spacing for airflow and scout for stem borer after heavy rain.",
          },
          Cocoa: {
            risk: "Black pod risk",
            note: "Warm, humid, wet conditions strongly favor black pod disease. Improve canopy ventilation and remove infected pods promptly; avoid spraying just before rain.",
          },
          Cassava: {
            risk: "Waterlogging risk",
            note: "Cassava tolerates dry spells well but is sensitive to waterlogged soil — ensure drainage ahead of forecast heavy rain.",
          },
          Rice: {
            risk: "Blast disease risk",
            note: "Extended leaf wetness under high humidity increases blast and bacterial blight risk. Maintain steady water levels rather than fluctuating flooding.",
          },
          Tomatoes: {
            risk: "Blight risk",
            note: "Cool, humid, wet conditions are ideal for early and late blight. Stake plants for airflow and avoid overhead irrigation in humid stretches.",
          },
          Beans: {
            risk: "Rust & rot risk",
            note: "Wet foliage for extended periods encourages rust and root rot. Time spraying and weeding around dry windows.",
          },
          Plantain: {
            risk: "Sigatoka risk",
            note: "Persistent humidity favors black Sigatoka leaf spot. Prune lower leaves to improve airflow through the stand.",
          },
          Banana: {
            risk: "Sigatoka & wind risk",
            note: "Similar leaf-spot pressure to plantain, plus wide leaves are vulnerable to wind damage in gusty conditions.",
          },
          Potato: {
            risk: "Late blight risk",
            note: "Cool, damp weather is the classic trigger for late blight. Hill soil well ahead of heavy rain to protect tubers.",
          },
          Vegetables: {
            risk: "Mixed disease & pest risk",
            note: "Leafy vegetables are sensitive to both waterlogging and heat stress — adjust watering with the forecast rather than a fixed schedule.",
          },
        };

        /* ---------------------------- RENDER: HORIZON / SUN ARC ---------------------------- */
        function renderHorizon() {
          const now = new Date();
          const nowFrac = (now.getHours() * 60 + now.getMinutes()) / 1440;
          const sunX = 6 + nowFrac * 88;
          const el = $("#horizonSvg");
          el.innerHTML = `
    <polyline points="0,42 40,40 80,44 120,38 160,43 200,39 240,44 280,41 320,40 360,42 400,39 440,43 480,41 520,40 560,44 600,41 640,42 680,40 700,41" fill="none" stroke="var(--soil-600)" stroke-opacity="0.35" stroke-width="2"/>
    ${Array.from({ length: 26 })
      .map(
        (_, i) =>
          `<line x1="${10 + i * 27}" y1="46" x2="${10 + i * 27}" y2="${46 - 6 - ((i * 37) % 9)}" stroke="var(--leaf-500)" stroke-opacity="0.55" stroke-width="2.4" stroke-linecap="round"/>`,
      )
      .join("")}
    <circle cx="${sunX * 7}" cy="${26 - Math.sin(nowFrac * Math.PI) * 16}" r="7" fill="var(--sun-500)" opacity="0.95"/>
    <circle cx="${sunX * 7}" cy="${26 - Math.sin(nowFrac * Math.PI) * 16}" r="13" fill="var(--sun-300)" opacity="0.35"/>
  `;
        }
        function renderSunArc(current) {
          const el = $("#sunArcSvg");
          const [sh, sm] = current.sunrise.split(":").map(Number);
          const [eh, em] = current.sunset.split(":").map(Number);
          const now = new Date();
          const nowMin = now.getHours() * 60 + now.getMinutes();
          const riseMin = sh * 60 + sm,
            setMin = eh * 60 + em;
          const frac = Math.min(
            1,
            Math.max(0, (nowMin - riseMin) / (setMin - riseMin)),
          );
          const x = 10 + frac * 260,
            y = 56 - Math.sin(frac * Math.PI) * 46;
          el.innerHTML = `
    <path d="M10,56 Q140,-18 270,56" fill="none" stroke="var(--line)" stroke-width="2" stroke-dasharray="3 5"/>
    <circle cx="${x}" cy="${y}" r="7" fill="var(--sun-500)"/>
    <circle cx="10" cy="56" r="3" fill="var(--ink-300)"/>
    <circle cx="270" cy="56" r="3" fill="var(--ink-300)"/>
  `;
        }

        /* ---------------------------- RENDER: DASHBOARD ---------------------------- */
        let CURRENT, HOURLY, DAILY, ALERTS;

        function loc() {
          return State.locations[State.activeLocationIdx];
        }

        function refreshData() {
          CURRENT = fetchCurrent(loc());
          HOURLY = fetchHourly(loc());
          DAILY = fetchDaily(loc());
          ALERTS = fetchAlerts(loc(), CURRENT, DAILY);
        }

        function renderLocationUI() {
          $("#locName").textContent = loc().name;
          $("#locSub").textContent = loc().region;
          const list = $("#locList");
          list.innerHTML = "";
          State.locations.forEach((l, i) => {
            const d = document.createElement("div");
            d.className = "loc-list-item";
            d.innerHTML = `<span>📍</span><span>${l.name}<br><span class="mono" style="font-size:10.5px;color:var(--ink-500)">${l.region}</span></span>`;
            d.onclick = () => {
              State.activeLocationIdx = i;
              closeLocPanel();
              refreshAll();
              toast("Switched to " + l.name);
            };
            list.appendChild(d);
          });
        }
        function closeLocPanel() {
          $("#locPanel").classList.remove("open");
        }

        function renderHero() {
          $("#heroTemp").innerHTML =
            `${Math.round(CURRENT.tempC)}<sup>°${State.useMetric ? "C" : "F"}</sup>`;
          $("#heroIcon").textContent = CURRENT.cond.icon;
          $("#heroCond").textContent = CURRENT.cond.label;
          $("#heroFeels").textContent =
            `Feels like ${tempDisplay(CURRENT.feelsC)} · Updated just now`;
          $("#heroLoc").textContent =
            loc().name.toUpperCase() + " · " + loc().region;
          $("#heroTime").innerHTML =
            new Date().toLocaleDateString(undefined, {
              weekday: "long",
              month: "long",
              day: "numeric",
            }) +
            "<br>" +
            new Date().toLocaleTimeString(undefined, {
              hour: "2-digit",
              minute: "2-digit",
            });
          $("#heroStats").innerHTML = `
    <div class="hero-stat"><div class="k">Humidity</div><div class="v">${CURRENT.humidity}%</div></div>
    <div class="hero-stat"><div class="k">Wind</div><div class="v">${kmhDisplay(CURRENT.windKmh)}</div></div>
    <div class="hero-stat"><div class="k">Rain chance</div><div class="v">${CURRENT.precipPct}%</div></div>
    <div class="hero-stat"><div class="k">UV Index</div><div class="v">${CURRENT.uv}</div></div>
  `;
          $("#sunTimes").innerHTML =
            `<span>↑ ${CURRENT.sunrise}</span><span>↓ ${CURRENT.sunset}</span>`;
          renderSunArc(CURRENT);

          $("#detailGrid").innerHTML = [
            ["🧭", "Wind dir.", windDir(CURRENT.windDeg)],
            ["📊", "Pressure", CURRENT.pressure + " <small>hPa</small>"],
            ["👁", "Visibility", CURRENT.visibilityKm + " <small>km</small>"],
            ["💧", "Dew point", tempDisplay(CURRENT.dewPointC)],
            ["☁️", "Cloud cover", CURRENT.cloudPct + "%"],
            ["🌡", "Feels like", tempDisplay(CURRENT.feelsC)],
          ]
            .map(
              ([ic, k, v]) =>
                `<div class="detail-tile"><div class="k">${ic} ${k}</div><div class="v">${v}</div></div>`,
            )
            .join("");
        }

        function renderHourly() {
          $("#hourlyStrip").innerHTML = HOURLY.map((h) => {
            let flag = "";
            if (h.cond.key === "storm") flag = "⛈";
            else if (
              h.rainPct > 60 &&
              h.hourOffset > 0 &&
              HOURLY[h.hourOffset - 1].rainPct <= 60
            )
              flag = "🌧";
            else if (h.windKmh > 26) flag = "💨";
            else if (h.tempC > 34) flag = "🌡";
            return `<div class="hour-card ${flag ? "flag" : ""}">
      ${flag ? `<div class="flagtag">${flag}</div>` : ""}
      <div class="t">${h.label}</div>
      <div class="ic">${h.cond.icon}</div>
      <div class="temp">${tempDisplay(h.tempC)}</div>
      <div class="rain">💧 ${h.rainPct}%</div>
      <div class="sub">${kmhDisplay(h.windKmh)} · ${h.humidity}%</div>
    </div>`;
          }).join("");
        }

        function renderDaily() {
          const maxT = Math.max(...DAILY.map((d) => d.maxC));
          const minT = Math.min(...DAILY.map((d) => d.minC));
          $("#dailyList").innerHTML = DAILY.map((d, i) => {
            const left = ((d.minC - minT) / (maxT - minT)) * 100;
            const width = ((d.maxC - d.minC) / (maxT - minT)) * 100;
            return `<div class="day-row" data-i="${i}">
      <div><span class="dname">${i === 0 ? "Today" : dayName(i)}</span><span class="ddate">${dayDate(i)}</span></div>
      <div class="dic">${d.cond.icon}</div>
      <div class="drain"><span>💧</span><span>${d.rainPct}%</span><span class="lbl" style="color:var(--ink-500)">· ${d.rainMm}mm</span></div>
      <div class="drange"><span class="dmin">${tempDisplay(d.minC)}</span><div class="range-bar" style="flex:1"><i style="left:${left}%; width:${width}%"></i></div><span class="dmax">${tempDisplay(d.maxC)}</span></div>
      <div></div>
      <div class="dchev">›</div>
      <div class="day-detail">
        <div><div class="k">Wind</div><div class="v">${kmhDisplay(d.windKmh)}</div></div>
        <div><div class="k">Humidity</div><div class="v">${d.humidity}%</div></div>
        <div><div class="k">UV Index</div><div class="v">${d.uv}</div></div>
        <div><div class="k">Sunrise</div><div class="v">${d.sunrise}</div></div>
        <div><div class="k">Sunset</div><div class="v">${d.sunset}</div></div>
      </div>
    </div>`;
          }).join("");
          $$(".day-row").forEach((row) => {
            row.addEventListener("click", (e) => {
              row.classList.toggle("open");
            });
          });
        }

        /* ---------------------------- RENDER: ADVICE ---------------------------- */
        function verdictClass(v) {
          return v === "good" ? "good" : v === "bad" ? "bad" : "caution";
        }
        function chipClass(t) {
          return "chip " + t;
        }

        function renderAdvice() {
          const plant = analyzePlanting(CURRENT, DAILY);
          const irrigate = analyzeIrrigation(CURRENT, DAILY);
          const spray = analyzeSpray(CURRENT, HOURLY);
          const harvest = analyzeHarvest(DAILY);

          $("#advicePlant").innerHTML = `
    <div class="advice-head"><span class="advice-ic">🌱</span><div><div class="advice-title">Planting</div></div></div>
    <div class="advice-verdict ${verdictClass(plant.verdict)}">${plant.label.toUpperCase()}</div>
    <div class="advice-why">${plant.reasons.map((r) => `<span class="${chipClass(r.t)}">${r.text}</span>`).join("")}</div>
  `;
          $("#adviceIrrigate").innerHTML = `
    <div class="advice-head"><span class="advice-ic">💧</span><div><div class="advice-title">Irrigation</div></div></div>
    <div class="advice-verdict ${verdictClass(irrigate.verdict)}">${irrigate.label.toUpperCase()}</div>
    <div class="advice-body">${irrigate.detail}</div>
  `;
          const sprayLabel =
            spray.level === "g"
              ? "🟢 Excellent"
              : spray.level === "a"
                ? "🟡 Moderate"
                : "🔴 Poor";
          $("#adviceSpray").innerHTML = `
    <div class="advice-head"><span class="advice-ic">🧴</span><div><div class="advice-title">Spraying conditions</div></div></div>
    <div class="advice-verdict ${spray.level === "g" ? "good" : spray.level === "a" ? "caution" : "bad"}">${sprayLabel}</div>
    <div class="spray-gauge">${spray.segs.map((s) => `<div class="seg on ${s}"></div>`).join("")}</div>
    <div class="spray-window">${spray.windowText}</div>
  `;
          $("#adviceHarvest").innerHTML = `
    <div class="advice-head"><span class="advice-ic">🌾</span><div><div class="advice-title">Harvesting</div></div></div>
    <div class="advice-verdict ${verdictClass(harvest.verdict)}">${harvest.label.toUpperCase()}</div>
    <div class="advice-body">${harvest.detail}</div>
  `;

          renderRainIntel();
          renderCloudIntel();
          renderCropAdvice();
          renderAiSummary(plant, irrigate, spray, harvest);
        }

        function renderAiSummary(plant, irrigate, spray, harvest) {
          const c = CURRENT;
          let diseaseNote = "";
          if (c.humidity > 78) {
            diseaseNote = ` Humidity is running high, which can raise fungal disease pressure on ${State.activeCrop.toLowerCase()} — keep an eye on leaves for early spotting or wilting.`;
          }
          const text = `Temperature is ${tempDisplay(c.tempC)} with ${c.humidity}% humidity and a ${c.precipPct}% chance of rain today.${diseaseNote} ${spray.level === "b" ? "Hold off on spraying — wind or rain risk is too high right now." : spray.level === "g" ? "Spraying conditions are favorable for most of the day." : "Spraying is workable in short windows — check the gauge above before heading out."} ${irrigate.label}.`;
          $("#aiSummaryText").textContent = text;
        }

        function renderRainIntel() {
          const next12 = HOURLY.slice(0, 12);
          $("#rainTimeline").innerHTML = next12
            .map((h) => {
              const heavy = h.rainPct > 55;
              return `<div class="rain-col">
      <div class="rain-pct">${h.rainPct}%</div>
      <div class="rain-bar-wrap"><div class="rain-bar ${heavy ? "heavy" : ""}" style="height:${Math.max(4, h.rainPct)}%">${heavy ? '<div class="rain-flag">🌧</div>' : ""}</div></div>
      <div class="rain-hr">${h.label}</div>
    </div>`;
            })
            .join("");
          const startIdx = next12.findIndex((h) => h.rainPct > 55);
          const totalMm = next12.reduce((a, h) => a + h.rainMm, 0).toFixed(1);
          $("#rainStats").innerHTML = `
    <div class="detail-tile"><div class="k">💧 Expected total</div><div class="v">${totalMm} <small>mm / 12h</small></div></div>
    <div class="detail-tile"><div class="k">⏱ Starts</div><div class="v">${startIdx >= 0 ? next12[startIdx].label : "—"}</div></div>
    <div class="detail-tile"><div class="k">⏱ Peak intensity</div><div class="v">${next12.reduce((m, h) => (h.rainPct > m.rainPct ? h : m), next12[0]).label}</div></div>
  `;
          const alertBox = $("#rainAlertBox");
          if (startIdx >= 0) {
            alertBox.style.display = "flex";
            alertBox.innerHTML = `<span class="ic">⚠️</span><div><b>Significant rainfall expected</b><span>Rain is likely to intensify around ${next12[startIdx].label}. Consider delaying spraying or fieldwork planned for that window.</span></div>`;
          } else {
            alertBox.style.display = "none";
          }
        }

        function renderCloudIntel() {
          const c = analyzeCloud(CURRENT);
          const el = $("#cloudSvg");
          el.innerHTML = `
    <ellipse cx="90" cy="60" rx="70" ry="30" fill="white" opacity="0.9"/>
    <ellipse cx="180" cy="45" rx="90" ry="36" fill="white" opacity="0.85"/>
    <ellipse cx="290" cy="65" rx="65" ry="26" fill="white" opacity="0.8"/>
    <ellipse cx="230" cy="80" rx="110" ry="28" fill="white" opacity="0.75"/>
  `;
          $("#cloudFacts").innerHTML = `
    <div class="f"><div class="k">Cloud type</div><div class="v">${c.type.name}</div></div>
    <div class="f"><div class="k">Coverage</div><div class="v">${c.coverage}%</div></div>
    <div class="f"><div class="k">Altitude</div><div class="v" style="font-size:13px">${c.type.alt}</div></div>
  `;
          $("#cloudEffect").innerHTML =
            `<b>Expected effect:</b> ${c.type.effect} &nbsp;·&nbsp; <b>Thunderstorm potential:</b> ${c.stormRisk}`;
        }

        function renderCropAdvice() {
          $("#cropPicker").innerHTML = State.crops
            .map(
              (cr) =>
                `<div class="crop-pill ${cr === State.activeCrop ? "active" : ""}" data-crop="${cr}">${cropEmoji(cr)} ${cr}</div>`,
            )
            .join("");
          $$(".crop-pill").forEach(
            (p) =>
              (p.onclick = () => {
                State.activeCrop = p.dataset.crop;
                renderCropAdvice();
              }),
          );
          const info = CROP_NOTES[State.activeCrop];
          $("#cropAdviceBody").innerHTML = `
    <div class="advice-head"><span class="advice-ic">${cropEmoji(State.activeCrop)}</span><div><div class="advice-title">${State.activeCrop} weather risk</div><div class="advice-verdict caution" style="font-size:17px">${info.risk}</div></div></div>
    <div class="advice-body">${info.note}</div>
  `;
        }
        function cropEmoji(c) {
          return (
            {
              Maize: "🌽",
              Cocoa: "🍫",
              Cassava: "🥔",
              Rice: "🌾",
              Tomatoes: "🍅",
              Beans: "🫘",
              Plantain: "🍌",
              Banana: "🍌",
              Potato: "🥔",
              Vegetables: "🥬",
            }[c] || "🌱"
          );
        }

        /* ---------------------------- RENDER: ALERTS ---------------------------- */
        function renderAlerts() {
          const badge = $("#alertBadgeCount");
          const risky = ALERTS.filter((a) => a.sev !== "normal").length;
          badge.style.display = risky > 0 ? "block" : "none";
          $("#alertsView").innerHTML = ALERTS.map(
            (a) => `
    <div class="alert-card">
      <div class="alert-sev ${a.sev}">${a.icon}</div>
      <div style="flex:1">
        <span class="alert-badge ${a.sev}">${a.sev}</span>
        <div class="alert-title">${a.title}</div>
        <div class="alert-body">${a.body}</div>
      </div>
    </div>`,
          ).join("");
          $("#alertsPreview").innerHTML = ALERTS.slice(0, 2)
            .map(
              (a) => `
    <div class="alert-card">
      <div class="alert-sev ${a.sev}">${a.icon}</div>
      <div style="flex:1">
        <span class="alert-badge ${a.sev}">${a.sev}</span>
        <div class="alert-title">${a.title}</div>
        <div class="alert-body">${a.body}</div>
      </div>
    </div>`,
            )
            .join("");
        }

        /* ---------------------------- RENDER: FARM ---------------------------- */
        function renderFarm() {
          const wrap = $("#farmList");
          wrap.innerHTML = "";
          State.farms.forEach((f, fi) => {
            const div = document.createElement("div");
            div.className = "farm-card";
            div.innerHTML = `
      <div class="farm-banner"><h3>${f.name}</h3><span>${f.location} · ${f.size} · ${f.soil}</span></div>
      <div class="farm-body">
        ${f.fields
          .map(
            (fd, idx) => `
          <div class="field-row">
            <div>
              <div class="field-name">${cropEmoji(fd.crop)} ${fd.name} — ${fd.crop}</div>
              <div class="field-meta">${fd.size} · Planted ${fd.planted} · ${fd.harvest} · ${fd.irrigation}</div>
            </div>
            <span class="field-tag">${fd.crop}</span>
          </div>`,
          )
          .join("")}
        <button class="add-field-btn" data-fi="${fi}">+ Add field</button>
        <div class="field-form" style="display:none">
          <div><label>Crop</label><select class="fCrop">${State.crops.map((c) => `<option>${c}</option>`).join("")}</select></div>
          <div><label>Size (ha)</label><input class="fSize" placeholder="e.g. 1.5"/></div>
          <div><label>Planting date</label><input class="fPlanted" type="date"/></div>
          <div><label>Irrigation method</label><select class="fIrr"><option>Rain-fed</option><option>Drip</option><option>Sprinkler</option><option>Furrow</option></select></div>
          <div class="full"><button class="btn-primary fSave" style="width:100%">Save field</button></div>
        </div>
      </div>`;
            wrap.appendChild(div);
            const addBtn = div.querySelector(".add-field-btn");
            const form = div.querySelector(".field-form");
            addBtn.onclick = () => {
              form.style.display =
                form.style.display === "none" ? "grid" : "none";
            };
            div.querySelector(".fSave").onclick = () => {
              const crop = div.querySelector(".fCrop").value;
              const size = div.querySelector(".fSize").value || "1 ha";
              const planted = div.querySelector(".fPlanted").value || "Today";
              const irr = div.querySelector(".fIrr").value;
              f.fields.push({
                name: "Field " + (f.fields.length + 1),
                crop,
                size: size + (size.includes("ha") ? "" : " ha"),
                planted,
                harvest: "Est. TBD",
                irrigation: irr,
              });
              renderFarm();
              toast("Field added to " + f.name);
            };
          });
        }

        /* ---------------------------- RENDER: CALENDAR ---------------------------- */
        function renderCalendar() {
          const wrap = $("#calGrid");
          wrap.innerHTML = "";
          DAILY.slice(0, 7).forEach((d, i) => {
            const tags = [];
            const plant = analyzePlanting(
              i === 0
                ? CURRENT
                : {
                    ...CURRENT,
                    tempC: d.maxC,
                    humidity: d.humidity,
                    windKmh: d.windKmh,
                  },
              DAILY.slice(i),
            );
            if (d.rainMm > 15) tags.push(["rain", "🌧 Rain preparation"]);
            if (plant.verdict === "good" && d.rainMm < 10)
              tags.push(["plant", "🌱 Good planting"]);
            if (d.rainMm < 3 && d.humidity < 60)
              tags.push(["irrigate", "💧 Irrigation window"]);
            if (d.windKmh < 16 && d.rainPct < 35)
              tags.push(["spray", "🧴 Spray OK"]);
            else tags.push(["avoid", "🚫 Avoid spraying"]);
            if (i >= 4 && d.rainMm < 5)
              tags.push(["harvest", "🌾 Harvest ready"]);
            const div = document.createElement("div");
            div.className = "cal-day";
            div.innerHTML = `<div class="dn">${i === 0 ? "TODAY" : dayName(i)}</div><div class="dd">${dayDate(i)}</div>${tags
              .slice(0, 3)
              .map(
                ([cls, label]) =>
                  `<span class="cal-tag ${cls}">${label}</span>`,
              )
              .join("")}`;
            wrap.appendChild(div);
          });
        }

        /* ---------------------------- RENDER: MAP ---------------------------- */
        let leafletMap,
          layerMarkers = [];
        function renderMap() {
          if (!leafletMap) {
            leafletMap = L.map("leafletMap", { zoomControl: true }).setView(
              [loc().lat, loc().lon],
              7,
            );
            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
              attribution: "&copy; OpenStreetMap contributors",
              maxZoom: 18,
            }).addTo(leafletMap);
          } else {
            leafletMap.setView([loc().lat, loc().lon], 7);
          }
          layerMarkers.forEach((m) => leafletMap.removeLayer(m));
          layerMarkers = [];
          State.locations.forEach((l) => {
            const c = fetchCurrent(l);
            const valByLayer = {
              temperature: tempDisplay(c.tempC),
              rainfall: c.precipPct + "%",
              wind: kmhDisplay(c.windKmh),
              humidity: c.humidity + "%",
              cloud: c.cloudPct + "%",
              storms: c.cond.key === "storm" ? "Active" : "None",
              uv: "UV " + c.uv,
              pressure: c.pressure + " hPa",
            };
            const icon = L.divIcon({
              className: "",
              html: `<div style="background:#254E32;color:#fff;padding:5px 10px;border-radius:20px;font-family:'IBM Plex Mono',monospace;font-size:11px;white-space:nowrap;box-shadow:0 4px 10px rgba(0,0,0,.25);border:2px solid #fff;">📍 ${l.name}: ${valByLayer[State.mapLayer]}</div>`,
              iconAnchor: [10, 10],
            });
            const marker = L.marker([l.lat, l.lon], { icon }).addTo(leafletMap);
            layerMarkers.push(marker);
          });
          setTimeout(() => leafletMap.invalidateSize(), 200);
        }
        function renderMapLayerButtons() {
          const layers = [
            ["temperature", "🌡 Temperature"],
            ["rainfall", "🌧 Rainfall"],
            ["wind", "💨 Wind"],
            ["humidity", "💧 Humidity"],
            ["cloud", "☁️ Cloud cover"],
            ["storms", "⛈ Storms"],
            ["uv", "🔆 UV"],
            ["pressure", "📊 Pressure"],
          ];
          $("#mapLayers").innerHTML = layers
            .map(
              ([k, l]) =>
                `<div class="layer-btn ${State.mapLayer === k ? "active" : ""}" data-l="${k}">${l}</div>`,
            )
            .join("");
          $$(".layer-btn").forEach(
            (b) =>
              (b.onclick = () => {
                State.mapLayer = b.dataset.l;
                renderMap();
                renderMapLayerButtons();
              }),
          );
        }

        /* ---------------------------- RENDER: HISTORY ---------------------------- */
        let histChart,
          activeRange = "30d";
        function renderHistory() {
          const data = fetchHistory(loc(), activeRange);
          const ctx = $("#histChart").getContext("2d");
          if (histChart) histChart.destroy();
          histChart = new Chart(ctx, {
            type: "bar",
            data: {
              labels: data.labels,
              datasets: [
                {
                  type: "line",
                  label: "Temp (°C)",
                  data: data.temps,
                  borderColor: "#2E7BC4",
                  backgroundColor: "transparent",
                  yAxisID: "y",
                  tension: 0.35,
                  pointRadius: 0,
                  borderWidth: 2.5,
                },
                {
                  type: "bar",
                  label: "Rainfall (mm)",
                  data: data.rain,
                  backgroundColor: "#6FC98A",
                  yAxisID: "y1",
                  borderRadius: 4,
                  maxBarThickness: 18,
                },
              ],
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              interaction: { mode: "index", intersect: false },
              plugins: {
                legend: {
                  position: "top",
                  labels: {
                    font: { family: "'Inter',sans-serif" },
                    boxWidth: 12,
                  },
                },
              },
              scales: {
                y: {
                  position: "left",
                  grid: { color: "#EDF2EE" },
                  title: { display: true, text: "°C" },
                },
                y1: {
                  position: "right",
                  grid: { display: false },
                  title: { display: true, text: "mm" },
                },
                x: { grid: { display: false } },
              },
            },
          });
          const avgT = (
            data.temps.reduce((a, b) => a + b, 0) / data.temps.length
          ).toFixed(1);
          const totalRain = data.rain.reduce((a, b) => a + b, 0).toFixed(0);
          const avgH = Math.round(
            data.humidity.reduce((a, b) => a + b, 0) / data.humidity.length,
          );
          const maxWind = Math.max(...data.wind).toFixed(0);
          $("#histStats").innerHTML = `
    <div class="s"><div class="k">Avg temperature</div><div class="v">${avgT}°C</div></div>
    <div class="s"><div class="k">Total rainfall</div><div class="v">${totalRain} mm</div></div>
    <div class="s"><div class="k">Avg humidity</div><div class="v">${avgH}%</div></div>
    <div class="s"><div class="k">Peak wind</div><div class="v">${maxWind} km/h</div></div>
  `;
        }

        /* ---------------------------- AI ASSISTANT ---------------------------- */
        function botAnswer(q) {
          const s = q.toLowerCase();
          const plant = analyzePlanting(CURRENT, DAILY);
          const irrigate = analyzeIrrigation(CURRENT, DAILY);
          const spray = analyzeSpray(CURRENT, HOURLY);
          const harvest = analyzeHarvest(DAILY);
          const rainTom = DAILY[1];

          if (/plant/.test(s)) {
            return `For <b>${loc().name}</b>: ${plant.label}. ${plant.reasons.map((r) => r.text).join("; ")}. 
            ${/tomorrow/.test(s) ? (DAILY[1].rainMm > 15 ? "Tomorrow specifically looks wet — I'd wait a day or two." : "Tomorrow's conditions look workable if today's don't fit.") : ""}`;
          }
          if (/spray/.test(s)) {
            const lvl =
              spray.level === "g"
                ? "good"
                : spray.level === "a"
                  ? "workable in windows"
                  : "poor";
            return `Spraying conditions for ${State.activeCrop} 
            are currently <b>${lvl}</b>. ${spray.windowText} Avoid spraying if
             wind exceeds 18 km/h or rain is likely within 4 hours — it just washes off.`;
          }
          if (/irrigat|water/.test(s)) {
            return `${irrigate.label}. ${irrigate.detail}`;
          }
          if (/rain.*(tomorrow|today)|will it rain/.test(s)) {
            const target = /tomorrow/.test(s) ? DAILY[1] : DAILY[0];
            return `${/tomorrow/.test(s) ? "Tomorrow" : "Today"}
             in ${loc().name}: ${target.rainPct}% chance of rain, roughly 
             ${target.rainMm} mm expected, conditions trending 
             ${target.cond.label.toLowerCase()}.`;
          }
          if (/harvest/.test(s)) {
            return `${harvest.label}. ${harvest.detail}`;
          }
          if (/danger|risk|cocoa|maize|tomato/.test(s)) {
            const cropKey =
              Object.keys(CROP_NOTES).find((c) =>
                s.includes(c.toLowerCase()),
              ) || State.activeCrop;
            const info = CROP_NOTES[cropKey];
            return `For <b>${cropKey}</b>, 
            the main weather-linked risk right now is <b>${info.risk}</b>. 
            ${info.note}`;
          }
          if (/wind/.test(s)) {
            return `Current wind is ${kmhDisplay(CURRENT.windKmh)}
             from the ${windDir(CURRENT.windDeg)}. $
             {CURRENT.windKmh > 22 ? "That's strong enough to affect spray drift and staked crops." 
             : "That's within a safe range for most fieldwork."}`;
          }
          if (/humid/.test(s)) {
            return `Humidity is at ${CURRENT.humidity}%. ${CURRENT.humidity > 78 ? "That's high enough to raise fungal disease pressure — keep watch on leaves." : "That's a moderate, manageable level."}`;
          }
          return `Here's what I have for ${loc().name} right now: ${tempDisplay(CURRENT.tempC)}, ${CURRENT.cond.label.toLowerCase()}, ${CURRENT.humidity}% humidity, ${CURRENT.precipPct}% rain chance. Ask me about planting, spraying, irrigation, harvesting, or a specific crop for tailored advice.`;
        }

        function addMsg(role, html) {
          const wrap = $("#chatBody");
          const div = document.createElement("div");
          div.className = "msg " + role;
          div.innerHTML = html;
          wrap.appendChild(div);
          wrap.scrollTop = wrap.scrollHeight;
        }
        function sendChat(text) {
          if (!text.trim()) return;
          addMsg("user", escapeHtml(text));
          $("#chatInput").value = "";
          const typing = document.createElement("div");
          typing.className = "msg bot";
          typing.innerHTML = `<div class="typing"><span></span><span></span><span></span></div>`;
          $("#chatBody").appendChild(typing);
          $("#chatBody").scrollTop = $("#chatBody").scrollHeight;
          setTimeout(
            () => {
              typing.remove();
              addMsg("bot", botAnswer(text));
            },
            500 + Math.random() * 400,
          );
        }
        function escapeHtml(s) {
          return s.replace(
            /[&<>"]/g,
            (c) =>
              ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" })[c],
          );
        }

        /* ---------------------------- NOTIFICATIONS / TOAST ---------------------------- */
        function toast(msg, icon = "✅") {
          const wrap = $("#toastWrap");
          const t = document.createElement("div");
          t.className = "toast";
          t.innerHTML = `<span>${icon}</span><span>${msg}</span>`;
          wrap.appendChild(t);
          setTimeout(() => {
            t.style.opacity = "0";
            t.style.transform = "translateX(20px)";
            t.style.transition = "all .3s";
            setTimeout(() => t.remove(), 300);
          }, 3200);
        }

        /* ---------------------------- NAV ---------------------------- */
        const VIEWS = [
          "dashboard",
          "forecast",
          "farm",
          "advice",
          "map",
          "assistant",
          "alerts",
          "history",
          "calendar",
          "settings",
        ];
        function showView(name) {
          VIEWS.forEach((v) => {
            $("#view-" + v)?.classList.toggle("active", v === name);
          });
          $$(".nav-item").forEach((n) =>
            n.classList.toggle("active", n.dataset.view === name),
          );
          $$(".bn-item").forEach((n) =>
            n.classList.toggle("active", n.dataset.view === name),
          );
          if (name === "map") renderMap();
          if (name === "history") renderHistory();
          window.scrollTo({ top: 0, behavior: "smooth" });
        }

        /* ---------------------------- INIT ---------------------------- */
        function refreshAll() {
          refreshData();
          renderLocationUI();
          renderHorizon();
          renderHero();
          renderHourly();
          renderDaily();
          renderAdvice();
          renderAlerts();
          renderCalendar();
          renderMapLayerButtons();
        }

        function initEvents() {
          $$(".nav-item, .bn-item").forEach((n) =>
            n.addEventListener("click", () => showView(n.dataset.view)),
          );
          $("#locBtn").addEventListener("click", () =>
            $("#locPanel").classList.toggle("open"),
          );
          document.addEventListener("click", (e) => {
            if (!e.target.closest(".location-picker")) closeLocPanel();
          });
          $("#locSearch").addEventListener("input", (e) => {
            const q = e.target.value.toLowerCase();
            $$(".loc-list-item").forEach((el, i) => {
              el.style.display =
                State.locations[i].name.toLowerCase().includes(q) ||
                State.locations[i].region.toLowerCase().includes(q)
                  ? "flex"
                  : "none";
            });
          });
          $("#useGpsBtn").addEventListener("click", () => {
            toast("Using approximate location", "📍");
            closeLocPanel();
          });
          $("#unitToggle").addEventListener("click", () => {
            State.useMetric = !State.useMetric;
            $("#unitToggle").textContent = State.useMetric ? "°C" : "°F";
            renderHero();
            renderHourly();
            renderDaily();
          });
          $("#notifBtn").addEventListener("click", () => {
            showView("alerts");
          });

          $("#chatForm").addEventListener("submit", (e) => {
            e.preventDefault();
            sendChat($("#chatInput").value);
          });
          $$(".suggest-chip").forEach((c) =>
            c.addEventListener("click", () => sendChat(c.textContent)),
          );

          $$(".hist-tab").forEach((t) =>
            t.addEventListener("click", () => {
              $$(".hist-tab").forEach((x) => x.classList.remove("active"));
              t.classList.add("active");
              activeRange = t.dataset.range;
              renderHistory();
            }),
          );

          $$(".settings .toggle").forEach((tg) =>
            tg.addEventListener("click", () => {
              tg.classList.toggle("on");
              toast(
                (tg.classList.contains("on") ? "Enabled " : "Disabled ") +
                  tg.dataset.label,
              );
            }),
          );

          $("#saveApiKey").addEventListener("click", () => {
            const v = $("#apiKeyInput").value.trim();
            toast(
              v ? "Key saved for this session" : "Add a key to go live",
              v ? "🔑" : "ℹ️",
            );
          });
        }

        document.addEventListener("DOMContentLoaded", () => {
          initEvents();
          refreshAll();
          showView("dashboard");
          addMsg(
            "bot",
            `Hi, I'm <b>AgriWeather AI</b> 🌾 — ask me things like <i>"Can I plant maize tomorrow?"</i> or <i>"Should I spray today?"</i> using live conditions for <b>${loc().name}</b>.`,
          );
        });
      })();
   
