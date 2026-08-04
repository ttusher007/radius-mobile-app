/**
 * POS receipt printing for the Money Receipt screen.
 *
 * Window events consumed:
 *   mr-print-receipt  { receipt, manual }  print a receipt
 *   mr-pair-printer   -                    open the Bluetooth chooser (needs a tap)
 *   mr-test-print     -                    print a sample receipt
 *
 * Window event emitted:
 *   mr-print-status   { type: info|success|error, message }
 *
 * Print methods (localStorage `mr_print_method`):
 *   auto      Bluetooth on mobile/tablet, system print dialog on desktop (default)
 *   bluetooth ESC/POS over Web Bluetooth (BLE printers only)
 *   rawbt     Hand ESC/POS to the RawBT Android app (Bluetooth *Classic* printers)
 *   dialog    System print dialog (desktop POS printers)
 *
 * Web Bluetooth notes that shape this file:
 *   - It only exists in a secure context (HTTPS), so an http:// deployment has
 *     no `navigator.bluetooth` at all.
 *   - Pairing the printer in Android's Bluetooth settings does NOT grant the
 *     page access; the in-page chooser must be used once per device.
 *   - `getDevices()` (silent re-access to an already-permitted printer) is
 *     still behind a Chrome flag, so the connection is kept alive for the
 *     session rather than relying on it.
 */

const KEY_METHOD = 'mr_print_method';
const KEY_DEVICE = 'mr_bt_printer_id';

/**
 * Services worth asking for on a BLE printer. `requestDevice` only grants
 * access to services named here, so the list has to be generous — the actual
 * writable characteristic is then discovered at runtime rather than assumed.
 */
const PRINTER_SERVICES = [
    0x18f0, // common ESC/POS printer service
    0xff00,
    0xffe0,
    0xffe5,
    0xfff0,
    0xff80,
    0xffb0,
    0xae30,
    '49535343-fe7d-4ae5-8fa9-9fafd205e455', // ISSC / Microchip transparent UART
    '6e400001-b5a3-f393-e0a9-e50e24dcca9e', // Nordic UART
    'e7810a71-73ae-499d-8c15-faa9aef0c3f2',
    '0000fee7-0000-1000-8000-00805f9b34fb',
];

/** Characters per line on a 58 mm printer (also fine on 80 mm). */
const LINE_WIDTH = 32;

/** Live connection, reused across receipts so printing stays instant. */
let session = { device: null, characteristic: null };

let printing = false;

window.addEventListener('mr-print-receipt', (event) => {
    const receipt = event.detail?.receipt;
    if (! receipt || ! receipt.mrn) return;

    print(receipt, event.detail?.manual === true);
});

window.addEventListener('mr-pair-printer', () => pairPrinter());
window.addEventListener('mr-test-print', (event) => print(sampleReceipt(event.detail?.company), true));

/* ── Status reporting ────────────────────────────────────────────────── */

function status(type, message) {
    window.dispatchEvent(new CustomEvent('mr-print-status', { detail: { type, message } }));
}

/* ── Routing ─────────────────────────────────────────────────────────── */

function printMethod() {
    return localStorage.getItem(KEY_METHOD) || 'auto';
}

function isMobileDevice() {
    const ua = navigator.userAgent || '';

    if (/android|iphone|ipad|ipod|windows phone|mobile|tablet/i.test(ua)) return true;

    // iPadOS Safari reports itself as Macintosh but has multi-touch.
    return /macintosh/i.test(ua) && navigator.maxTouchPoints > 1;
}

