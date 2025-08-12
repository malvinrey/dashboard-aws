# Implementasi Overlay Loading untuk Graph Analysis

## Tujuan

Membuat overlay loading yang menutupi seluruh halaman hingga semua komponen benar-benar terload, mencegah user mengklik elemen yang belum siap.

## Masalah yang Diatasi

1. **User Experience Buruk**: User bisa mengklik filter/button yang belum terload
2. **Error Interaksi**: Klik pada elemen yang belum siap bisa menyebabkan error
3. **Loading State Tidak Jelas**: User tidak tahu kapan semua komponen sudah siap

## Solusi: Overlay Loading Komprehensif

### 1. **Overlay Loading Utama**

**HTML Structure:**

```html
<div id="main-loading-overlay" class="main-loading-overlay">
    <div class="loading-content">
        <div class="spinner-large"></div>
        <p class="loading-text">Loading Graph Analysis...</p>
        <p class="loading-subtext">
            Please wait while all components are being initialized
        </p>
    </div>
</div>
```

**CSS Features:**

-   `position: fixed` - Menutupi seluruh viewport
-   `z-index: 99999` - Selalu di atas semua elemen
-   `backdrop-filter: blur(5px)` - Efek blur pada background
-   `transition: opacity 0.3s ease-out` - Animasi fade out yang smooth

### 2. **Sequential Component Loading**

**Algoritma Loading:**

```javascript
async loadComponentsSequentially() {
    // Step 1: Setup SSE connection (500ms delay)
    // Step 2: Start connection checker (300ms delay)
    // Step 3: Wait for Select2 to be ready
    // Step 4: Wait for interval buttons to be ready
    // Step 5: Dispatch loadChartData event (200ms delay)
    // Step 6: Final delay (500ms delay)
    // Hide overlay
}
```

**Keunggulan:**

-   Loading berurutan untuk memastikan dependencies
-   Delay yang cukup untuk setiap komponen
-   Error handling yang robust

### 3. **Component Readiness Detection**

**Select2 Detection:**

```javascript
waitForSelect2() {
    return new Promise((resolve) => {
        const checkSelect2 = () => {
            if (typeof $ !== 'undefined' && $('#metrics-select2').length > 0) {
                resolve();
            } else {
                setTimeout(checkSelect2, 100);
            }
        };
        checkSelect2();
    });
}
```

**Interval Buttons Detection:**

```javascript
waitForIntervalButtons() {
    return new Promise((resolve) => {
        const checkButtons = () => {
            const buttons = document.getElementById('interval-buttons');
            if (buttons && buttons.querySelectorAll('button').length > 0) {
                resolve();
            } else {
                setTimeout(checkButtons, 100);
            }
        };
        checkButtons();
    });
}
```

## Fitur Utama

### 1. **Visual Design**

-   **Spinner Besar**: 60px dengan animasi rotate
-   **Typography**: Font weight dan size yang jelas
-   **Color Scheme**: Blue accent dengan white background
-   **Responsive**: Adaptif untuk mobile dan desktop

### 2. **User Experience**

-   **Blocking**: User tidak bisa scroll atau klik apapun
-   **Informative**: Pesan yang jelas tentang apa yang sedang terjadi
-   **Smooth**: Animasi fade in/out yang tidak mengganggu
-   **Fallback**: Timeout 10 detik untuk mencegah stuck

### 3. **Technical Features**

-   **Body Lock**: `overflow: hidden` dan `position: fixed`
-   **Event Prevention**: `pointer-events: none` saat hidden
-   **Memory Cleanup**: Overlay di-remove dari DOM setelah animasi
-   **Error Handling**: Overlay tetap hilang meskipun ada error

## Implementasi Detail

### 1. **CSS Classes**

```css
.main-loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(5px);
    z-index: 99999;
}

.main-loading-overlay.hidden {
    opacity: 0;
    pointer-events: none;
}

body.loading {
    overflow: hidden;
    position: fixed;
    width: 100%;
}
```

