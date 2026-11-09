# K-iwi

K-iwi is a PHP MVC framework for building business applications, developed by
[Krisoft s.r.l.](https://www.krisoft.it) and released under the GNU GPL 3.0.

Copyright (C) 2014 Krisoft s.r.l.

## Project status

This is K-iwi 1.0: a stable, mature release that already powers several
production applications built by Krisoft over the years. We're publishing it
as open source as a first step to share the framework with the community that
has supported our projects so far.

A more advanced K-iwi 2.0 is separately in development and will become the
future direction of the framework. 1.0 isn't going away in the meantime: it's
a solid foundation with real-world mileage, not just a proof of concept, and
it will keep being useful for anyone building on it today.

## License

K-iwi is free software distributed under the GNU GPL 3.0: see
[license.txt](license.txt). The third-party components bundled with it and
their licenses are listed in [NOTICE.txt](NOTICE.txt).

## Requirements

- Apache web server 2.4 or higher, with PHP as a module (mod_php) or PHP-FPM.
- PHP 8.0 or higher (developed and tested on PHP 8.4), with these extensions:
  - `mysqli` for MySQL/MariaDB, or `pdo_odbc` for any other ODBC compliant
    database (Oracle, PostgreSQL, SQL Server...);
  - `zlib` (optional) for output compression (`setting.compression`).
- Database server: MySQL or MariaDB (tested on MariaDB 11.8), or any other
  ODBC compliant database.

## Release log

- **1.0** — first public release.

## How to install

1. **Copy K-iwi to your server** and point the web server's DocumentRoot to
   the `krsobjp` folder (the only public one). Everything else —
   configuration, libraries, modules, logs, sessions — stays outside the web
   root. Apache example:

   ```apache
   DocumentRoot /path/to/k-iwi/krsobjp
   <Directory /path/to/k-iwi/krsobjp>
       Options FollowSymLinks
       AllowOverride All
       Require all granted
   </Directory>
   ```

   The folders outside `krsobjp` also ship an `.htaccess` that denies any
   access, as a safety net if the DocumentRoot points to the project root by
   mistake.

2. **Create an empty database** in your database engine. With MySQL/MariaDB
   use the `utf8mb4` charset (e.g. `CREATE DATABASE kiwi CHARACTER SET
   utf8mb4`), the one K-iwi connects with and creates its tables in.

3. **Edit `config/config.ini`** and set the parameters of the `[database]`
   section according to the database created in the previous step. The
   `driver` parameter must be one of:
   - `mysql` for a MySQL or MariaDB server;
   - `odbc` for an ODBC compliant database server.

4. **Run the installer** by opening

   ```
   http://yourserver/install/index.php
   ```

   where `yourserver` is the address that serves the `krsobjp` folder. If you
   see `OK` as a result, the framework is installed. Any other message, or a
   blank page, means that something went wrong during the installation: check
   `config/config.ini` and, if the problem persists, the PHP error log.

5. **Log in to the admin**:

   ```
   http://yourserver/admin
   ```

   Default user `admin`, default password `admin` — change it right away (see
   [Security](#security)). The user side of the framework is at

   ```
   http://yourserver/
   ```

## API keys and the scheduler

Every route of the user side requires a login. Machine-to-machine callers
(external systems, the cron that drives the scheduler) authenticate with an
API key instead of a session. A key belongs to a user and grants exactly that
user's permissions; only its SHA-256 hash is stored in the database.

Manage keys from the command line:

```sh
php tools/api_key.php create <username> [description]
php tools/api_key.php list
php tools/api_key.php revoke <id>
```

The key is printed only once, when it's created. On installations made before
API keys existed, the first run also creates the `krs_api_keys` table.

Send the key in the `X-API-KEY` header (preferred) or as an `api_key` POST
field. A key in the URL (`?api_key=`) is refused with a 401: it would end up
in the web server's access logs, in proxies and in the browser history. To expose an API from a module, extend `ApiController`: it checks
the HTTP method and that the request really came with a key (not with a
browser session cookie), and answers with a standard JSON
`{ "result", "stato", "id", "msg" }`:

```php
class ControllerMymoduleApiOrders extends ApiController {
	public function insert() {
		if (!$this->authorize('POST')) {
			return;
		}

		$this->load->model('mymodule/orders');
		$this->respond($this->model_mymodule_orders->insert($this->user->getId(), $this->request->post));
	}
}
```

```
POST https://yourserver/index.php?route=mymodule/api/orders/insert
X-API-KEY: <key>
```

The scheduler runs the jobs listed in `krs_scheduler` and must be called with
a key, typically from cron with a dedicated "system" user; it passes the same
key on to every job it starts:

```cron
*/5 * * * * curl -s -H "X-API-KEY: <key>" "https://yourserver/index.php?route=common/scheduler" > /dev/null
```

The scheduler starts each job with a request to this same installation,
sending the key in the `X-API-KEY` header (so it never shows up in the web
server's access logs). With `setting.use_ssl = "1"` the request goes over
https and the server certificate is verified; on a server with a self-signed
certificate add this line to `config/config.ini` (or set it from
Admin > System > Settings), otherwise the jobs won't start (the reason is
written in the error log):

```ini
setting.scheduler_ssl_verify = "0"
```

## Code generator

**Admin > Tools > Generator** writes a working CRUD module (list with search
and pagination, insert/update form, delete) for a database table:

1. Register the table with **Admin > Tools > Import Tables**.
2. Generate the module: pick the table, the fields and the features. Several
   tables can be generated into the same module.
3. Install it from **Admin > Extensions > Modules**: this creates the menu
   items and the programs (list, and "New ..." for the insert form).
4. Give permissions on the new programs to the user groups
   (**Admin > Users > User Groups**). Each user sees and can open only the
   programs of their groups, also when typing the address by hand.

The module gets a language file for every active language (labels of the
page, the list and the fields, plus `menu.php` for the menu items): they start
out with the field names in every language and are translated from
**Admin > System > Languages**. The common messages (added, updated, deleted)
come from the user side's main language file.

The generator writes PHP files into `krsobjm/<module>/`, so the web server
needs write access there while you use it. It's a development tool: on a
production server keep `krsobjm/` read-only and copy the generated module in
with the rest of the code.

## Settings

**Admin > System > Settings** edits `config/config.ini`: a form with one tab
per section (known keys get labels, drop-downs and checks; any other key is
shown as a text field) and a plain text editor for the whole file. Only the
changed lines are rewritten, so comments and order stay as they are. Before
saving, the file is checked to be valid ini, and if the database settings
change the new connection is tried first: if it fails nothing is saved. A copy
of the previous file goes to `config/backup/` (the last 10, ignored by git
because they contain the database password) and can be reopened in the
editor.

The links of the admin's **Help** menu come from the `[help]` section of
`config/config.ini` (`help.framework`, `help.documentation`, `help.support`,
`help.vendor`, the last one shown with the name in `framework.author`): point
them to your own documentation or helpdesk, or leave a value empty to hide
that entry. Only `http://` and `https://` addresses are used.

The page needs write access to `config/config.ini` and to `config/` (for the
backups); without it the page is read-only. On a production server you can
keep `config/` read-only and change the file by hand.

## Themes

**Admin > System > Themes** lists the themes of the site
(`krsobjm/standard/view/themes/<name>/`) and of the admin
(`krsobjm/admin/view/themes/<name>/`) and activates one, writing
`framework.theme` or `framework.theme_admin` with the same save as Settings
(backup copy included). Each theme is checked for its public folder
(`krsobjp/themes/<name>/`, or `krsobjp/themes/admin/<name>/` for the admin) and
for the files its templates point to through `makePath()` or a
`themes/<name>/...` link, so a half-installed theme shows what's missing. A
`preview.jpg` (or `.png`) in the public folder is shown as its preview.

The site ships four themes: `kiwi` (the look of the admin — same palette,
fonts and collapsible sidebar, light and dark — on top of Bootstrap 3, so the
pages made by the generator need no changes), `dashboard`, `dashboard_2.0`
and `default`.

## Languages

Each user picks their own language (**Admin > Users**, "Language"). Users who
haven't picked one, and anyone not logged in, get `setting.language` from
`config/config.ini`, which also covers any text missing from the user's
language. The browser's language is never used. Italian and English are
included.

**Admin > System > Languages** adds, edits and removes languages. All the
texts of a language, from every module, travel as a single JSON file: download
the default language, translate the texts (not the keys) and upload the file
on the new language. Only the files contained in the upload are replaced. The
upload is validated as plain text and the PHP files are written by the server,
so nothing in the uploaded file gets executed.

Uploading writes into `krsobjm/<module>/language/`, so the web server needs
write access to those folders. As with the generator, on a production server
you can keep them read-only and deploy the language files with the rest of
the code.

## Backup / Restore

**Admin > Tools > Backup / Restore** saves the tables of the framework
database (the `[database]` section of `config/config.ini`, MySQL/MariaDB only)
to a `.sql` file written in PHP, so it works on hosting that can't run
`mysqldump`. Every statement is on its own line and the last line holds the
SHA-256 of the whole file. Sessions (`krs_session`) are left out unless you
tick them.

A backup made here, or uploaded after being downloaded, can be restored from
a confirmation page that lists the tables it replaces and those it leaves
alone. The file must pass the SHA-256 check and contain only the statements
this page writes, for the tables named in its header; each line runs as a
single statement. Before restoring, a full backup of the current database is
made automatically, because MySQL can't roll back `DROP`/`CREATE TABLE`: if
the restore stops halfway, that backup brings the database back.

Backups are kept in `backup/` in the project root (outside the public folder,
ignored by git), which the web server needs to write. They contain the whole
database, users and password hashes included: download the ones you need and
delete the others.

## Security

Before exposing an installation to any network you don't fully control (and
before deploying to production in general):

- **Change the default `admin`/`admin` login** immediately after your first
  install: whoever logs in to the admin with the password `admin` is taken to
  their own user form, and every other page stays closed until a new password
  is saved. Changing the username too (Admin > Users) is a good idea.
- **Change the example database credentials** shipped in `config/config.ini`
  (`database.user` / `database.password`): they are placeholders, not meant
  to be used as-is.
- **Make sure the DocumentRoot points to `krsobjp`** (step 1 of the install):
  `config/config.ini` holds the database password.
- **Failed logins are limited**: after 5 wrong passwords in 15 minutes for the
  same username from the same IP address, that pair can't log in until the
  15 minutes have passed (the table `krs_login_attempts` is created on its own
  on older installations). Both values can be changed in `config/config.ini`
  or from Admin > System > Settings:

  ```ini
  setting.login_max_attempts = 5
  setting.login_lockout_minutes = 15
  ```

  Two wider limits, in the same window, stop attacks that change username or
  IP: 50 failures from one IP on any usernames lock that IP out, 100 failures
  for one username from any IP lock that username out
  (`setting.login_max_attempts_ip`, `setting.login_max_attempts_user`).

- **Keep `setting.error_display = "0"`** (the default) in `config/config.ini`:
  PHP errors go to `logs/error.txt` instead of being printed to visitors, with
  the server's paths. Turn it on only on a development machine.
- **Site POST requests carry a CSRF token**: every POST made with the session
  cookie must send back the session's token, or it's refused ("The page has
  expired"). KAJAX and jQuery's `$.ajax` add it on their own (`X-CSRF-Token`
  header); a module that posts a plain HTML form, or uploads through an
  iframe, has to add a `csrf_token` field with the value of
  `csrf_token($this->session)`. Calls made with an API key don't need it.

Enjoy!
