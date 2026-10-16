# Workify Design System Specification

Dokumen ini mendefinisikan sistem desain antarmuka (*design system*) berbasis referensi visual landing page SaaS B2B "Workify". Sistem dirancang modular, modern, dan scalable dengan pendekatan implementasi **mobile-first**.

---

## 1. Design Direction & Brand Personality

* **Arah Desain:** Modern B2B Enterprise SaaS, High-trust, Clean, Elevated Minimalist.
* **Karakteristik Visual:**
  * Kontras tajam antara latar belakang terang (*clean white/neutral-50*) dengan aksen *floating elements* gelap atau berwarna kontras.
  * Nuansa *soft dimensional depth* menggunakan bayangan berlapis (*ambient blur & directional glow*), bukan batas garis tebal yang kaku.
  * Perpaduan elemen *tactile* (tombol oranye hangat dengan *inner bevel/glow*) dan aksen hijau alami pada latar belakang preview produk untuk menciptakan kesan segar dan ramah pengguna (*approachable enterprise*).
  * Struktur tipografi *editorial display* yang tegas dipadu dengan sans-serif utilitarian yang mudah dibaca di berbagai resolusi.

---

## 2. Color Palette & Token

### 2.1 Primary Brand (Warm Orange Accent)
Digunakan untuk Call-To-Action (CTA) utama, highlight brand, dan titik fokus visual.
* `color-primary-50`: `#FFF5ED`
* `color-primary-100`: `#FFE6D3`
* `color-primary-500`: `#F55D2C` *(Aksen utama tombol "Try It Now" & logo mark)*
* `color-primary-600`: `#DF4B1C` *(Hover state CTA)*
* `color-primary-700`: `#B8350F` *(Active state)*

### 2.2 Secondary & Accents
* `color-accent-blue-500`: `#2F80ED` *(Badge "Human Resource", icon Excel)*
* `color-accent-green-500`: `#10B981` *(Indikator status positif, tren kepuasan karyawan)*
* `color-accent-amber-500`: `#F59E0B` *(Rating stars & warning badges)*

### 2.3 Neutral Palette (Surfaces & Text)
* `color-neutral-0`: `#FFFFFF` (Surface utama card & mockups)
* `color-neutral-50`: `#F8F9FA` (Background body utama)
* `color-neutral-100`: `#F1F3F5` (Subtle dividers & table headers)
* `color-neutral-200`: `#E5E7EB` (Border default)
* `color-neutral-400`: `#9CA3AF` (Logo partner grayscale, disabled placeholder)
* `color-neutral-600`: `#6B7280` (Body text sekunder & subtitle)
* `color-neutral-800`: `#1F2937` (Subheadings & nav pill background)
* `color-neutral-900`: `#111827` (Heading primer & deep contrast blocks)
* `color-neutral-950`: `#0B0F17` (Dark card section & island navigation)

---

## 3. Typography System

* **Primary Font Family:** Inter, Plus Jakarta Sans, atau SF Pro Display (sans-serif sistem modern).
* **Font Smoothing:** Subpixel Antialiased / `-webkit-font-smoothing: antialiased`.

| Token / Role | Mobile Size / Line Height | Desktop Size / Line Height | Weight | Tracking |
| :--- | :--- | :--- | :--- | :--- |
| `text-display` (Hero Headline) | 36px / 44px | 64px / 72px | 700 (Bold) | -0.025em |
| `text-h1` (Section Title) | 28px / 36px | 44px / 52px | 700 (Bold) | -0.02em |
| `text-h2` (Card / Feature Title) | 22px / 28px | 28px / 36px | 600 (SemiBold) | -0.015em |
| `text-h3` (Sub-feature Title) | 18px / 24px | 20px / 28px | 600 (SemiBold) | -0.01em |
| `text-body-lead` | 16px / 24px | 18px / 28px | 400 (Regular) | 0 |
| `text-body` | 14px / 22px | 15px / 24px | 400 (Regular) | 0 |
| `text-caption` / `text-meta` | 12px / 16px | 13px / 18px | 500 (Medium) | +0.01em |
| `text-badge` | 11px / 14px | 11px / 14px | 700 (Bold) | +0.05em (Uppercase) |

---

## 4. Spacing, Radius & Shadows

### 4.1 Spacing Scale (8pt Grid System)
* `space-1`: `4px`
* `space-2`: `8px`
* `space-3`: `12px`
* `space-4`: `16px` (Default mobile gutter)
* `space-6`: `24px` (Tablet padding)
* `space-8`: `32px` (Card padding desktop)
* `space-12`: `48px`
* `space-16`: `64px` (Section gap mobile)
* `space-24`: `96px` (Section gap desktop)