async function print(receipt, manual) {
    if (printing) return;

    const method = printMethod();

    if (method === 'dialog') return printViaDialog(receipt);
    if (method === 'rawbt') return printViaRawBt(receipt);

    const wantsBluetooth = method === 'bluetooth' || (method === 'auto' && isMobileDevice());

    if (! wantsBluetooth) return printViaDialog(receipt);

    if (! navigator.bluetooth) {
        // On a phone the system print dialog cannot reach a POS printer, so
        // say what is wrong instead of opening a dialog that leads nowhere.
        if (isMobileDevice()) {
            status('error', window.isSecureContext
                ? 'This browser has no Bluetooth support. Use Chrome on Android, or switch the print method to RawBT.'
                : 'Bluetooth printing requires HTTPS. Open the app over https:// or switch the print method.');

            return;
        }

        return printViaDialog(receipt);
    }

    printing = true;
    status('info', 'Sending to printer…');

    try {
        await printViaBluetooth(receipt, manual);
        status('success', 'Receipt sent to the printer.');
    } catch (error) {
        handleBluetoothError(error, manual);
    } finally {
        printing = false;
    }
}

function handleBluetoothError(error, manual) {
    // Drop the connection but keep the device handle: re-pairing costs the
    // user a tap, and `getDevices()` may not be there to recover it silently.
    session.characteristic = null;

    if (error?.name === 'NoWritableCharacteristic') {
        session = { device: null, characteristic: null };
        localStorage.removeItem(KEY_DEVICE);
    }

    // The user closed the chooser — not a failure worth shouting about.
    if (manual && error?.name === 'NotFoundError') {
        status('info', 'Printer selection cancelled.');

        return;
    }

    console.warn('[receipt-printer] Bluetooth print failed.', error);

    if (error?.name === 'NeedsPairing') {
        status('error', 'No printer paired yet. Tap "Pair printer" and choose your printer.');

        return;
    }

    if (error?.name === 'NotFoundError') {
        status('error', 'Printer not found. Make sure it is switched on and in range, then pair it again.');

        return;
    }

    if (error?.name === 'NoWritableCharacteristic') {
        status('error', 'That device is not a supported BLE printer. If it is a Bluetooth Classic printer, switch the print method to RawBT.');

        return;
    }

    if (error?.name === 'NetworkError' || error?.name === 'NotSupportedError') {
        status('error', 'Could not connect to the printer. Close any other app using it, switch it off and on, then try again.');

        return;
    }

    if (error?.name === 'SecurityError') {
        status('error', 'Bluetooth is blocked for this page. Check Chrome\'s site permissions and that the app is served over HTTPS.');

        return;
    }

    status('error', `Printing failed: ${error?.message || error}`);
}

/* ── Bluetooth (ESC/POS over BLE) ────────────────────────────────────── */

async function pairPrinter() {
    if (! navigator.bluetooth) {
        status('error', window.isSecureContext
            ? 'This browser has no Web Bluetooth support. Use Chrome on Android.'
            : 'Bluetooth printing requires HTTPS. Open the app over https://.');

        return;
    }

    try {
        status('info', 'Select your printer…');

        const device = await navigator.bluetooth.requestDevice({
            acceptAllDevices: true,
            optionalServices: PRINTER_SERVICES,
        });

        localStorage.setItem(KEY_DEVICE, device.id);
        session = { device, characteristic: null };

        // Connect straight away so an unsupported printer is caught here,
        // during setup, rather than in the middle of a customer's payment.
        await connect();

        status('success', `Paired with ${device.name || 'printer'}. Try a test print.`);
    } catch (error) {
        handleBluetoothError(error, true);
    }
}

async function printViaBluetooth(receipt, allowChooser) {
    const bytes = buildEscPos(receipt);

    try {
        await writeToPrinter(bytes, allowChooser);
    } catch (error) {
        // A stale connection (printer slept, drifted out of range) surfaces as
        // a network error — reconnect to the same device and try once more.
        if (error?.name === 'NetworkError' && session.device) {
            session.characteristic = null;
            await delay(500);
            await writeToPrinter(bytes, allowChooser);

            return;
        }

        throw error;
    }
}

async function writeToPrinter(bytes, allowChooser) {
    const characteristic = await connect(allowChooser);

    await writeInChunks(characteristic, bytes);

    // Let the printer's buffer drain before the connection can drop.
    await delay(400);
}

