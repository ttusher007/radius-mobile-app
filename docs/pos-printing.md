# Money Receipt — POS printing setup

The Money Receipt screen can print a thermal receipt straight after a payment
is saved. The "Print Receipt" choice and the selected printer method are stored
in the browser's `localStorage`, so each device remembers its own setup.

## How printing is routed

| Print method | What it does | Use it for |
| --- | --- | --- |
| **Automatic** (default) | Bluetooth on phones/tablets, system print dialog on desktop | Most users |
| **Bluetooth POS printer** | ESC/POS over Web Bluetooth | BLE printers, also on laptops |
| **RawBT app (Android)** | Hands ESC/POS to the RawBT Android app | Bluetooth **Classic** printers |
| **System print dialog** | 80 mm HTML receipt via the browser print dialog | Desktop/counter POS printers |

Web Bluetooth talks only to **Bluetooth Low Energy (BLE)** printers. Older
58 mm pocket printers are often Bluetooth **Classic (SPP)** only — no browser
can reach those directly, which is what the RawBT method is for.

---

## Part 1 — Production server

### 1. HTTPS is mandatory

Web Bluetooth only exists in a *secure context*. Over plain `http://`,
`navigator.bluetooth` is `undefined` and Bluetooth printing can never work
(the app will now say so instead of failing silently).

- Serve the app over `https://` with a valid certificate (Let's Encrypt is fine).
- Set `APP_URL=https://your-domain` in `.env`.
- If the app sits behind a proxy/load balancer, make sure `TrustProxies` is
  configured so Laravel generates `https://` URLs.

`http://localhost` is exempt from this rule, which is why printing works in
local testing but not on an `http://` production host.

### 2. Deploy the code

```bash
cd /path/to/radius-mobile-app
git pull origin master

composer install --no-dev --optimize-autoloader
npm ci
npm run build          # required — the printer code ships in resources/js

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`npm run build` is the step that is easy to forget: without it, `public/build`
still holds the old JavaScript and nothing about printing changes.

### 3. Receipt header name

The company name printed at the top of the receipt comes from the PWA manifest
name. Set it in `.env`:

```
APP_NAME="Your ISP Name"
PWA_NAME="Your ISP Name"
```

Then re-run `php artisan config:cache`.

### 4. Verify

Open the app on a desktop browser, record a test payment with "Print Receipt"
ticked, and confirm the print dialog appears with an 80 mm receipt preview.

---

## Part 2 — Android phone

### 1. Requirements

- **Google Chrome** (or Edge). Firefox and Samsung Internet do **not** support
  Web Bluetooth.
- Android 6 or newer.
- The app opened over **HTTPS**.

### 2. Grant Bluetooth access to Chrome

1. Android **Settings → Apps → Chrome → Permissions**.
2. Allow **Nearby devices** (Android 12+) and **Location** (Android 11 and
   older — BLE scanning needs it).
3. Turn Bluetooth on. On Android 11 and older, also turn Location on.

### 3. Pair the printer with the app

Pairing the printer in Android's Bluetooth settings is **not enough** — the
browser needs its own permission for the device.

1. Switch the printer on and keep it within a metre or two.
2. Open the app → **Money Receipt**.
3. Tick **Print Receipt**. The printer settings panel appears.
4. Leave **Printer** on *Automatic* (or pick *Bluetooth POS printer*).
5. Tap **Pair printer** — Chrome shows a device chooser.
6. Pick your printer (often named `BlueTooth Printer`, `PT-210`, `MTP-II`,
   `Printer001`, etc.) and tap **Pair**.
7. Tap **Test print**. A sample receipt should come out.

### 4. Make pairing survive an app restart

The in-memory connection lasts for the browsing session. To let the app
re-connect silently after Chrome is closed and reopened, enable Chrome's
persistent-permissions flag once per phone:

1. Open `chrome://flags` in Chrome.
2. Search for **Use the new permissions backend for Web Bluetooth**.
3. Set it to **Enabled** and restart Chrome.

Without this flag, after a full restart the first receipt of the session shows
"No printer paired yet" — tap **Print Receipt** on the success screen once and
pick the printer, and the rest of the session prints automatically.

### 5. Install as an app (optional)

Chrome menu → **Add to Home screen**. The installed PWA keeps Web Bluetooth
support and its own `localStorage`, so pair the printer again inside the
installed app the first time.

---

## Part 3 — If the printer does not appear or does not print

| Symptom | Cause | Fix |
| --- | --- | --- |
| "Bluetooth printing requires HTTPS" | Site served over `http://` | Install a TLS certificate (Part 1.1) |
| "This browser has no Bluetooth support" | Firefox / Samsung Internet / iPhone | Use Chrome on Android |
| Printer missing from the chooser list | Printer is Bluetooth **Classic**, or switched off / out of range, or already connected to another app | Use the **RawBT** method below |
| "That device is not a supported BLE printer" | Picked a non-printer, or a Classic-only printer | Re-pair and pick the right device, or use RawBT |
| "Could not connect to the printer" | Another app holds the printer | Close the vendor print app, power-cycle the printer, retry |
| Prints blank or half a receipt | Paper roll inserted upside down | Flip the roll — thermal paper only prints on one side |

### RawBT — for Bluetooth Classic printers

1. Install **RawBT** from the Play Store.
2. Pair the printer normally in Android **Settings → Bluetooth**.
3. Open RawBT once and select that printer as its default.
4. In the app: **Printer → RawBT app (Android)**.
5. Tap **Test print**. Android hands the receipt to RawBT, which prints it.

### iPhone / iPad

iOS has no Web Bluetooth in any browser. Options:

- Use a Wi-Fi/LAN POS printer with the **System print dialog** method via AirPrint.
- Use an Android device for counter collections.

---

## Part 4 — Desktop / laptop POS printer

1. Install the printer's Windows driver and set the paper size to 80 mm (or 58 mm).
2. In the app leave the method on **Automatic** (or **System print dialog**).
3. Save a receipt — the print dialog opens; pick the POS printer.

To skip the dialog entirely at a counter PC, launch Chrome with kiosk printing,
which prints straight to the default printer:

```
chrome.exe --kiosk-printing --app=https://your-domain/billing/money-receipt
```
