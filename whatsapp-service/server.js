import express from 'express';
import cors from 'cors';
import pino from 'pino';
import QRCode from 'qrcode';
import makeWASocket, {
    DisconnectReason,
    useMultiFileAuthState,
    fetchLatestBaileysVersion
} from '@whiskeysockets/baileys';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = process.env.PORT || 3000;
const SESSION_DIR = path.join(__dirname, 'session');

app.use(cors());
app.use(express.json());

// Logger minimal agar console tetap bersih
const logger = pino({ level: 'silent' });

let sock = null;
let qrCodeDataUrl = null;
let isConnected = false;
let connectedUser = null;
let isConnecting = false;

async function startWhatsApp() {
    if (isConnecting) return;
    isConnecting = true;

    try {
        if (!fs.existsSync(SESSION_DIR)) {
            fs.mkdirSync(SESSION_DIR, { recursive: true });
        }

        const { state, saveCreds } = await useMultiFileAuthState(SESSION_DIR);
        const { version } = await fetchLatestBaileysVersion();

        console.log(`[*] Memulai WhatsApp Baileys Gateway (Versi: ${version.join('.')})...`);

        sock = makeWASocket({
            version,
            logger,
            printQRInTerminal: true,
            auth: state,
            browser: ['SMAN 2 Tondano', 'Chrome', '1.0.0'],
            syncFullHistory: false
        });

        sock.ev.on('creds.update', saveCreds);

        sock.ev.on('connection.update', async (update) => {
            const { connection, lastDisconnect, qr } = update;

            if (qr) {
                try {
                    qrCodeDataUrl = await QRCode.toDataURL(qr, { margin: 2, scale: 6 });
                    console.log('[+] QR Code baru digenerate! Siap di-scan di halaman web atau terminal.');
                } catch (e) {
                    console.error('[!] Gagal generate QR Code:', e);
                }
            }

            if (connection === 'close') {
                isConnected = false;
                connectedUser = null;
                const statusCode = lastDisconnect?.error?.output?.statusCode;
                const shouldReconnect = statusCode !== DisconnectReason.loggedOut;

                console.log(`[!] Koneksi WhatsApp terputus. Status: ${statusCode}. Sambung ulang: ${shouldReconnect}`);

                if (shouldReconnect) {
                    setTimeout(() => {
                        isConnecting = false;
                        startWhatsApp();
                    }, 3000);
                } else {
                    console.log('[!] Perangkat keluar (Logged Out). Menghapus sesi lama...');
                    try {
                        fs.rmSync(SESSION_DIR, { recursive: true, force: true });
                    } catch (err) {}
                    qrCodeDataUrl = null;
                    setTimeout(() => {
                        isConnecting = false;
                        startWhatsApp();
                    }, 3000);
                }
            } else if (connection === 'open') {
                isConnected = true;
                qrCodeDataUrl = null;
                isConnecting = false;
                const userJid = sock.user?.id || '';
                connectedUser = userJid.split(':')[0] || userJid.split('@')[0];
                console.log(`[✓] WhatsApp Baileys BERHASIL TERHUBUNG! Nomor pengirim: +${connectedUser}`);
            }
        });

    } catch (err) {
        console.error('[!] Error saat inisialisasi Baileys:', err);
        isConnecting = false;
        setTimeout(startWhatsApp, 5000);
    }
}

// Endpoint Cek Status Gateway & QR Code
app.get('/status', (req, res) => {
    res.json({
        online: true,
        connected: isConnected,
        user: connectedUser ? `+${connectedUser}` : null,
        qr: isConnected ? null : qrCodeDataUrl,
        message: isConnected
            ? `WhatsApp Terhubung aktif (+${connectedUser})`
            : (qrCodeDataUrl ? 'Menunggu scan QR Code dari aplikasi WhatsApp' : 'Sedang menghubungkan ke server WhatsApp...')
    });
});

// Endpoint Kirim Pesan WhatsApp
app.post('/send-message', async (req, res) => {
    try {
        const { number, phone, message } = req.body;
        const targetRaw = number || phone;

        if (!targetRaw || !message) {
            return res.status(400).json({
                status: 'error',
                pesan: "Parameter 'number' dan 'message' wajib diisi."
            });
        }

        if (!isConnected || !sock) {
            return res.status(503).json({
                status: 'error',
                pesan: 'WhatsApp belum terhubung! Silakan scan QR Code terlebih dahulu di menu Pengaturan Notifikasi.'
            });
        }

        // Normalisasi nomor target
        let cleanNumber = String(targetRaw).replace(/[^0-9]/g, '');
        if (cleanNumber.startsWith('0')) {
            cleanNumber = '62' + cleanNumber.substring(1);
        } else if (cleanNumber.startsWith('8')) {
            cleanNumber = '62' + cleanNumber;
        }

        const jid = `${cleanNumber}@s.whatsapp.net`;

        // Kirim pesan teks
        const sent = await sock.sendMessage(jid, { text: String(message) });

        console.log(`[+] Pesan WhatsApp terkirim ke: +${cleanNumber}`);
        return res.json({
            status: 'sukses',
            pesan: `Pesan berhasil dikirim ke +${cleanNumber}`,
            id: sent?.key?.id
        });

    } catch (err) {
        console.error('[!] Gagal mengirim pesan WA:', err);
        return res.status(500).json({
            status: 'error',
            pesan: `Gagal mengirim pesan: ${err.message}`
        });
    }
});

// Endpoint Reset / Reconnect
app.post('/restart', async (req, res) => {
    console.log('[*] Permintaan restart gateway diterima.');
    try {
        if (sock) {
            await sock.logout().catch(() => {});
        }
    } catch (e) {}
    try {
        fs.rmSync(SESSION_DIR, { recursive: true, force: true });
    } catch (e) {}
    isConnected = false;
    connectedUser = null;
    qrCodeDataUrl = null;
    isConnecting = false;
    startWhatsApp();
    res.json({ status: 'sukses', pesan: 'Gateway WhatsApp direstart.' });
});

// Jalankan HTTP Server
app.listen(PORT, '0.0.0.0', () => {
    console.log('====================================================');
    console.log(`  WhatsApp Baileys Gateway Server SMAN 2 Tondano    `);
    console.log(`  Berjalan di http://127.0.0.1:${PORT}             `);
    console.log('====================================================');
    startWhatsApp();
});