/** Resolve a connected, writable characteristic — reusing the live one. */
async function connect(allowChooser = false) {
    if (session.characteristic && session.device?.gatt?.connected) {
        return session.characteristic;
    }

    const device = session.device ?? await resolveDevice(allowChooser);

    // The first GATT connect after the printer wakes up often fails outright.
    let server;
    for (let attempt = 1; ; attempt++) {
        try {
            server = await device.gatt.connect();
            break;
        } catch (error) {
            if (attempt >= 3) throw error;
            await delay(600);
        }
    }

    const characteristic = await findWritableCharacteristic(server);

    device.addEventListener('gattserverdisconnected', () => {
        session.characteristic = null;
    }, { once: true });

    session = { device, characteristic };

    return characteristic;
}

async function resolveDevice(allowChooser) {
    // Silent re-access to an already-permitted printer. Only works when
    // chrome://flags/#enable-web-bluetooth-new-permissions-backend is on.
    const savedId = localStorage.getItem(KEY_DEVICE);
    if (savedId && navigator.bluetooth.getDevices) {
        try {
            const devices = await navigator.bluetooth.getDevices();
            const known = devices.find((d) => d.id === savedId);
            if (known) return known;
        } catch {
            /* fall through */
        }
    }

    if (! allowChooser) {
        throw new DOMException('No paired printer available without a tap.', 'NeedsPairing');
    }

    const device = await navigator.bluetooth.requestDevice({
        acceptAllDevices: true,
        optionalServices: PRINTER_SERVICES,
    });

    localStorage.setItem(KEY_DEVICE, device.id);

    return device;
}

/**
 * Printers vary wildly in which service/characteristic carries ESC/POS data,
 * so discover it instead of hard-coding: walk every granted service and take
 * the first characteristic that accepts writes.
 */
async function findWritableCharacteristic(server) {
    let services = [];

    try {
        services = await server.getPrimaryServices();
    } catch {
        /* some stacks refuse bulk discovery — probed individually below */
    }

    if (services.length === 0) {
        for (const uuid of PRINTER_SERVICES) {
            try {
                services.push(await server.getPrimaryService(uuid));
            } catch {
                /* not present on this printer */
            }
        }
    }

    for (const service of services) {
        let characteristics = [];

        try {
            characteristics = await service.getCharacteristics();
        } catch {
            continue;
        }

        // Prefer write-without-response: it is what printer firmware expects.
        const writable = characteristics.find((c) => c.properties.writeWithoutResponse)
            ?? characteristics.find((c) => c.properties.write);

        if (writable) return writable;
    }

    throw new DOMException('No writable characteristic found.', 'NoWritableCharacteristic');
}

async function writeInChunks(characteristic, bytes) {
    // BLE's default ATT MTU allows only 20 payload bytes; anything larger is
    // rejected or silently truncated on write-without-response. Chrome does a
    // long-write for write-with-response, so that path can use bigger chunks.
    const withoutResponse = characteristic.properties.writeWithoutResponse;
    const size = withoutResponse ? 20 : 100;

    for (let i = 0; i < bytes.length; i += size) {
        const chunk = bytes.slice(i, i + size);

        if (withoutResponse && characteristic.writeValueWithoutResponse) {
            await characteristic.writeValueWithoutResponse(chunk);
            await delay(20); // unacknowledged writes need pacing
        } else if (characteristic.writeValueWithResponse) {
            await characteristic.writeValueWithResponse(chunk);
        } else {
            await characteristic.writeValue(chunk); // older Chrome
            await delay(20);
        }
    }
}

