/* ==========================
   Real-time Location (GPS -> Address autofill)
   Proxy: /MyBrand_Ecommerce/ajax/reverse_geocode.php?lat=..&lon=..
   - One-time mode + Real-time mode (watchPosition)
   - Throttle reverse geocode to prevent spamming
========================== */

const loc = {
  btn: null,
  stopBtn: null, // optional button
  status: null,
  lat: null,
  lng: null,
  address: null,
  city: null,
  country: null,

  watchId: null,
  lastGeocodeAt: 0,
  lastLatLngKey: "",
};

// ---------- UI helpers ----------
function setLocStatus(text, type = "info") {
  if (!loc.status) return;
  loc.status.textContent = text || "";
  loc.status.className =
    "text-xs mt-2 " +
    (type === "error"
      ? "text-red-600"
      : type === "success"
        ? "text-green-700"
        : "text-gray-500");
}

function setLocBtnLoading(isLoading, label = "Use Location") {
  if (!loc.btn) return;
  loc.btn.disabled = isLoading;
  loc.btn.classList.toggle("opacity-60", isLoading);
  loc.btn.classList.toggle("cursor-not-allowed", isLoading);
  loc.btn.innerHTML = isLoading
    ? `<i class="fas fa-circle-notch animate-spin mr-2"></i> Locating...`
    : `<i class="fas fa-location-crosshairs mr-2"></i> ${label}`;
}

function setStopBtnVisible(visible) {
  if (!loc.stopBtn) return;
  loc.stopBtn.classList.toggle("hidden", !visible);
  loc.stopBtn.disabled = !visible;
}

// ---------- security check ----------
function ensureSecureContext() {
  // Geolocation requires HTTPS (except localhost)
  const isLocalhost =
    location.hostname === "localhost" ||
    location.hostname === "127.0.0.1" ||
    location.hostname === "::1";

  if (location.protocol !== "https:" && !isLocalhost) {
    throw new Error("Geolocation requires HTTPS. Please use https:// domain.");
  }
}

// ---------- GPS ----------
function getGPSOnce(
  options = { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
) {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation)
      return reject(new Error("Geolocation not supported."));
    navigator.geolocation.getCurrentPosition(resolve, reject, options);
  });
}

function watchGPS(
  options = { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 },
  onUpdate,
  onError,
) {
  if (!navigator.geolocation) throw new Error("Geolocation not supported.");

  return navigator.geolocation.watchPosition(
    (pos) => onUpdate(pos),
    (err) => onError(err),
    options,
  );
}

// ---------- Reverse geocode ----------
async function reverseGeocode(lat, lng) {
  const url = `/MyBrand_Ecommerce/ajax/reverse_geocode.php?lat=${encodeURIComponent(
    lat,
  )}&lon=${encodeURIComponent(lng)}`;

  const res = await fetch(url, {
    method: "GET",
    headers: { Accept: "application/json" },
    credentials: "same-origin",
    cache: "no-store",
  });

  if (!res.ok)
    throw new Error("Reverse geocode failed (HTTP " + res.status + ").");
  const data = await res.json();
  if (!data) throw new Error("Reverse geocode returned empty data.");
  return data;
}

/* Cambodia FIX:
   - City input = province/capital (state / province)
   - Address = road + commune/sangkat + district/khan
*/
function fillFromNominatim(data) {
  const a = data?.address || {};
  const country = a.country || "";

  // Province/Capital
  const province = a.state || a.province || a.region || a.state_district || "";

  // District/Khan
  const district =
    a.county || a.city_district || a.municipality || a.town || a.city || "";

  // Commune/Sangkat/Village
  const commune =
    a.suburb || a.neighbourhood || a.village || a.hamlet || a.locality || "";

  // Street
  const road = a.road || a.residential || a.pedestrian || "";
  const house = a.house_number || "";

  const line1 = [house, road].filter(Boolean).join(" ").trim();
  const line2 = [commune, district].filter(Boolean).join(", ").trim();

  let finalAddress = [line1, line2].filter(Boolean).join(", ").trim();
  let city = province;

  // fallback if province empty
  if (!city) {
    city = a.city || a.town || a.village || a.municipality || a.county || "";
  }

  // fallback from display_name
  if ((!finalAddress || !city) && data?.display_name) {
    const parts = String(data.display_name)
      .split(",")
      .map((x) => x.trim())
      .filter(Boolean);

    if (!finalAddress) finalAddress = parts.slice(0, 3).join(", "); // cleaner than full string
    if (!city && parts.length >= 2) city = parts[parts.length - 2] || "";
    if (
      city &&
      country &&
      city.toLowerCase() === country.toLowerCase() &&
      parts.length >= 3
    ) {
      city = parts[parts.length - 3] || "";
    }
  }

  // Only overwrite if empty so user can edit
  if (loc.address && finalAddress && !loc.address.value.trim())
    loc.address.value = finalAddress;
  if (loc.city && city && !loc.city.value.trim()) loc.city.value = city;
  if (loc.country && country && !loc.country.value.trim())
    loc.country.value = country;
}

