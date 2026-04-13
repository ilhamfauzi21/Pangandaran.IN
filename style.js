document.addEventListener("DOMContentLoaded", () => {
  console.log("JS TERLOAD");

  /* ================= FILTER (INDEX) ================= */
  const filterMain = document.querySelector(".filter-tabs-main");
  if (filterMain) {
    const tabs = filterMain.querySelectorAll(".tab");
    const cards = document.querySelectorAll(".card");

    tabs.forEach((tab) => {
      tab.addEventListener("click", () => {
        tabs.forEach((t) => t.classList.remove("active"));
        tab.classList.add("active");

        const filter = tab.dataset.filter;
        cards.forEach((card) => {
          card.style.display =
            filter === "all" || card.classList.contains(filter) ? "block" : "none";
        });
      });
    });
  }

    /* ================= DESTINATION CARD -> SCROLL KE PAKET + FILTER ================= */
  const destCards = document.querySelectorAll(".popular-v2 .dest-card");
  const paketSection = document.getElementById("paket-scroll");
  const filterTabs = document.querySelectorAll(".filter-tabs-main .tab");
  const packageCards = document.querySelectorAll(".destination .card");

  destCards.forEach((card) => {
    card.addEventListener("click", (e) => {
      e.preventDefault();

      const targetFilter = card.dataset.filter;
      if (!paketSection || !targetFilter) return;

      paketSection.scrollIntoView({
        behavior: "smooth",
        block: "start"
      });

      setTimeout(() => {
        filterTabs.forEach((tab) => {
          tab.classList.toggle("active", tab.dataset.filter === targetFilter);
        });

        packageCards.forEach((item) => {
          item.style.display =
            targetFilter === "all" || item.classList.contains(targetFilter)
              ? "block"
              : "none";
        });
      }, 500);
    });
  });

  

  /* ================= DETAIL DINAMIS (SUPPORT 2 MODE) ================= */
  const params = new URLSearchParams(window.location.search);
  const paketParam = params.get("paket"); // bisa key ("gc") atau nama ("Water Sport")
  const hargaParam = params.get("harga"); // mode lama biasanya ada

  const titleEl = document.getElementById("namaPaket");
  const hargaEl = document.getElementById("hargaPaket");
  const metaEl = document.querySelector(".detail-meta");
  const descEl = document.querySelector(".detail-desc");

  // Helper format rupiah
  const formatRupiah = (n) => "Rp " + (Number(n) || 0).toLocaleString("id-ID");

  // Jika halaman detail ada slider (detail.html)
  const slidesEl = document.getElementById("slides");
  const thumbsEl = document.getElementById("thumbs");

  function renderGallery(images) {
    if (!slidesEl || !thumbsEl) return;

    slidesEl.innerHTML = "";
    thumbsEl.innerHTML = "";

    images.forEach((src, idx) => {
      const slide = document.createElement("div");
      slide.className = "slide";
      slide.innerHTML = `<img src="${src}" alt="gallery ${idx + 1}">`;
      slidesEl.appendChild(slide);

      const t = document.createElement("img");
      t.src = src;
      t.alt = `thumb ${idx + 1}`;
      t.addEventListener("click", () => goToSlide(idx));
      thumbsEl.appendChild(t);
    });

    goToSlide(0);
  }

  function goToSlide(index) {
    if (!slidesEl) return;
    slidesEl.style.transform = `translateX(-${index * 100}%)`;

    if (thumbsEl) {
      [...thumbsEl.querySelectorAll("img")].forEach((img, i) => {
        img.classList.toggle("active", i === index);
      });
    }
  }

  // MODE BARU: paketParam adalah key dan ada di dataPaket
if (paketParam && typeof dataPaket !== "undefined" && dataPaket[paketParam]) {
  const paket = dataPaket[paketParam];

  if (titleEl) titleEl.innerText = paket.title;
  if (metaEl) metaEl.innerText = paket.subtitle;
  if (descEl) descEl.innerText = paket.description;

  const harga = paket.pricing?.from ?? 0;
  if (hargaEl) hargaEl.innerText = formatRupiah(harga);

  // isi slider kalau ada
  const images = paket.media?.gallery?.length
    ? paket.media.gallery
    : [paket.media?.cover].filter(Boolean);

  if (images.length) renderGallery(images);

  // ===== Render Highlights =====
  const hlEl = document.getElementById("highlightsList");
  if (hlEl) {
    hlEl.innerHTML = "";
    (paket.highlights || []).forEach((txt) => {
      const div = document.createElement("div");
      div.className = "highlight-item";
      div.innerHTML = `
        <div><h3>${txt}</h3></div>
      `;
      hlEl.appendChild(div);
    });
  }

  // ===== Render Inclusions =====
  const incEl = document.getElementById("inclusionsList");
  if (incEl) {
    incEl.innerHTML = "";
    (paket.inclusions || []).forEach((group) => {
      const box = document.createElement("div");
      box.className = "inc-box";
      box.innerHTML = `
        <strong>${group.label}</strong>
        <ul>${(group.items || []).map(i => `<li>${i}</li>`).join("")}</ul>
      `;
      incEl.appendChild(box);
    });
  }

  // ===== Render Exclusions =====
  const excEl = document.getElementById("exclusionsList");
  if (excEl) {
    excEl.innerHTML = "";
    (paket.exclusions || []).forEach((txt) => {
      const li = document.createElement("li");
      li.textContent = txt;
      excEl.appendChild(li);
    });
  }

} else {
  // MODE LAMA
  if (paketParam && titleEl) titleEl.innerText = paketParam;
  if (hargaParam && hargaEl) hargaEl.innerText = formatRupiah(hargaParam);
}

  /* ================= EXPERIENCE THUMB VIDEO ================= */
  const mainVideo = document.getElementById("mainVideo");
  const thumbs = document.querySelectorAll(".exp2-thumb");

  if (mainVideo && thumbs.length) {
    thumbs.forEach((btn) => {
      const v = btn.querySelector("video");
      if (v) {
        v.muted = true;
        v.loop = true;
        v.playsInline = true;
        v.play().catch(() => {});
      }
    });

    thumbs.forEach((btn) => {
      btn.addEventListener("click", () => {
        thumbs.forEach((b) => b.classList.remove("active"));
        btn.classList.add("active");

        const src = btn.getAttribute("data-video");
        if (!src) return;

        mainVideo.pause();
        mainVideo.innerHTML = `<source src="${src}" type="video/mp4">`;
        mainVideo.load();
        mainVideo.play().catch(() => {});
      });
    });
  }

  /* ================= DETAIL -> BOOKING (TETAP MODE LAMA biar booking aman) ================= */
  const btnDetailBooking = document.getElementById("bookWA");
  if (btnDetailBooking) {
    btnDetailBooking.addEventListener("click", () => {

  const namaPaket = (document.getElementById("namaPaket")?.innerText || "").trim();
  const hargaText = (document.getElementById("hargaPaket")?.innerText || "").replace(/[^\d]/g, "");

  if (!namaPaket || !hargaText) {
    alert("Paket / harga tidak ditemukan.");
    return;
  }

  /* SIMPAN KE DATABASE */
  fetch("save_booking.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded"
    },
    body:
      "nama=guest" +
      "&tanggal=" + new Date().toISOString().split("T")[0] +
      "&peserta=1" +
      "&paket=" + encodeURIComponent(namaPaket) +
      "&total=" + encodeURIComponent(hargaText)
  });

  /* LANJUT KE HALAMAN BOOKING */
  window.location.href =
    `booking.html?paket=${encodeURIComponent(namaPaket)}&harga=${encodeURIComponent(hargaText)}`;

  });

  }
  /* ================= MOST POPULAR DESTINATION SLIDER FIX FINAL ================= */
