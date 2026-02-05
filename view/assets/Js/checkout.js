/* ==========================
     Payment + QR logic (your original)
  ========================== */
let selectedPayment = null;

const qrMap = {
  aba: "../assets/qr/aba.jpg",
  wing: "../assets/qr/wing.jpg",
  bakong: "../assets/qr/bakong.jpg",
  acleda: "../assets/qr/acleda.jpg",
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