// ---------- Throttle geocode ----------
function shouldGeocodeNow(lat, lng) {
  const now = Date.now();

  // 1) throttle to once every 4 seconds
  if (now - loc.lastGeocodeAt < 4000) return false;

  // 2) only if moved enough (avoid tiny jitter)
  const key = `${lat.toFixed(4)},${lng.toFixed(4)}`; // ~11m precision
  if (key === loc.lastLatLngKey) return false;

  loc.lastGeocodeAt = now;
  loc.lastLatLngKey = key;
  return true;
}

// ---------- errors ----------
function handleGeoError(err) {
  const code = err?.code;
  if (code === 1) {
    setLocStatus("Permission denied. Please allow location access.", "error");
  } else if (code === 2) {
    setLocStatus(
      "Position unavailable. Turn on GPS / Wi-Fi and try again.",
      "error",
    );
  } else if (code === 3) {
    setLocStatus("Timeout getting location. Try again.", "error");
  } else {
    setLocStatus(err?.message || "Could not get your location.", "error");
  }
}

// ---------- One-time use ----------
async function useMyLocationOnce() {
  try {
    ensureSecureContext();
    setLocBtnLoading(true);
    setLocStatus("Requesting GPS permission…");

    const pos = await getGPSOnce();
    const lat = pos.coords.latitude;
    const lng = pos.coords.longitude;

    if (loc.lat) loc.lat.value = String(lat);
    if (loc.lng) loc.lng.value = String(lng);

    setLocStatus("GPS found. Fetching address…");

    const data = await reverseGeocode(lat, lng);
    fillFromNominatim(data);

    if (loc.city && !loc.city.value.trim()) {
      setLocStatus(
        "Location found but province/city not detected. Please type manually.",
        "error",
      );
    } else {
      setLocStatus(
        "Location filled successfully (you can edit the fields).",
        "success",
      );
    }
  } catch (err) {
    handleGeoError(err);
  } finally {
    setLocBtnLoading(false);
  }
}

// ---------- Real-time tracking ----------
async function startRealtimeLocation() {
  try {
    ensureSecureContext();

    if (loc.watchId !== null) return; // already watching

    setLocBtnLoading(true, "Tracking…");
    setLocStatus("Starting real-time tracking…");
    setStopBtnVisible(true);

    loc.watchId = watchGPS(
      { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 },
      async (pos) => {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;

        if (loc.lat) loc.lat.value = String(lat);
        if (loc.lng) loc.lng.value = String(lng);

        setLocStatus(
          `Live GPS: ${lat.toFixed(6)}, ${lng.toFixed(6)} (accuracy ~${Math.round(pos.coords.accuracy)}m)`,
          "info",
        );

        // throttle reverse geocode
        if (!shouldGeocodeNow(lat, lng)) return;

        try {
          setLocStatus("Updating address from GPS…", "info");
          const data = await reverseGeocode(lat, lng);
          fillFromNominatim(data);
          setLocStatus(
            "Live location updated (you can edit fields).",
            "success",
          );
        } catch (e) {
          // do not stop tracking if geocode fails
          setLocStatus(
            "GPS ok, but address lookup failed. Continue tracking…",
            "error",
          );
        }
      },
      (err) => {
        handleGeoError(err);
        stopRealtimeLocation();
      },
    );
  } catch (err) {
    handleGeoError(err);
    stopRealtimeLocation();
  } finally {
    setLocBtnLoading(false, "Use Location");
  }
}

function stopRealtimeLocation() {
  if (loc.watchId !== null && navigator.geolocation) {
    navigator.geolocation.clearWatch(loc.watchId);
  }
  loc.watchId = null;
  setStopBtnVisible(false);
  setLocStatus("Real-time tracking stopped.", "info");
}

// ---------- init ----------
document.addEventListener("DOMContentLoaded", () => {
  loc.btn = document.getElementById("btnUseLocation");
  loc.stopBtn = document.getElementById("btnStopLocation"); // optional
  loc.status = document.getElementById("locStatus");
  loc.lat = document.getElementById("lat");
  loc.lng = document.getElementById("lng");
  loc.address = document.getElementById("address");
  loc.city = document.getElementById("city");
  loc.country = document.getElementById("country");

  // If you want ONE-TIME click:
  // loc.btn?.addEventListener("click", useMyLocationOnce);

  // If you want REAL-TIME click:
  loc.btn?.addEventListener("click", startRealtimeLocation);
  loc.stopBtn?.addEventListener("click", stopRealtimeLocation);

  if (loc.lat?.value && loc.lng?.value) {
    setLocStatus("GPS saved for this order.", "success");
  }
});