function delay(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

/* ── RawBT (Bluetooth Classic printers on Android) ───────────────────── */

function printViaRawBt(receipt) {
    const bytes = buildEscPos(receipt);
    let binary = '';
    for (const byte of bytes) binary += String.fromCharCode(byte);

    status('info', 'Handing the receipt to RawBT…');

    window.location.href = 'rawbt:base64,' + btoa(binary);
}

/* ── ESC/POS byte generation ─────────────────────────────────────────── */

function buildEscPos(r) {
    const out = [];
    const push = (...codes) => out.push(...codes);
    const text = (line = '') => {
        for (const wrapped of wrap(line)) {
            for (const code of asciiBytes(wrapped)) push(code);
            push(0x0a);
        }
    };

    const align = (n) => push(0x1b, 0x61, n); // 0 left, 1 center
    const bold = (on) => push(0x1b, 0x45, on ? 1 : 0);
    const doubleSize = (on) => push(0x1d, 0x21, on ? 0x11 : 0x00);

    push(0x1b, 0x40); // initialize

    align(1);
    bold(true);
    doubleSize(true);
    // Double-width text fits half as many characters per line.
    for (const line of wrap(r.company, LINE_WIDTH / 2)) text(line);
    doubleSize(false);
    bold(false);
    text('Money Receipt');

    align(0);
    text(divider());
    kv(text, 'Receipt #', r.mrn);
    kv(text, 'Date', r.date);
    text(divider());
    kv(text, 'Customer', r.customer_name);
    kv(text, 'Cust. ID', `${r.customer_id} (${r.username})`);
    if (r.package) kv(text, 'Package', r.package);
    if (r.contact) kv(text, 'Contact', r.contact);
    if (r.address) kv(text, 'Address', r.address);
    text(divider());
    text(moneyRow('Previous Due', r.previous_due));
    bold(true);
    text(moneyRow('Paid Amount', r.amount));
    bold(false);
    text(moneyRow('Current Due', r.new_due));
    if (r.recharge_months > 0) kv(text, 'Recharged', `${r.recharge_months} month(s)`);
    text(divider());
    if (r.ledger) kv(text, 'Ledger', r.ledger);
    kv(text, 'Received By', r.received_by);
    text(divider());

    align(1);
    text('Thank you for your payment.');
    text('Computer generated receipt.');

    push(0x0a, 0x0a, 0x0a, 0x0a); // feed clear of the tear bar
    push(0x1d, 0x56, 0x42, 0x00); // partial cut (ignored by cutter-less printers)

    return new Uint8Array(out);
}

function kv(text, label, value) {
    const labelCol = `${label}`.padEnd(11).slice(0, 11) + ': ';
    const valueWidth = LINE_WIDTH - labelCol.length;
    const parts = wrap(String(value ?? ''), valueWidth);

    text(labelCol + (parts[0] ?? ''));
    for (const part of parts.slice(1)) {
        text(' '.repeat(labelCol.length) + part);
    }
}

function moneyRow(label, amount) {
    const value = formatMoney(amount);

    return label.padEnd(Math.max(1, LINE_WIDTH - value.length)) + value;
}

function divider() {
    return '-'.repeat(LINE_WIDTH);
}

function formatMoney(amount) {
    const n = Number(amount ?? 0);

    return 'Tk ' + n.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

/** Thermal printers only speak ASCII reliably — degrade everything else. */
function sanitizeAscii(value) {
    return String(value ?? '')
        .replace(/৳/g, 'Tk ')
        .replace(/[‘’]/g, "'")
        .replace(/[“”]/g, '"')
        .replace(/[–—]/g, '-')
        .replace(/[^\x20-\x7e]/g, '?');
}

function asciiBytes(value) {
    return Array.from(sanitizeAscii(value), (ch) => ch.charCodeAt(0));
}

function wrap(value, width = LINE_WIDTH) {
    const words = sanitizeAscii(value).split(/\s+/).filter(Boolean);
    if (words.length === 0) return [''];

    const lines = [];
    let current = '';

    for (let word of words) {
        while (word.length > width) {
            if (current) {
                lines.push(current);
                current = '';
            }
            lines.push(word.slice(0, width));
            word = word.slice(width);
        }

        if (! current) {
            current = word;
        } else if (current.length + 1 + word.length <= width) {
            current += ' ' + word;
        } else {
            lines.push(current);
            current = word;
        }
    }

    if (current) lines.push(current);

    return lines;
}

function sampleReceipt(company) {
    return {
        company: company || 'Test Print',
        mrn: 'TEST-0000000001',
        date: new Date().toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }),
        customer_id: 0,
        customer_name: 'Test Customer',
        username: 'test',
        contact: '01700000000',
        address: 'Test address line for wrapping check',
        package: 'Test Package',
        previous_due: 1000,
        amount: 1000,
        new_due: 0,
        ledger: 'Cash',
        received_by: 'Test',
        recharge_months: 1,
    };
}

