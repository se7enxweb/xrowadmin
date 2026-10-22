# Changelog

## 1.1.3 (2026-10-22)

- Fixed: The classes eZBackup, eZFTPBackup, eZUserAddition and ezSiteAccessOperator have `__construct()`
  constructors; PHP 8 no longer ran their class-named constructors, so the backups did not set up their
  options and directories.
- Updated: The README names the PEAR package Net_FTP that the FTP upload of the backup cronjob part needs.

## 1.1.2 (2026-10-22)

- Updated: The Admin tab (`[Topmenu_xrowadmin]`) shows the Bootstrap icon `tools` in the admin4 design
  (`Icon=tools` in `settings/menu.ini.append.php`).
- Fixed: The backup cronjob part (`bin/ezbackup.php`) parses on PHP 8 and loads its classes from
  `extension/xrowadmin`.
- Fixed: The Migration view shows the names of the node IDs entered again (the jQuery plugin and the
  server function's template used the old name ezadmin).
- Updated: A modern README.md replaces README.txt, with installation by Composer and by hand,
  configuration, every view, permissions, scripts, cronjobs and known limits. INSTALL points to it.
- Updated: composer.json is published as `se7enxweb/xrowadmin` (formerly `xrow/xrowadmin-ls`), requires
  PHP 8.0 or later and the Exponential legacy installer, and states the license (GPL-2.0-or-later),
  authors, homepage and support links.
- Updated: ezinfo.php (class `xrowadminInfo`) and a new extension.xml state the version, copyright,
  license and website.

## 1.1.1 (2017-05-16)

- Use https everywhere.

## 1.1.0 (2016-10-25)

- Support https in the remote content views.

## 1.0.0 (2014-12-03)

- First tagged release.
