# Hi-tech-savvy

Business website for HI TECH SAVVY'S Engineering.

## Run locally

Install PHP 8.1 or newer, then from the repository root run:

```powershell
php -S localhost:8080 -t app/frontend/public
```

Open <http://localhost:8080> in a browser. The contact form posts to `contact.php` and stores each accepted submission in `app/data/contact-submissions.jsonl`.

The homepage quote buttons open `quote.html`, a dedicated quote-request page connected to the PHP backend.

## Quote email delivery

Each accepted quote sends two emails: a new-request notification to `info@hitechsavvys.co.za` and a confirmation to the customer. PHP's `mail()` function must be connected to an SMTP service before email delivery will work. Configure the SMTP host, port, username, password and sender address in `php.ini` or your production hosting panel; do not commit credentials to this repository.

The quote flow also supports a confirmation SMS through Twilio. Configure these environment variables on the server: `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN` and `TWILIO_FROM_NUMBER`. The customer's South African number is converted from `0...` to `+27...`; other international numbers should be entered in international format. Never commit these credentials.

The `app/data` directory is created automatically on the first successful submission. Keep it outside the public directory and restrict access to it in production.

## Local firewall

The PHP server binds to localhost and should not be exposed to the network. To add a Windows Firewall rule allowing only local access to port 8080, open PowerShell as Administrator and run:

```powershell
.\firewall\allow-localhost-8080.ps1
```

The script replaces the existing rule with the same name and allows only `127.0.0.1` and `::1`.
