# xrow Admin (xrowadmin)

A toolbox for administrators and developers of an Exponential site. It adds an **Admin** tab to the
administration interface with tools you otherwise reach for on the command line or in a database client:

- **User Change**: log in as another user to see the site exactly as they do, and switch back afterwards.
- **SQL Manager**: run SQL statements against the site's database from the browser.
- **Mail Test**: send a test mail through the site's mail settings.
- **SOAP Test Client**: call a SOAP function on a server and see the answer.
- **System Check**: run the setup wizard's PHP and database checks on a running site.
- **SOLR Test**: replay a Solr query copied from eZ Find's debug output.
- **Migration**: move a node, move all children of a node, or swap two nodes, by node ID.
- **Helptools**: find the content object behind a file name, an attribute ID or a page block ID, and list
  the most recently modified and published objects.
- **Change Owner**: a context menu entry that sets a new owner on a content object.
- A set of **maintenance scripts** and **cronjob parts** (backups, image checks, empty folder cleanup).

It is meant for the people who run a site: administrators, developers and integrators. Most tools are
powerful (the SQL Manager runs any statement), so they belong in a trusted administrator's role only.

## Requirements

- Exponential 6.x (the legacy PHP kernel)
- PHP 8.0 or later
- The **ezjscore** extension (shipped with Exponential): the Migration view asks the server for node names
  through it.
- Optional: **ezfind** for the SOLR Test view, the PHP **curl** extension for the remote content views.

## Installation

The extension directory must be named `xrowadmin`.

### With Composer

```bash
composer require se7enxweb/xrowadmin
```

The Exponential legacy installer puts the package into `extension/xrowadmin`.

### By hand

```bash
cd /path/to/exponential
git clone https://github.com/se7enxweb/xrowadmin.git extension/xrowadmin
```