const destinationSliders = document.querySelectorAll(".popular-v2 .dest-slider");

destinationSliders.forEach((slider) => {
  const images = Array.from(slider.querySelectorAll("img"));
  if (images.length === 0) return;

  const sources = images.map((img) => img.getAttribute("src")).filter(Boolean);
  if (sources.length === 0) return;

  images.forEach((img) => {
    img.style.display = "none";
  });

  const bg = document.createElement("div");
  bg.className = "dest-slide-bg";
  bg.style.backgroundImage = `url('${sources[0]}')`;
  slider.appendChild(bg);

  let current = 0;

  setInterval(() => {
    current = (current + 1) % sources.length;
    bg.style.opacity = "0.35";

    setTimeout(() => {
      bg.style.backgroundImage = `url('${sources[current]}')`;
      bg.style.opacity = "1";
    }, 300);
  }, 5000);
});
console.log("STYLE JS JALAN");
  
});



/* ================= GLOBAL NAV FUNCTIONS (SUPPORT 2 INPUT) ================= */
// Bisa dipanggil: goDetail('gc') atau goDetail('Water Sport', 300000)
window.goDetail = function (paketOrKey, hargaPaket) {
  // kalau harga ada -> mode lama (nama + harga)
  if (hargaPaket !== undefined && hargaPaket !== null) {
    window.location.href =
      `detail.html?paket=${encodeURIComponent(paketOrKey)}&harga=${encodeURIComponent(hargaPaket)}`;
    return;
  }

  // kalau cuma 1 argumen -> bisa key untuk mode baru
  window.location.href = `detail.html?paket=${encodeURIComponent(paketOrKey)}`;
};

// Bisa dipanggil: goBooking('Water Sport', 300000) (tetap aman)
window.goBooking = function (namaPaket, hargaPaket) {
  window.location.href =
    `booking.html?paket=${encodeURIComponent(namaPaket)}&harga=${encodeURIComponent(hargaPaket)}`;
};