/* ── System print dialog (desktop POS / fallback) ────────────────────── */

function printViaDialog(receipt) {
    const iframe = document.createElement('iframe');
    iframe.setAttribute('aria-hidden', 'true');
    iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden;';
    document.body.appendChild(iframe);

    const doc = iframe.contentDocument;
    doc.open();
    doc.write(receiptHtml(receipt));
    doc.close();

    const cleanup = () => iframe.remove();

    iframe.contentWindow.addEventListener('afterprint', () => setTimeout(cleanup, 250));
    setTimeout(cleanup, 60_000); // safety net if afterprint never fires

    setTimeout(() => {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    }, 150);
}

function receiptHtml(r) {
    const esc = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    const metaRow = (label, value) => value
        ? `<tr><td class="l">${esc(label)}</td><td class="v">${esc(value)}</td></tr>`
        : '';

    const moneyLine = (label, amount, strong = false) =>
        `<tr${strong ? ' class="strong"' : ''}><td>${esc(label)}</td><td class="r">${esc(formatMoney(amount))}</td></tr>`;

    return `<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Money Receipt ${esc(r.mrn)}</title>
<style>
    @page { size: 80mm auto; margin: 0; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        width: 72mm;
        margin: 0 auto;
        padding: 4mm 0 8mm;
        font-family: 'Courier New', Courier, monospace;
        font-size: 12px;
        line-height: 1.45;
        color: #000;
        background: #fff;
    }
    .center { text-align: center; }
    .company { font-size: 16px; font-weight: 700; }
    .subtitle { font-size: 12px; margin-bottom: 4px; }
    .rule { border: 0; border-top: 1px dashed #000; margin: 5px 0; }
    table { width: 100%; border-collapse: collapse; }
    td { vertical-align: top; padding: 1px 0; }
    td.l { width: 34%; }
    td.v { width: 66%; word-break: break-word; }
    td.r { text-align: right; white-space: nowrap; }
    tr.strong td { font-weight: 700; font-size: 13px; }
    .footer { margin-top: 6px; font-size: 11px; }
</style>
</head>
<body>
    <div class="center company">${esc(r.company)}</div>
    <div class="center subtitle">Money Receipt</div>
    <hr class="rule">
    <table>
        ${metaRow('Receipt #', r.mrn)}
        ${metaRow('Date', r.date)}
    </table>
    <hr class="rule">
    <table>
        ${metaRow('Customer', r.customer_name)}
        ${metaRow('Cust. ID', `${r.customer_id} (${r.username})`)}
        ${metaRow('Package', r.package)}
        ${metaRow('Contact', r.contact)}
        ${metaRow('Address', r.address)}
    </table>
    <hr class="rule">
    <table>
        ${moneyLine('Previous Due', r.previous_due)}
        ${moneyLine('Paid Amount', r.amount, true)}
        ${moneyLine('Current Due', r.new_due)}
    </table>
    <hr class="rule">
    <table>
        ${metaRow('Recharged', r.recharge_months > 0 ? `${r.recharge_months} month(s)` : '')}
        ${metaRow('Ledger', r.ledger)}
        ${metaRow('Received By', r.received_by)}
    </table>
    <hr class="rule">
    <div class="center footer">
        Thank you for your payment.<br>
        Computer generated receipt.
    </div>
</body>
</html>`;
}
