# 🔔 Notification System Troubleshooting Guide

## 📋 Overview

Panduan lengkap untuk mendiagnosis dan memperbaiki masalah sistem notifikasi real-time pada Supply Purchase System.

**Tanggal:** 2024-12-11  
**Versi:** 1.0  
**Status:** ✅ COMPLETE

---

## 🚨 Problem: Tidak Ada Notifikasi Muncul di Browser

### 📊 Diagnosis Steps

#### 1. **Browser Console Check**

Buka Developer Tools (F12) dan periksa console:

```javascript
// Cek apakah semua komponen tersedia
console.log("Echo:", !!window.Echo);
console.log("Laravel User:", window.Laravel?.user);
console.log("showNotification:", typeof showNotification);
console.log("Notification Permission:", Notification.permission);
```

#### 2. **System Readiness Check**

Gunakan keyboard shortcut: **Ctrl+Shift+S** atau jalankan:

```javascript
window.SupplyPurchaseGlobal?.checkReadiness();
```

#### 3. **Manual Notification Test**

Gunakan keyboard shortcut: **Ctrl+Shift+T** atau jalankan:

```javascript
window.testBrowserNotification();
```

---

## 🛠️ Solutions

### ✅ Solution 1: Browser Permission

**Problem:** Browser notifications diblokir

**Steps:**

1. Klik ikon gembok/info di address bar
2. Pilih "Allow" untuk Notifications
3. Refresh halaman
4. Test dengan Ctrl+Shift+T

**Alternative:**

```javascript
// Request permission manually
Notification.requestPermission().then((permission) => {
    console.log("Permission:", permission);
});
```

### ✅ Solution 2: JavaScript Files Not Loaded

**Problem:** File JavaScript tidak dimuat dengan benar

**Check:**

1. Pastikan file-file ini dimuat:

    - `/assets/js/browser-notification.js`
    - `/assets/js/echo-setup.js`
    - `/assets/js/app.bundle.js`

2. Periksa di Network tab Developer Tools apakah ada file yang gagal dimuat

**Fix:**

```bash
# Rebuild assets
npm run dev
```

### ✅ Solution 3: Laravel User Not Set

**Problem:** User authentication tidak tersedia untuk private channels

**Check:**

```javascript
console.log("User:", window.Laravel?.user);
```

**Fix:** Pastikan user sudah login atau set manual:

```javascript
window.Laravel = {
    user: {
        id: 1,
        name: "Test User",
        email: "test@test.com",
    },
};
```

### ✅ Solution 4: Toastr/SweetAlert Not Available

**Problem:** Library notifikasi tidak tersedia

**Check:**

```javascript
console.log("Toastr:", typeof toastr);
console.log("SweetAlert:", typeof Swal);
```

**Fix:** Sistem akan menggunakan fallback, tapi untuk hasil terbaik pastikan library tersedia.

---

## 🧪 Testing Tools

### 1. **Simple Test Page**

Buka: `testing/simple_notification_test.php` di browser

### 2. **Backend Test Script**

```bash
# Test sistem backend
php testing/test_realtime_notification.php check

# Run notification test
php testing/test_realtime_notification.php
```

### 3. **Browser Console Commands**

```javascript
// Test basic notification
window.testBrowserNotification();

// Test Echo event
window.testEcho?.triggerSupplyPurchaseEvent();

// Test user notification
window.testEcho?.triggerUserNotification();

// System check
window.SupplyPurchaseGlobal?.checkReadiness();
```

### 4. **Keyboard Shortcuts**

-   **Ctrl+Shift+T**: Test notification
-   **Ctrl+Shift+S**: System check
-   **Ctrl+Shift+N**: Simulate notification

---

## 📱 Multiple Notification Methods

Sistem menggunakan fallback hierarchy:

1. **Toastr** (Primary) - Web app notifications
2. **SweetAlert** (Secondary) - Modal notifications
3. **Browser Notifications** (Tertiary) - OS notifications
4. **Custom HTML** (Quaternary) - Custom styled notifications
5. **Alert** (Fallback) - Basic browser alert

