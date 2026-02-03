/* ==========================
     Payment + QR logic (your original)
  ========================== */
let selectedPayment = null;

const qrMap = {
  aba: "../assets/qr/aba.jpg",
  wing: "../assets/qr/wing.jpg",
  bakong: "../assets/qr/bakong.jpg",
  acleda: "../assets/qr/acleda.jpg",
  chipmong: "../assets/qr/chipmong.jpg",
};

const $ = (id) => document.getElementById(id);

function selectPayment(event, method) {
  selectedPayment = method;
  $("paymentMethod").value = method;

  document.querySelectorAll(".payment-card").forEach((card) => {
    card.classList.remove("selected", "border-blue-500");
    card.classList.add("border-gray-200");
  });

  const card = event.currentTarget;
  card.classList.add("selected", "border-blue-500");
  card.classList.remove("border-gray-200");
}

function showQRModal(method) {
  const src = qrMap[method];
  if (!src) return alert("QR image not found for: " + method);

  $("qrImage").src = src;
  const modal = $("qrModal");
  modal.classList.remove("hidden");
  modal.classList.add("flex");
}

function closeQRModal() {
  const modal = $("qrModal");
  modal.classList.add("hidden");
  modal.classList.remove("flex");
}

function showReloadOverlay() {
  const overlay = $("reloadOverlay");
  if (!overlay) return;
  overlay.classList.remove("hidden");
  overlay.classList.add("flex");
}

function processOrder(event) {
  const form = $("checkoutForm");

  if (!form.checkValidity()) {
    form.reportValidity();
    return false;
  }
  if (!selectedPayment) {
    alert("Please select payment method");
    return false;
  }

  event.preventDefault();
  showQRModal(selectedPayment);
  return false;
}

function confirmPaidAndSubmit(ev) {
  const confirmInput = $("confirmPaid");
  if (confirmInput) confirmInput.value = "1";

  const btn = $("btnCompletePayment");
  if (btn) {
    btn.disabled = true;
    btn.classList.add("opacity-60", "cursor-not-allowed");
    btn.innerHTML = `<i class="fas fa-circle-notch animate-spin mr-2"></i> Processing...`;
  }

  closeQRModal();
  showReloadOverlay();

  setTimeout(() => {
    $("checkoutForm").submit();
  }, 250);
}

document.addEventListener("DOMContentLoaded", () => {
  const first = document.querySelector(".payment-card[data-method]");
  if (first) first.click();
});

window.addEventListener("beforeunload", () => {
  showReloadOverlay();
});

/* ==========================
           ✅ Real-time Location (GPS -> Address autofill)
           Uses local proxy: /E-commerce-shoes/ajax/reverse_geocode.php
        ========================== */
const loc = {
  btn: null,
  status: null,
  lat: null,
  lng: null,
  address: null,
  city: null,
  country: null,
};

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

function setLocBtnLoading(isLoading) {
  if (!loc.btn) return;
  loc.btn.disabled = isLoading;
  loc.btn.classList.toggle("opacity-60", isLoading);
  loc.btn.classList.toggle("cursor-not-allowed", isLoading);
  loc.btn.innerHTML = isLoading
    ? `<i class="fas fa-circle-notch animate-spin mr-2"></i> Locating...`
    : `<i class="fas fa-location-crosshairs mr-2"></i> Use Location`;
}

function getGPS(
  options = {
    enableHighAccuracy: true,
    timeout: 12000,
    maximumAge: 0,
  },
) {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject(new Error("Geolocation not supported in this browser."));
      return;
    }
    navigator.geolocation.getCurrentPosition(resolve, reject, options);
  });
}

async function reverseGeocode(lat, lng) {
  const url = `/E-commerce-shoes/ajax/reverse_geocode.php?lat=${encodeURIComponent(
    lat,
  )}&lon=${encodeURIComponent(lng)}`;

  const res = await fetch(url, {
    method: "GET",
    headers: {
      Accept: "application/json",
    },
    credentials: "same-origin",
  });

  if (!res.ok) throw new Error("Reverse geocode failed.");
  return await res.json();
}