### 2. **JavaScript Functions**

```javascript
// Utility function untuk delay
delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// Hide overlay dengan animasi
hideMainLoadingOverlay() {
    const overlay = document.getElementById('main-loading-overlay');
    if (overlay) {
        overlay.classList.add('hidden');
        document.body.classList.remove('loading');

        setTimeout(() => {
            if (overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
        }, 300);
    }
}

// Fallback untuk mencegah stuck
ensureOverlayHidden() {
    setTimeout(() => {
        if (document.getElementById('main-loading-overlay')) {
            this.hideMainLoadingOverlay();
        }
    }, 10000);
}
```

## Flow Loading

### 1. **Initial State**

```
User membuka halaman → Overlay muncul → Body locked
```

### 2. **Component Loading**

```
SSE Setup → Connection Checker → Select2 Ready → Buttons Ready → Chart Data → Final Delay
```

### 3. **Completion**

```
Overlay fade out → Body unlocked → User bisa interaksi
```

## Responsive Design

### 1. **Desktop (>768px)**

-   Spinner: 60px
-   Padding: 2rem
-   Font sizes: 1.25rem, 0.875rem

### 2. **Mobile (≤768px)**

-   Spinner: 50px
-   Padding: 1.5rem
-   Font sizes: 1.1rem, 0.8rem
-   Margin: 1rem

## Error Handling

### 1. **Try-Catch Block**

```javascript
try {
    // Sequential loading
} catch (error) {
    console.error("Error during component loading:", error);
    this.hideMainLoadingOverlay(); // Tetap hide overlay
}
```

### 2. **Fallback Timeout**

```javascript
// Force hide setelah 10 detik
setTimeout(() => {
    if (document.getElementById("main-loading-overlay")) {
        this.hideMainLoadingOverlay();
    }
}, 10000);
```

### 3. **Component Validation**

```javascript
// Pastikan semua komponen ada sebelum melanjutkan
if (!buttons || buttons.querySelectorAll("button").length === 0) {
    setTimeout(checkButtons, 100);
}
```

## Testing dan Verifikasi

### 1. **Test Case: Normal Loading**

-   ✅ Overlay muncul saat halaman dibuka
-   ✅ User tidak bisa scroll atau klik
-   ✅ Semua komponen terload berurutan
-   ✅ Overlay hilang dengan animasi smooth

### 2. **Test Case: Slow Network**

-   ✅ Overlay tetap muncul selama loading
-   ✅ Fallback timeout berfungsi
-   ✅ Error handling robust

### 3. **Test Case: Mobile Device**

-   ✅ Responsive design berfungsi
-   ✅ Touch events diblokir
-   ✅ Layout tidak rusak

## Maintenance dan Troubleshooting

### 1. **Common Issues**

-   **Overlay stuck**: Cek console untuk error, gunakan fallback timeout
-   **Component tidak terload**: Verifikasi dependencies dan event listeners
-   **Performance issue**: Monitor loading time dan optimize delays

### 2. **Debugging Steps**

1. Buka browser console
2. Monitor sequential loading logs
3. Cek component readiness
4. Verifikasi overlay state

### 3. **Performance Optimization**

-   Adjust delay values berdasarkan performance
-   Monitor component loading time
-   Optimize Select2 dan button initialization

## Kesimpulan

Implementasi overlay loading ini memberikan:

1. **User Experience yang Baik**: Loading state yang jelas dan tidak mengganggu
2. **Technical Robustness**: Error handling dan fallback yang komprehensif
3. **Responsive Design**: Adaptif untuk berbagai device dan screen size
4. **Maintainability**: Code yang terstruktur dan mudah di-debug

Solusi ini memastikan user tidak bisa berinteraksi dengan elemen yang belum siap, memberikan pengalaman yang smooth dan profesional.
