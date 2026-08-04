/**
 * POS receipt printing for the Money Receipt screen.
 *
 * Listens for the `mr-print-receipt` window event (dispatched by the
 * Livewire component after a successful save, and by the manual
 * "Print Receipt" button). Routing:
 *
 *  - Mobile / tablet ........ ESC/POS over Web Bluetooth (BLE POS printer).
 *                             Falls back to the system print dialog when
 *                             Bluetooth is unavailable or fails.
 *  - Desktop / laptop ....... System print dialog with an 80 mm thermal
 *                             layout (prints to the default/selected POS
 *                             printer).
 *
 * The paired Bluetooth printer is remembered (localStorage + the browser's
 * Web Bluetooth permission), so after the first pairing receipts print
 * without any prompt. The Web Bluetooth device chooser requires a user
 * gesture, so first-time pairing only happens from the manual button
 * (`manual: true` on the event detail).
 */

const BT_DEVICE_KEY = 'mr_bt_printer_id';

/** Known write endpoints used by common BLE ESC/POS printers. */
const BT_ENDPOINTS = [
    { service: 0x18f0, characteristic: 0x2af1 },
    { service: 0xffe0, characteristic: 0xffe1 },
    { service: 0xfff0, characteristic: 0xfff2 },
    { service: 'e7810a71-73ae-499d-8c15-faa9aef0c3f2', characteristic: 'bef8d6c9-9c21-4c9e-b632-bd58c1009f9f' },
];

/** Characters per line on a 58 mm printer (also fine on 80 mm). */
const LINE_WIDTH = 32;

let cachedDevice = null;

window.addEventListener('mr-print-receipt', (event) => {
    const receipt = event.detail?.receipt;
    if (! receipt || ! receipt.mrn) return;

    handlePrint(receipt, event.detail?.manual === true);
});

async function handlePrint(receipt, manual) {
    if (isMobileDevice() && navigator.bluetooth) {
        try {
            await printViaBluetooth(receipt, manual);
            notify('Receipt sent to Bluetooth printer.');

            return;
        } catch (error) {
            // User dismissed the printer chooser — treat as a cancel, not an error.
            if (manual && error?.name === 'NotFoundError') return;

            console.warn('[receipt-printer] Bluetooth print failed, using system print dialog.', error);
        }
    }

    printViaDialog(receipt);
}

function isMobileDevice() {
    const ua = navigator.userAgent || '';

    if (/android|iphone|ipad|ipod|windows phone|mobile|tablet/i.test(ua)) return true;

    // iPadOS Safari reports itself as Macintosh but has multi-touch.
    return /macintosh/i.test(ua) && navigator.maxTouchPoints > 1;
}

function notify(message) {
    try {
        window.Flux?.toast?.(message);
    } catch {
        /* toast is optional */
    }
}

/* ── Bluetooth (ESC/POS) ─────────────────────────────────────────────── */

async function printViaBluetooth(receipt, allowChooser) {
    const device = await resolvePrinterDevice(allowChooser);

    const server = await device.gatt.connect();

    try {
        const characteristic = await findWritableCharacteristic(server);
        await writeInChunks(characteristic, buildEscPos(receipt));
    } finally {
        try {
            device.gatt.disconnect();
        } catch {
            /* already disconnected */
        }
    }
}

async function resolvePrinterDevice(allowChooser) {
    if (cachedDevice?.gatt) return cachedDevice;

    // Previously permitted printer (no prompt needed).
    const savedId = localStorage.getItem(BT_DEVICE_KEY);
    if (savedId && navigator.bluetooth.getDevices) {
        try {
            const devices = await navigator.bluetooth.getDevices();
            const known = devices.find((d) => d.id === savedId);
            if (known) return (cachedDevice = known);
        } catch {
            /* fall through to the chooser */
        }
    }

    if (! allowChooser) {
        throw new DOMException('No paired Bluetooth printer. Use the Print Receipt button to pair one.', 'NotAllowedError');
    }

    const device = await navigator.bluetooth.requestDevice({
        acceptAllDevices: true,
        optionalServices: BT_ENDPOINTS.map((e) => e.service),
    });

    localStorage.setItem(BT_DEVICE_KEY, device.id);

    return (cachedDevice = device);
}

async function findWritableCharacteristic(server) {
    for (const endpoint of BT_ENDPOINTS) {
        try {
            const service = await server.getPrimaryService(endpoint.service);
            const characteristic = await service.getCharacteristic(endpoint.characteristic);
            if (characteristic.properties.write || characteristic.properties.writeWithoutResponse) {
                return characteristic;
            }
        } catch {
            /* try the next known endpoint */
        }
    }

    throw new Error('No writable ESC/POS characteristic found on this printer.');
}

async function writeInChunks(characteristic, bytes) {
    const CHUNK = 96;

    for (let i = 0; i < bytes.length; i += CHUNK) {
        const chunk = bytes.slice(i, i + CHUNK);

        if (characteristic.properties.writeWithoutResponse) {
            await characteristic.writeValueWithoutResponse(chunk);
        } else {
            await characteristic.writeValue(chunk);
        }

        // Give the printer's small buffer time to drain.
        await new Promise((resolve) => setTimeout(resolve, 30));
    }
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
