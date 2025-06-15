# Fitur Sidebar Collapse/Expand

**Tanggal Implementasi:** {{ date('Y-m-d H:i:s') }}
**Versi:** 1.0.0
**Status:** ✅ Implemented

## 📋 Deskripsi Fitur

Fitur ini memungkinkan pengguna untuk menghide/show dan collapse/expand sidebar menu dengan transisi yang smooth. Sidebar dapat beroperasi dalam beberapa mode:

1. **Expanded Mode** - Sidebar penuh dengan icon dan text
2. **Collapsed Mode** - Sidebar hanya menampilkan icon dengan tooltip
3. **Hidden Mode** - Sidebar tersembunyi sepenuhnya, content mengambil full width

## 🎯 Fungsi Utama

### 1. Toggle Button

-   Tombol toggle berbentuk lingkaran di sisi kanan sidebar
-   Dapat diklik untuk collapse/expand sidebar
-   Icon berputar sesuai dengan state sidebar

### 2. Keyboard Shortcut

-   **Ctrl + B** untuk toggle sidebar

### 3. State Persistence

-   State sidebar disimpan di localStorage
-   Akan restore state saat reload halaman

### 4. Responsive Design

-   Pada mobile, sidebar tetap full width saat dibuka
-   Toggle button tersembunyi pada mobile

### 5. Tooltip System

-   Saat collapsed, hover pada menu item akan menampilkan tooltip
-   Tooltip muncul di sebelah kanan dengan animasi smooth

## 🏗️ Implementasi Teknis

### File yang Dimodifikasi/Dibuat:

1. **resources/views/layouts/style60/master.blade.php**

    - Menambahkan `data-kt-app-sidebar-collapsed="false"`
    - Include CSS dan JS sidebar collapse

2. **resources/views/layouts/style60/partials/sidebar-layout/\_sidebar.blade.php**

    - Menambahkan toggle button
    - Update struktur sidebar

3. **public/css/custom/sidebar-collapse.css** (Baru)

    - Styling untuk semua state sidebar
    - Transisi dan animasi
    - Responsive design
    - Tooltip styling

4. **public/js/custom/sidebar-collapse.js** (Baru)
    - Logic toggle sidebar
    - Event handlers
    - State management
    - Keyboard shortcuts

### CSS Classes dan Data Attributes:

```css
/* State Attributes */
[data-kt-app-sidebar-collapsed="true"]   /* Collapsed state */
[data-kt-app-sidebar-collapsed="false"]  /* Expanded state */
[data-kt-app-sidebar-hidden="true"]      /* Hidden state */
[data-kt-app-sidebar-fixed="true"]       /* Fixed sidebar */
[data-kt-app-sidebar-fixed="false"]      /* Full content */

/* Sidebar Classes */
.app-sidebar           /* Visible sidebar */
.drawer-end           /* Hidden sidebar (mobile) */
.drawer-on            /* Showing sidebar (mobile) */
```

## 💡 Kondisi Operasi

### Berdasarkan Requirement:

1. **`data-kt-app-sidebar-fixed="false"`**

    - Content menjadi full width
    - Sidebar tersembunyi

2. **`class="app-sidebar drawer drawer-end"`**

    - Sidebar hidden (mobile mode)

3. **`class="app-sidebar"`**

    - Sidebar visible dan normal

4. **Manual Toggle**
    - User dapat mengontrol via toggle button
    - State disimpan dan dipersist

## 🎨 UI/UX Features

### Toggle Button:

-   Posisi: Absolute di kanan sidebar
-   Style: Circular button dengan border
-   Hover effect: Background dan border color change
-   Icon rotation: 180° saat collapsed

### Collapsed Sidebar:

-   Width: 70px
-   Icons: Centered
-   Text: Hidden
-   Tooltips: Muncul saat hover

### Expanded Sidebar:

-   Width: 225px (default)
-   Full menu dengan icon dan text
-   Normal navigation

### Transitions:

-   Duration: 0.3s ease
-   Smooth animation untuk semua perubahan
-   Content area menyesuaikan secara otomatis

## 🔧 API dan Events

### JavaScript API:

```javascript
// Global instance
window.sidebarCollapse;

// Methods
sidebarCollapse.toggle(); // Toggle collapse/expand
sidebarCollapse.setCollapsed(true); // Set collapsed state
sidebarCollapse.setHidden(true); // Set hidden state
sidebarCollapse.getState(); // Get current state
```

### Custom Events:

```javascript
// Listen for sidebar state changes
document.addEventListener("sidebar-toggled", (e) => {
    console.log("Sidebar state:", e.detail);
    // e.detail.collapsed (boolean)
    // e.detail.timestamp (Date)
});
```

## 📱 Responsive Behavior

### Desktop (> 991.98px):

-   Toggle button visible
-   Collapse/expand functionality aktif
-   State persistence berfungsi

### Mobile (≤ 991.98px):

-   Toggle button hidden
-   Sidebar selalu full width saat terbuka
-   Drawer behavior untuk show/hide

## 🔍 Testing Checklist

-   [ ] Toggle button berfungsi dengan baik
-   [ ] Keyboard shortcut (Ctrl + B) bekerja
-   [ ] State persistence after reload
-   [ ] Smooth animations pada semua transisi
-   [ ] Tooltips muncul saat collapsed
-   [ ] Responsive behavior pada mobile
-   [ ] Content area menyesuaikan width
-   [ ] Icons tetap centered saat collapsed
-   [ ] Mobile drawer functionality

## 🚀 Cara Penggunaan

### Basic Usage:

1. Klik toggle button di kanan sidebar
2. Atau gunakan keyboard shortcut Ctrl + B
3. Sidebar akan collapse/expand dengan smooth animation

### Programmatic Usage:

```javascript
// Toggle sidebar
window.sidebarCollapse.toggle();

// Set specific state
window.sidebarCollapse.setCollapsed(true); // Collapse
window.sidebarCollapse.setCollapsed(false); // Expand
window.sidebarCollapse.setHidden(true); // Hide completely

// Get current state
const state = window.sidebarCollapse.getState();
console.log(state.collapsed); // true/false
console.log(state.hidden); // true/false
```

## 🐛 Troubleshooting

### Common Issues:

1. **Toggle button tidak muncul**

    - Pastikan CSS dan JS file ter-load
    - Check console untuk error

2. **State tidak persist**

    - Check localStorage permission
    - Verify browser support

3. **Animation tidak smooth**

    - Pastikan CSS transitions tidak di-override
    - Check browser compatibility

4. **Mobile behavior tidak benar**
    - Verify responsive CSS
    - Check viewport meta tag

## 📝 Future Enhancements

1. **Multi-level menu support** dalam collapsed mode
2. **Custom animation duration** settings
3. **Sidebar position** (left/right) configuration
4. **Theme-based** styling options
5. **Accessibility** improvements (ARIA attributes)

## 🔗 Dependencies

-   **CSS:** Bootstrap 5 compatible
-   **JS:** Vanilla JavaScript (ES6+)
-   **Icons:** KTIcons (Duotune)
-   **Storage:** localStorage API

---

**Author:** System Assistant
**Last Updated:** {{ date('Y-m-d H:i:s') }}
**Version:** 1.0.0