/* ✅ Cambodia FIX:
           - City input = province/capital (state)
           - Address = road + commune/sangkat + district/khan
        */
function fillFromNominatim(data) {
  const a = data?.address || {};

  const country = a.country || "";

  // Province / Capital (Best for Cambodia)
  const province = a.state || a.province || a.region || a.state_district || "";

  // District / Khan
  const district =
    a.county || a.city_district || a.municipality || a.town || "";

  // Commune / Sangkat / Village
  const commune =
    a.suburb || a.neighbourhood || a.village || a.hamlet || a.locality || "";

  // Street / Road
  const road = a.road || a.residential || "";
  const house = a.house_number || "";

  const line1 = [house, road].filter(Boolean).join(" ").trim();
  const line2 = [commune, district].filter(Boolean).join(", ").trim();

  let finalAddress = [line1, line2].filter(Boolean).join(", ").trim();
  let city = province;

  // fallback if province empty
  if (!city) {
    city =
      a.city ||
      a.town ||
      a.village ||
      a.municipality ||
      a.county ||
      a.city_district ||
      "";
  }

  // fallback from display_name
  if ((!finalAddress || !city) && data?.display_name) {
    const parts = String(data.display_name)
      .split(",")
      .map((x) => x.trim())
      .filter(Boolean);

    if (!finalAddress) finalAddress = data.display_name;

    if (!city && parts.length >= 2) {
      city = parts[parts.length - 2] || "";
      if (
        city.toLowerCase() === (country || "").toLowerCase() &&
        parts.length >= 3
      ) {
        city = parts[parts.length - 3] || "";
      }
    }
  }

  // Only overwrite if empty (so user can edit)
  if (loc.address && finalAddress && !loc.address.value.trim())
    loc.address.value = finalAddress;

  if (loc.city && city && !loc.city.value.trim()) loc.city.value = city;

  if (loc.country && country && !loc.country.value.trim())
    loc.country.value = country;
}

async function useMyLocationOnce() {
  try {
    setLocBtnLoading(true);
    setLocStatus("Requesting GPS permission…");

    const pos = await getGPS();
    const lat = pos.coords.latitude;
    const lng = pos.coords.longitude;

    if (loc.lat) loc.lat.value = String(lat);
    if (loc.lng) loc.lng.value = String(lng);

    setLocStatus("GPS found. Fetching address…");

    const data = await reverseGeocode(lat, lng);
    fillFromNominatim(data);

    // show warning if city still empty
    if (loc.city && !loc.city.value.trim()) {
      setLocStatus(
        "Location found ✅ but province/city not detected. Please type manually.",
        "error",
      );
    } else {
      setLocStatus(
        "Location filled successfully ✅ (You can edit the fields)",
        "success",
      );
    }
  } catch (err) {
    const code = err?.code;
    if (code === 1) {
      setLocStatus(
        "Permission denied. Please allow location access in your browser.",
        "error",
      );
    } else if (code === 2) {
      setLocStatus(
        "Position unavailable. Try again or turn on GPS / Wi-Fi.",
        "error",
      );
    } else if (code === 3) {
      setLocStatus("Timeout getting location. Try again.", "error");
    } else {
      setLocStatus(err?.message || "Could not get your location.", "error");
    }
  } finally {
    setLocBtnLoading(false);
  }
}

document.addEventListener("DOMContentLoaded", () => {
  loc.btn = document.getElementById("btnUseLocation");
  loc.status = document.getElementById("locStatus");
  loc.lat = document.getElementById("lat");
  loc.lng = document.getElementById("lng");
  loc.address = document.getElementById("address");
  loc.city = document.getElementById("city");
  loc.country = document.getElementById("country");

  if (loc.btn) loc.btn.addEventListener("click", useMyLocationOnce);

  if (loc.lat?.value && loc.lng?.value) {
    setLocStatus("GPS saved for this order ✅", "success");
  }
});