### 4.2 Border Radius
* `radius-sm`: `6px` (Tag, status dot wrapper)
* `radius-md`: `10px` (Input field, table row container)
* `radius-lg`: `16px` (Internal dashboard widgets & floating cards)
* `radius-xl`: `24px` (Container showcase mockup & dark feature panels)
* `radius-pill`: `9999px` (Button CTA, floating nav bar, pill badges)

### 4.3 Elevation & Shadows
* `shadow-sm`: `0 1px 2px rgba(0, 0, 0, 0.05)` (Subtle card border substitute)
* `shadow-md`: `0 8px 16px -4px rgba(0, 0, 0, 0.08), 0 2px 4px -1px rgba(0, 0, 0, 0.04)` (Card default)
* `shadow-floating`: `0 20px 40px -8px rgba(17, 24, 39, 0.12), 0 1px 3px rgba(0, 0, 0, 0.06)` (Floating overlay metric cards)
* `shadow-glow-orange`: `0 10px 25px -4px rgba(245, 93, 44, 0.45)` (Hero CTA button glow)
* `shadow-glow-blue`: `0 8px 20px -2px rgba(47, 128, 237, 0.35)` (Feature tag glow)

---

## 5. UI Component Specs

### 5.1 Navigation Bar
* **Visual Style:** Floating island bar berwarna hitam gelap (`#0B0F17`), rounded pill (`radius-pill`), semi-translucent (`backdrop-filter: blur(12px)`).
* **Komposisi Desktop:**
  * Kiri: Brand icon oranye + text "Workify".
  * Tengah: Menu link teks putih muted (`#9CA3AF`), hover putih (`#FFFFFF`).
  * Kanan: Pill button sekunder "CONTACT" (putih flat dengan teks hitam).
* **Perilaku Responsif:**
  * **Mobile:** Menjadi sticky top header (lebar 100% minus `16px` margin di kiri/kanan), logo di kiri, hamburger drawer button di kanan. Tombol "CONTACT" dipindahkan ke dalam drawer menu untuk menghemat horizontal space.
  * **Tablet:** Floating island bar terpusat (`max-width: 90%`), menu navigasi disederhanakan (beberapa item masuk menu overflow jika sempit).
  * **Desktop:** Floating island bar terpusat (`max-width: 960px` hingga `1120px`), margin atas `24px`.

### 5.2 Buttons
* **Primary CTA ("Try It Now"):**
  * Latar oranye (`#F55D2C`), teks putih, font weight 600, `radius-pill`.
  * Padding: `14px 32px` (desktop), `12px 24px` (mobile).
  * Memiliki `shadow-glow-orange` khas di bawah tombol untuk memberikan efek melayang (*elevated*).
* **Secondary Light CTA ("Contact", "Add Employee"):**
  * Latar putih bersih (`#FFFFFF`), teks gelap (`#111827`), padding `10px 20px`, `radius-pill`, subtil border `1px solid #E5E7EB`.
* **Dark Badge Pill ("OUR CORE VALUES", "CORE FEATURE"):**
  * Latar hitam transparan (`rgba(17, 24, 39, 0.9)`), border tipis abu-abu, ikon bulat kecil di kiri, teks kapital 11px putih.

### 5.3 Form & Input Fields
*(Asumsi: Berdasarkan search bar pada dashboard internal mockup dan modal input)*
* **Search Input (Dashboard / Header):**
  * Height: `40px` (desktop), `44px` (mobile touch target).
  * Background: `#F8F9FA`, border: `1px solid #E5E7EB`, radius: `radius-md`.
  * Leading icon search, placeholder warna `#9CA3AF`.
  * Focus state: Border berubah ke `#2F80ED` dengan ring tipis `rgba(47, 128, 237, 0.15)`.

### 5.4 Cards & Panels
* **Showcase Main Container:**
  * Panel berbingkai besar yang membungkus software dashboard, sudut melengkung `radius-xl`, drop shadow halus, background preview gradien alam di bagian bawah.
* **Floating Metric Widget (e.g., "Total Payroll", "Employee Satisfaction"):**
  * Latar belakang putih solid `#FFFFFF`, sudut `radius-lg`, padding `20px`, bayangan `shadow-floating`.
  * Berisi micro-typography: Label metrik kecil, nilai angka besar bold (`$84,302.00`), dan badge persentase hijau pill (`+12.4%`).
