<?php
/**
 * File containing the eZBackup class.
 *
 * @package ezadmin
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
include_once( 'lib/ezfile/classes/ezfile.php' );
include_once( 'lib/ezfile/classes/ezdir.php' );
include_once( 'lib/ezfile/classes/ezarchivehandler.php' );
include_once( 'lib/ezfile/classes/ezfilehandler.php' );
include_once( 'lib/ezlocale/classes/ezlocale.php' );
class eZBackup
{
    var $time;
    var $data;
    function __construct( $options = array() )
    {
        $this->data['compression'] = 'gzip';
        $this->data = array_merge( $options, $this->data );    
        $this->time = time();
        $ini =& eZINI::instance('site.ini');
        $this->data['DatabaseSettings'] = $ini->group('DatabaseSettings');
        $this->data['SiteAccessSettings'] = $ini->group('SiteAccessSettings');
        if ( !file_exists( $this->attribute('path') ) )
            eZDir::mkdir( $this->attribute('path') );
        if ( !file_exists( $this->attribute('path') . '/databases' ) )
            eZDir::mkdir( $this->attribute('path') . '/databases' );
        if ( !file_exists( $this->attribute('path') . '/files' ) )
            eZDir::mkdir( $this->attribute('path') . '/files' );
        if ( !file_exists( $this->attribute('path') . '/packages' ) )
            eZDir::mkdir( $this->attribute('path') . '/packages' );
    }
    function createPackage( $files, $type, $remove_path='' )
    {
        $archiveFile = $this->attribute('path') .'/packages/'. $this->attribute( 'datestring') . '-' . $type . $this->attribute('archive_extension');
        $archive = eZArchiveHandler::instance( 'tar', $this->data['compression'], $archiveFile );
        $files = array_unique( $files );
           $archive->addModify( $files, '', $remove_path );
           return $archiveFile;
    }
    function backupAllDatabases()
    {

        $sqlFile = $this->attribute('path') . '/databases/' . $this->attribute( 'datestring' ) . '-databases-all-in-one-file.sql';
        if ( $this->data['DatabaseSettings']['Password'] )
            $passstring = ' -p'.$this->data['DatabaseSettings']['Password'];
        else
            $passstring='';

        $cmd='mysqldump -A -h '.$this->data['DatabaseSettings']['Server'].' -u '.$this->data['DatabaseSettings']['User'].''.$passstring.' > "' . $sqlFile . '"';
        system( $cmd );
        $files[] = $sqlFile;
        $packagefile = $this->createPackage( $files, 'databases-all-in-one-file', $this->attribute('path') );
        foreach ( $files as $file )
        {
            eZFileHandler::doUnlink( $file );
        }
        return $packagefile;
    }
    function backupAvialableDatabases()
    {
        $db =& eZDB::instance();
        $databases = $db->availableDatabases();
        foreach( $databases as $database )
        { 
            $file = $this->dumpDatabase( $database );
            if ( $file )
                $files[] = $file;
        }
        if ( empty( $files ) )
        {
            return false;
        }
        $packagefile = $this->createPackage( $files, 'all-databases', $this->attribute('path') );
        foreach ( $files as $file )
        {
            #eZFileHandler::doUnlink( $file );
        }
        return $packagefile;
    }
    function dumpDatabase( $database = false )
    {
        
        if ( empty( $database ) )
            return false;

        $sqlFile = $this->attribute('path') . '/databases/' . $this->attribute( 'datestring' ) . '-database-'. $database .'.sql';
        if ( $this->data['DatabaseSettings']['Password'] )
            $passstring = ' -p'.$this->data['DatabaseSettings']['Password'];
        else
            $passstring='';

        $cmd='mysqldump -h '.$this->data['DatabaseSettings']['Server'].' -u '.$this->data['DatabaseSettings']['User'].''.$passstring.' "' . $database . '" > "' . $sqlFile . '"';
        system( $cmd );
        return $sqlFile;
    }
    function remove_files( $timepassed = 604800 ) # 7 days
    {
        $deletetime = time() - $timepassed;
        $files = array();
        $path = $this->attribute('path') . '/packages' ;
        $files = eZDir::findSubitems( $path );
        foreach ( $files as $file )
        {
            $file = $path.'/'.$file;
            $info = eZFileHandler::doStatistics( $file );
            if ( array_key_exists('mtime',$info) and eZFileHandler::doIsFile( $file ) )
            {
                if( $deletetime > $info['mtime'] and is_numeric( $info['mtime'] ) )
                {
                    eZFileHandler::doUnlink( $file );
                }
            }
            
        }
    }
    function remove()
    {

    }
    function attribute( $name )
    {
        switch ( $name )
        {
            case "path":
                return eZSys::cacheDirectory().'/backup';
            break;
            case "archive":
                if ( $this->data['compression'] == "gzip" )
                    return $this->attribute('path').'/'.$this->time.'.tgz';
                if ( $this->data['compression'] == "bz2" )
                    return $this->attribute('path').'/'.$this->time.'.bz2';
                return $this->attribute('path').'/'.$this->time.'.tar';
            break;
            case "datestring":
                return gmstrftime( "%d-%m-%Y-%H-%M-%S" . "-GMT", $this->time );
            break;
            case "archive_extension":
                if ( $this->data['compression'] == "gzip" )
                    return '.tgz';
                if ( $this->data['compression'] == "bz2" )
                    return '.bz2';
                return '.tar';
            break;
        }
    }
    function backupFiles( $files )
    {
        return $this->createPackage( $files, 'files', $remove_path='' );
    }
    function backup( )
    {
        $options=array();
        eZDir::recursiveDelete( $this->attribute('path') );
        eZDir::mkdir( $this->attribute('path'), 0777 );
        $sqlFile = $this->attribute('path'). '/' . $this->data['DatabaseSettings']['Database'].'.sql';

        $options['output-types']='all';

        if ( $options['output-types'] )
        {
            $includeSchema = false;
            $includeData = false;
            $includeTypes = explode( ',', $options['output-types'] );
            foreach ( $includeTypes as $includeType )
            {
                switch ( $includeType )
                {
                    case 'all':
                    {
                        $includeSchema = true;
                        $includeData = true;
                    } break;
        
                    case 'schema':
                    {
                        $includeSchema = true;
                    } break;
        
                    case 'data':
                    {
                        $includeData = true;
                    } break;
                }
            }
        }

        $dbschemaParameters = array( 'schema' => $includeSchema,
                             'data' => $includeData,
                             'format' => $options['format'] ? $options['format'] : 'generic',
                             'meta_data' => $options['meta-data'],
                             'table_type' => $options['table-type'],
                             'table_charset' => $options['table-charset'],
                             'compatible_sql' => $options['compatible-sql'],
                             'allow_multi_insert' => $options['allow-multi-insert'],
                             'diff_friendly' => true );
        $db =& eZDB::instance();
        include_once( 'lib/ezdbschema/classes/ezdbschema.php' );
        $dbSchema = eZDBSchema::instance( $db );

$outputType = 'serialized';
if ( $options['output-array'] )
    $outputType = 'array';
if ( $options['output-serialized'] )
    $outputType = 'serialized';
if ( $options['output-sql'] )
    $outputType = 'sql';

if ( $outputType == 'serialized' )
{
    $dbSchema->writeSerializedSchemaFile( $sqlFile,
                                          $dbschemaParameters );
}
else if ( $outputType == 'array' )
{
    $dbSchema->writeArraySchemaFile( $sqlFile,
                                     $dbschemaParameters );
}
else if ( $outputType == 'sql' )
{
    $dbSchema->writeSQLSchemaFile( $sqlFile,
                                   $dbschemaParameters );
}

        $archive = eZArchiveHandler::instance( 'tar', $compression, $this->attribute( 'archive' ) );
        $fileList = array();

        $fileList[] = $sqlFile;
        $fileList[] = 'settings/override';
        $fileList[] = eZSys::storageDirectory();
        foreach($this->data['SiteAccessSettings']['AvailableSiteAccessList'] as $siteaccess )
        {
            $fileList[] ='settings/siteaccess/'.$siteaccess;
        }
        $fileList = array_unique( $fileList );
        $archive->addModify( $fileList, '', '' );
        @unlink( $sqlFile );
    }
    function download()
    {
        eZFile::download( $this->attribute( 'archive' ) );
    }
    // to be continued...
    function restore ()
    {
        $sys = & eZSys::instance();
        $sys->init();
        $dir = $sys->RootDir();
        
        
        $db =& eZDB::instance();

        //system('mysql -h '.$this->data['DatabaseSettings']['Server'].' -u '.$this->data['DatabaseSettings']['User'].' -p'.$this->data['DatabaseSettings']['Password'].' '.$this->data['DatabaseSettings']['Database'].' < '.$this->data['DatabaseSettings']['Database'].'.sql');

    }
    function archive( $fileList, $archiveName, $destinationPath = false, $BaseDirectory='' )
    {

        $archivePath = $archiveName;
        if ( $destinationPath )
            $archivePath = $destinationPath . '/' . $archiveName;
        $archive = eZArchiveHandler::instance( 'tar', 'gzip', $archivePath );

           $archive->createModify( $fileList, '', $BaseDirectory );
        
        return $archivePath;
    }
    function unarchive( $to='' ,$archiveName,$destinationPath = false,$clean=false )
    {
        if ($clean)
        {
            eZDir::recursiveDelete( $to );
        }
        include_once( 'lib/ezfile/classes/ezarchivehandler.php' );
        $archivePath = $archiveName;
        if ( $destinationPath )
                $archivePath = $destinationPath . '/' . $archiveName;
        $archive = eZArchiveHandler::instance( 'tar', 'gzip', $archivePath );
        $archive->extract($to);
    }

}
?>