---

## 🔍 Common Issues & Solutions

### Issue: "Echo is not defined"

**Cause:** Laravel Echo tidak dimuat atau gagal inisialisasi

**Solution:**

1. Periksa `public/assets/js/app.bundle.js` ada
2. Rebuild assets: `npm run dev`
3. Check browser console untuk error

### Issue: "showNotification is not a function"

**Cause:** `browser-notification.js` tidak dimuat

**Solution:**

1. Pastikan file ada di `public/assets/js/browser-notification.js`
2. Check layout includes file tersebut
3. Clear browser cache

### Issue: Notifications tidak muncul tapi console log ada

**Cause:** Browser permission atau library tidak tersedia

**Solution:**

1. Check notification permission
2. Test dengan `window.testBrowserNotification()`
3. Check fallback notifications

### Issue: Real-time test script berhasil tapi browser tidak ada notifikasi

**Cause:** Event listener tidak terpasang atau Echo tidak connected

**Solution:**

1. Check Echo connection status
2. Verify broadcasting configuration
3. Test dengan mock Echo events

---

## 🎯 Complete Troubleshooting Checklist

### ✅ Browser Setup

-   [ ] Notification permission granted
-   [ ] Developer Tools open (F12)
-   [ ] Console tab visible
-   [ ] No JavaScript errors

### ✅ Files Loaded

-   [ ] `browser-notification.js` loaded
-   [ ] `echo-setup.js` loaded
-   [ ] `app.bundle.js` loaded
-   [ ] No 404 errors in Network tab

### ✅ JavaScript Objects

-   [ ] `window.Echo` exists
-   [ ] `window.Laravel.user` exists (if logged in)
-   [ ] `window.showNotification` function exists
-   [ ] `window.testEcho` object exists

### ✅ Test Functions

-   [ ] `Ctrl+Shift+T` shows notification
-   [ ] `window.testBrowserNotification()` works
-   [ ] `window.testEcho.triggerSupplyPurchaseEvent()` works
-   [ ] Browser shows notification or fallback

### ✅ Real-time Test

-   [ ] Backend test script runs: `php testing/test_realtime_notification.php`
-   [ ] Events fired successfully
-   [ ] Browser receives events (check console)
-   [ ] Notifications display

---

## 📞 Support & Additional Testing

### Quick Test Commands

```bash
# Backend system check
php testing/test_realtime_notification.php check

# Full notification test
php testing/test_realtime_notification.php

# Simple validation
php testing/test_supply_purchase_notification_simple.php
```

### Browser Test Page

Navigate to: `testing/simple_notification_test.php`

### Debug Console

```javascript
// Complete diagnostic
window.SupplyPurchaseGlobal?.checkReadiness();

// Manual notification test
showNotification("Debug Test", "Testing notification system", "info");

// Echo event simulation
window.testEcho?.triggerSupplyPurchaseEvent({
    batch_id: 999,
    invoice_number: "DEBUG-001",
    old_status: "test",
    new_status: "debug",
    metadata: { priority: "high" },
});
```

---

## 🎉 Success Indicators

### ✅ System Working Correctly

1. **Console Output:**

    ```
    ✅ Laravel Echo initialized successfully
    ✅ Laravel user info set
    ✅ Notification permission granted
    ✅ Supply Purchase Notification System loaded successfully!
    ```

2. **Test Results:**

    - Ctrl+Shift+T shows notification
    - Backend test script completes successfully
    - Browser displays notifications (any method)
    - Real-time events trigger notifications

3. **Browser Behavior:**
    - Toast/modal notifications appear
    - OR Browser notifications show
    - OR Custom HTML notifications display
    - Console logs show events received

---

**Status:** ✅ **NOTIFICATION SYSTEM READY** - Sistem dapat menampilkan notifikasi dengan berbagai metode fallback untuk memastikan user selalu menerima notifikasi.

**Last Updated:** 2024-12-11  
**Author:** AI Assistant