* **Feature Grid Card (Bottom Right Panels):**
  * Card putih minimalis dengan border tipis `1px solid #E5E7EB`, padding `24px`.
  * Heading card diikuti checklist atau progress bar compact.

### 5.5 Table *(Asumsi: Ekstrapolasi dari preview "SaaS Data Integrity" & employee list)*
* **Header:** Tinggi `36px`, font size 12px uppercase, background `#F8F9FA`, warna font `#6B7280`, tanpa border vertikal.
* **Row:** Tinggi minimal `48px`, border-bottom `1px solid #F1F3F5`, font size 13px/14px.
* **Status Cell:** Pill badge kecil (hijau untuk "Active/Healthy", oranye untuk "Pending").
* **Perilaku Responsif:**
  * **Desktop & Tablet:** Tampilan tabular lengkap dengan kolom fleksibel.
  * **Mobile:** Transformasi dari tabel horizontal menjadi *stacked cards* atau izinkan *horizontal scrolling container* dengan sticky column pertama untuk menjaga keterbacaan data numerik.

### 5.6 Modal / Dialog *(Asumsi: Diadaptasi dari sistem card floating & form)*
* **Backdrop:** `rgba(11, 15, 23, 0.6)` dengan `backdrop-filter: blur(8px)`.
* **Modal Dialog Body:**
  * Background `#FFFFFF`, `radius-xl`, padding `24px` hingga `32px`, max-width `540px`.
  * Header dilengkapi tombol tutup (X) bundar di kanan atas.
  * Primary button full-width di mobile, right-aligned di desktop.
  * Responsif: Pada layar mobile (< 640px), modal bertransformasi menjadi **Bottom Sheet Drawer** dengan handle geser di bagian atas.

---

## 6. Layout & Grid Strategy

* **Base Grid:** 12-kolom pada Desktop, 8-kolom pada Tablet, 4-kolom pada Mobile.
* **Breakpoints:**
  * **Mobile (`base`):** `< 640px` (Container padding horizontal `16px`)
  * **Tablet (`md`):** `640px - 1024px` (Container padding horizontal `32px`)
  * **Desktop (`lg` / `xl`):** `> 1024px` (Max container width `1200px` - `1280px`, auto-centered)

---

## 7. Responsive Behavior Breakdown

| Area / Komponen | Mobile (< 640px) | Tablet (640px - 1024px) | Desktop (> 1024px) |
| :--- | :--- | :--- | :--- |
| **Navigation** | Sticky pill bar; logo + hamburger icon; menu membuka modal drawer fullscreen. | Floating pill terpusat; link menu esensial ditampilkan; tombol CTA compact. | Floating island bar lengkap (`margin-top: 24px`); semua link terlihat; tombol "Contact" penuh. |
| **Hero Title** | 36px font size; text wrap 3-4 baris; ikon Excel bergeser inline tanpa merusak spasi. | 48px font size; text terpusat dengan padding kanan-kiri yang lega. | 64px font size; heading terpusat 2 baris tegas; aksen ikon inline proporsional. |
| **Hero CTA** | Tombol "Try It Now" full-width atau lebar minimal 80% dengan padding sentuh jari yang nyaman. | Tombol auto-width terpusat; shadow glow dipertahankan. | Tombol auto-width terpusat dengan padding generous (`14px 36px`). |
| **Product Showcase Mockup** | Tampilan mockup diskalakan atau dibungkus dalam horizontal swipe preview; floating cards ditumpuk vertikal (*stacked*) di bawah mockup utama untuk mencegah elemen bertumpuk tak terbaca. | Floating cards diperkecil skalanya (`scale(0.85)`), tetap berada di posisi semi-overlay di tepi kiri-kanan. | Mockup ukuran penuh dengan 2 floating card independen melayang di kiri ("Total Payroll") dan kanan ("Satisfaction"). |
| **Logo Cloud (Social Proof)** | Grid 2 atau 3 kolom berulang, atau horizontal infinite marquee carousel otomatis. | Grid 3 atau 6 kolom dengan opacity 60%. | Single horizontal row (6-7 logo) sejajar, logo grayscale monokrom dengan hover state 100% opacity. |
| **Feature Split Section** | Stacked vertikal 1 kolom: Judul fitur di atas, teks penjelasan di tengah, dan ilustrasi mockup di bawah. | Grid 2 kolom seimbang; atau stacked dengan ilustrasi diperbesar. | Asymmetric 2-column layout (misal: 45% teks deskripsi & timeline node, 55% visual dashboard interactif). |
| **Interactive Metric Cards** | Single column card list, full touchable area. | 2-column grid. | Multi-column grid dengan absolute floating layer. |