or download a release archive from
[GitHub releases](https://github.com/se7enxweb/xrowadmin/releases) and unpack it to `extension/xrowadmin`.

### Activate it

Add the extension to `settings/override/site.ini.append.php`:

```ini
[ExtensionSettings]
ActiveExtensions[]=xrowadmin
```

Then regenerate the autoload array and clear the caches:

```bash
php bin/php/ezpgenerateautoloads.php -e
php bin/php/ezcache.php --clear-all
```

Log in to the administration interface: the **Admin** tab appears in the top menu for every user whose role
grants `admin/menu` (see [Permissions](#permissions)).

## Usage

### The Admin tab

The tab is defined in `settings/menu.ini.append.php` as `[Topmenu_xrowadmin]`. It opens `admin/menu`, a
start page whose left menu lists every tool. In the admin4 design the tab shows the Bootstrap icon
**tools** before its name:

```ini
[Topmenu_xrowadmin]
Icon=tools
NavigationPartIdentifier=xrowadmin
Name=Admin
URL[default]=/admin/menu
PolicyList[]=admin/menu
```

Older admin designs ignore `Icon`.

### Views

| URL | What it does |
|---|---|
| `admin/menu` | Start page of the tab, with the tool menu on the left. |
| `admin/changeuserview` | Search users (every content class with an `ezuser` attribute), pick a siteaccess and log in as that user. |
| `admin/changeuser/<ObjectID>` | Log in as the user with that object ID. You need edit access to the user object. |
| `admin/recalluser` | Return to your own account after a user change. |
| `admin/sqlquery` | Run one or more SQL statements; a single `SELECT` shows the number of rows, other statements run one by one and stop at the first error. |
| `admin/mailtest` | Send a mail with the subject "Test mail &lt;date&gt;" to an address, through `eZMail` or through `ezcMail`'s MTA transport. The sender is `[MailSettings] EmailSender`, else `AdminEmail`. |
| `admin/client` | Call a SOAP function: server URL, port, user name, password and function name. |
| `admin/systemcheck` | Run the checks listed in `systemcheck.ini` plus a database connection check. |
| `admin/solrcheck` | Paste the parameter array of a query from eZ Find's debug output (it starts with `array(` or `["q"]`) and run it against Solr again. |
| `admin/migration` | Move node A below node B, move all children of A below B, or swap A and B. The names of the nodes appear when you leave an ID field. |
| `admin/helptools` | Look up a binary file name, an attribute ID or a page block ID, and run the queries listed in `helptools.ini`. |
| `admin/setowner/<ObjectID>` | Browse for a new owner (a user) and store it on the object. Reached from the **Change Owner** context menu entry. |
| `admin/backup` | Build a backup archive and download it (see [Cronjobs](#cronjobs) and `backup.ini`). |
| `admin/phpinfo` | Print `phpinfo()`. |
| `admin/frame/<module>/<view>` | Show another view (for example `admin/apc`) inside the admin page layout. |
| `admin/apc`, `admin/eaccelerator` | The control panels of the APC and eAccelerator opcode caches, shown in the menu only when the PHP extension is loaded. |
| `admin/maintance/<date>/<time>` | A "site is down for maintenance" page using the page layout of `[Maintance] PageLayout` in `layout.ini`. With a date (a timestamp or a date string) it says when the site is back. |
| `remote/content/head`, `remote/content/footer` | Fetch the page at `RemoteURL` (see [remotecontent.ini](#remotecontentini)) and return the part before (head) or after (footer) the content marker, with relative links made absolute. |
| `remote/content/full/<NodeID>` | Render a node of this site through `remote/scaffold` and return it with absolute links. |
| `remote/scaffold/<ViewMode>/<NodeID>` | Render a node with the content marker in place of its content, for use as a page frame by another site. Sends `X-Robots-Tag: noindex, nofollow`. |
| `remote/loadpage` | Return the page layout with only the content marker as its content. |
| `test/html` | A test page with common HTML elements, to check a design's styles. |

### Example: look at the site as an editor sees it

1. Open the **Admin** tab and choose **User Change**.
2. Search for the editor by name, choose the siteaccess (for example the public site) and log in.
3. Browse the site as that user.
4. Open `admin/recalluser` (the **Recall original user** link in the left menu) to return to your own account.

The original user ID is kept in the session variable `eZUserAdditionOldID`. In PHP code the class
`eZUserAddition` does the same:

```php
eZUserAddition::loginDifferentUser( $objectID ); // log in as another user, remember the current one
eZUserAddition::recallUserID();                 // the remembered user ID, or false
eZUserAddition::recallUser();                   // log the remembered user in again
```

### Template operators

| Operator | Result |
|---|---|
| `siteaccess()` | The current siteaccess array (`$GLOBALS['eZCurrentAccess']`), with keys such as `name`. |
| `function_exists( '<name>' )` | PHP's `function_exists()`, registered in `template.ini` `[PHP] PHPOperatorList`. |

```smarty
{def $access = siteaccess()}
<p>Current siteaccess: {$access.name|wash}</p>

{if function_exists( 'apcu_fetch' )}
    <p>APCu is loaded.</p>
{/if}
```

## Configuration

Copy the blocks you want to change into `settings/override/<file>.append.php` (or a siteaccess) instead of
editing the extension's files.

### systemcheck.ini

```ini
[SystemCheckSettings]
PHPTest=enabled
# Your own check classes, see classes/checkurltest.php
#CustomTests[]=checkUrlTest

[PHPTestSettings]
PHPTests[]=phpversion
PHPTests[]=database_extensions
PHPTests[]=memory_limit
# ... the full list is in settings/systemcheck.ini.append.php

#[checkUrlTest]
#CustomHandlerName=checkUrlTest
#CustomTestTitle=Check url
#CustomTests[]=check_url
```

A custom check is a class with `runTests( $testList )` and `getHeadlines()`; `classes/checkurltest.php` is
a complete example.

### helptools.ini

```ini
[helptools]
# Content class and datatype the "find block" search looks in
ContentclassID=23
DataTypeIdentifier=ezpage

[activelist]
active[]=lastmodified
active[]=lastpublished

[lastmodified]
# $$timeStamp$$ is replaced with the current time
query=SELECT id , modified , published FROM ezcontentobject WHERE modified < $$timeStamp$$ AND status = 1 AND current_version >=2 ORDER by modified DESC LIMIT 10;
headline=The 10 latest afterwards modified objects
```

Each name in `active[]` is a group with a `query` (it must return an `id` column with content object IDs) and a
`headline`.

### xrowadmin.ini

```ini
[CheckMissingImages]
# Who gets the report of bin/image_existance.php
NotificationReceiver[]=webmaster@example.com

[CheckMissingMainnodes]
# Who gets the report of bin/check_mainnode.php
NotificationReceiver[]=webmaster@example.com
```

### backup.ini

```ini
[BackupSettings]
Server=ftp.example.com
Username=exampleftpuser
Password=passwordofftpuser
Port=21
# Days to keep backups
BackupPurge=7

[Backup-default]
# all, avialable (sic) or installation
Databases=installation
Include[]=settings/
Include[]=var/
```

### remotecontent.ini

```ini
[Settings]
RemoteURL[host]=www.example.com
RemoteURL[path]=layout/page
# The marker that splits the remote page into head and footer
ContentMarker=<!--CONTENT-->
```

### Other settings the extension sets

- `browse.ini` `[BrowseNewOwner]`: the browse page of **Change Owner** starts in the users tree and lets you
  pick one user.
- `admininterface.ini` `[AdditionalMenuSettings]`: adds the **Change Owner** entry to the node context menus.
- `ezjscore.ini` `[ezjscServer_xrowadmin]`: the server function class `xrowAdminServerFunctions`.
- `design.ini`: loads `xrowadmin.js` and `helptools.css`.
- `datetime.ini`: adds the date format `ISO8601`.
- `site.ini` `[RoleSettings] PolicyOmitList[]`: `test/html` and the `remote/*` views need no policy.

## Permissions

All functions belong to the module `admin`. Grant them in a role (Users, Roles):

| Function | Lets the user |
|---|---|
| `admin/menu` | see the Admin tab and its start page |
| `admin/changeuserview`, `admin/changeuser`, `admin/recalluser` | log in as other users and return |
| `admin/sqlquery` | run SQL statements |
| `admin/mailtest` | send test mails |
| `admin/client` | use the SOAP test client |
| `admin/systemcheck` | run the system check |
| `admin/solrcheck` | run Solr queries |
| `admin/migration` | move and swap nodes |
| `admin/helptools` | use the help tools |
| `admin/setowner` | change the owner of objects |
| `admin/backup` | download backups |
| `admin/phpinfo`, `admin/apc` | see PHP and APC information |

`admin/sqlquery`, `admin/changeuser` and `admin/backup` give full control over the site: grant them to
administrators only.

## Command line scripts

The scripts in `bin/` are maintenance helpers written for real sites. Read a script before you run it: some
have values for one site in their code (user IDs, language IDs, dates) that you must change first. Run them
with the kernel's bootstrap from the installation root:

```bash
php bin/php/ezexec.php extension/xrowadmin/bin/check_mainnode.php
```

| Script | Purpose |
|---|---|
| `check_mainnode.php` | Find objects without a main node and report them (the repair calls are commented out in the script); mails `[CheckMissingMainnodes] NotificationReceiver`. |
| `check_visibility_curruptions.php` | Find nodes whose visibility flags disagree with their parents. |
| `check_language_problems.php` | Find objects whose language codes and language IDs disagree (the language map is in the script). |
| `image_existance.php` | Find image attributes whose files are missing; mails `[CheckMissingImages] NotificationReceiver`. |
| `dbintegritycheck.php` | Run a list of SQL checks for database inconsistencies and print the results (parts of the script are in German). |
| `attribute_migration.php` | Copy an object relation attribute into an object relation list attribute: arguments class ID, source attribute ID, target attribute ID. |
| `change_attribute_value.php` | Change attribute values on conditions written in the script. |
| `fix_empty_usermail.php` | Report users without an email address that have one in `ezuser_restore`. |
| `remove_user.php` | Remove disabled users without nodes from a list of object IDs in the script. |
| `updatealwaysavailable.php` | Toggle the always-available flag of every object twice to rebuild it. |
| `importcustomurlaliases.php` | Import custom URL aliases from a database table: `--source-table=<table> [--dry-run] [-n]`. |
| `migrateurlalisesfrombackup.php` | Import URL aliases from an older installation's tables. |
| `extension2tgz.php` | Pack an extension as a `.tgz` file: `--name=<extension>`. |

## Cronjobs

`settings/cronjob.ini.append.php` defines these cronjob parts:

```bash
php runcronjobs.php backup           # bin/ezbackup.php: database dump + files, uploaded by FTP (backup.ini)
php runcronjobs.php checkimages      # bin/image_existance.php
php runcronjobs.php fixscript        # bin/fixscript.php
php runcronjobs.php directorycleanup # cronjobs/directory_cleanup.php: removes empty folders in var/storage
php runcronjobs.php session_cleanup  # cronjobs/session_cleanup.php
```

See [Known limits](#known-limits) before you schedule `directorycleanup` or `session_cleanup`.

## Upgrading

From 1.1.1 or earlier: replace the extension directory with the new release, then regenerate the autoloads
and clear the caches as in [Installation](#installation). There are no database changes. The Composer
package is now `se7enxweb/xrowadmin` (the original package was `xrow/xrowadmin-ls`).

## Known limits

- Most views use the classic admin box markup; they work in every admin design but look plainest in admin4.
- The **Change Owner** context menu entry uses the context menu script of the classic admin design.
- `admin/phpmyadmin` is an unfinished experiment with a fixed local path and does not work.
- The Helptools file search joins a table named `cluster_test_db.ezdfsfile`, so it only works on a site
  with a DFS cluster database of that name.
- The cronjob settings name `session_cleanup` as the extension directory of the cronjob scripts, so the
  `directorycleanup` and `session_cleanup` parts are only found after you add
  `[CronjobSettings] ExtensionDirectories[]=xrowadmin` in an override. `session_cleanup.php` calls
  `eZSessionGarbageCollector()`, which current kernels no longer have; use the kernel's own session cleanup
  instead.
- The APC and eAccelerator panels need those PHP extensions, which modern PHP versions no longer ship.

## Troubleshooting

- **The Admin tab is missing**: check that `xrowadmin` is in `ActiveExtensions[]`, clear the caches and make
  sure your role has `admin/menu`.
- **"Access denied" on User Change**: you need `content/edit` on the user object you want to become.
- **The Migration view shows no node names**: check that ezjscore is active and that the
  `ezjscServer_xrowadmin` settings are loaded (clear the INI cache).
- **"Module not found" for a view**: regenerate the autoloads and clear the caches.

## Contributing

Bug reports and pull requests are welcome at
[github.com/se7enxweb/xrowadmin](https://github.com/se7enxweb/xrowadmin/issues).

## License

GNU General Public License v2.0 (or any later version); see [LICENSE.txt](LICENSE.txt).

## Credits and history

xrowadmin was written by xrow GmbH, Hannover, Germany (Björn Dieding, Kristina Ebel and others) from 2004 on,
first as "eZAdmin" for eZ Publish, the PHP content management system that its creator discontinued after
about 18 years. It was published as `xrow/xrowadmin-ls`. 7x maintains it for Exponential.

The screenshots in `doc/screenshots/` show older versions of the tools.
