# Wiz Monitor Client

TYPO3 v14 extension that records errors of an installation and exposes health, security and error data to a central [Wiz Monitor](https://github.com/Tenryuubito/wiz-monitor) instance.

## What it collects

| Section | Data |
|---|---|
| `environment` | TYPO3, PHP, database and OS version, application context, memory limit, max execution time, site base URLs |
| `packages` | All installed Composer packages with version and type |
| `errors` | Log records of level `error` and above, grouped by fingerprint with occurrence count, first and last occurrence |
| `scheduler` | Scheduler tasks with last and next execution and failure state (only if EXT:scheduler is installed) |
| `hardening` | `ENABLE_INSTALL_TOOL` file, `displayErrors`, restricted `trustedHostsPattern`, number of active administrators, failed logins in the last 24 hours |
| `storage` | Free and total disk space of `var/`, writability of `var/` and `typo3temp/` |

E-mail addresses, IP addresses, query string values and long tokens are redacted before error messages are stored. Request URLs are stored without query string.

## Installation

```bash
composer require tenryuubito/wiz-monitor-client
vendor/bin/typo3 extension:setup
```

Add the secret generated in the Wiz Monitor backend module to the environment of the installation, for example in `.env`:

```dotenv
WIZ_MONITOR_CLIENT_SECRET=<secret>
```

The endpoint stays disabled (HTTP 404) as long as no secret with at least 32 characters is configured.

### Capturing exceptions of the global exception handler

TYPO3 creates the logger of its global exception and error handler before `ext_localconf.php` files are loaded. Register the writer in `config/system/additional.php` as well, so that uncaught exceptions are recorded too:

```php
$GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'][\Psr\Log\LogLevel::ERROR][\Tenryuubito\WizMonitorClient\Log\Writer\MonitoringLogWriter::class] = [];
```

## Endpoint and authentication

`POST <site base>/wiz-monitor/status` is handled by a frontend middleware before site resolution. Every request has to carry

- `X-Wiz-Monitor-Timestamp`: Unix timestamp, at most 60 seconds off
- `X-Wiz-Monitor-Nonce`: 32 hexadecimal characters, accepted only once
- `X-Wiz-Monitor-Signature`: `HMAC-SHA256(secret, "request\nPOST\n<path>\n<timestamp>\n<nonce>\n<sha256(body)>")`

Requests with a missing or wrong signature, an outdated timestamp or a reused nonce receive HTTP 404. The response is signed with `HMAC-SHA256(secret, "response\n<request nonce>\n<timestamp>\n<sha256(body)>")`.

## Extending

Implement `Tenryuubito\WizMonitorClient\Status\StatusCollectorInterface` in any extension with autoconfiguration enabled to add another section to the status report.

## License

GPL-2.0-or-later
