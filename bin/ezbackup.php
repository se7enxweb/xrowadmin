<?php
/**
 * File ezbackup.php
 *
 * @package ezadmin
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
include_once( "extension/xrowadmin/classes/ezbackup.php" );
include_once( "extension/xrowadmin/classes/ezftpbackup.php" );

if ( !$isQuiet )
    $cli->output( "Starting processing backup" );
$backupini =& eZINI::instance('backup.ini');

if ( empty( $backupType ) )
{
	$backupType="default";
}

$backupdef = $backupini->group( "Backup-" . $backupType );

$backup = new eZBackup();
$backup->setDataDir( $backupini->variable( "BackupSettings" , "Server" ) );
if ( !$isQuiet )
    $cli->output( "Starting backup database" );

switch ( $backupdef['Databases'] )
{
	case "installation":
		$backupfiles[] = $backup->backupInstallationDatabases( );
	break;
	case "avialable":
		$backupfiles[] = $backup->backupAvialableDatabases( );
	break;
	case "all":
		$backupfiles[] = $backup->backupAllDatabases( );
	break;
	default:
	break;
}


if ( !$isQuiet )
    $cli->output( "Starting backup files" );
$backupfiles[] = $backup->backupFiles( $backupdef['Include'] );

$backupConnection = new eZFTPBackup( 
	$backupini->variable( "BackupSettings" , "Server" ),
	$backupini->variable( "BackupSettings" , "Port" ),
	$backupini->variable( "BackupSettings" , "Username" ),
	$backupini->variable( "BackupSettings" , "Password" ) );

foreach ( $backupfiles as $file )
{
	if ( !$isQuiet )
    	$cli->output( "Starting FTP transfer of " . $file ." ( ". filesize( $file ) . " bytes )." );
	if ( !$backupConnection->backupFile( $file ) and !$isQuiet )
		$cli->output( "Upload failed for file" . $file );
}

$backupConnection->disconnect();

#todo remove old files
$days = 60*60*24*$backupini->variable( "BackupSettings" , "BackupPurge" ); # 14 days
$backup->remove_files( $days );

if ( !$isQuiet )
    $cli->output( "Done" );

?>