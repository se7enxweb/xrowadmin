<?PHP
/**
 * File containing the eZFTPBackup class.
 *
 * @package ezadmin
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
include_once( 'lib/ezutils/classes/ezmimetype.php' );
include_once( "extension/ezpowerlib/ezpowerlib.php" );
include_once( "Net/FTP.php" );
class eZFTPBackup extends Net_FTP
{
    var $con;
    var $data_path = 'html/';
    function __construct( $host=null, $port=21, $user=null, $password=null )
    {
        parent::__construct();
        $this->setHostname( $host );
        $this->setPort( $port );
        $this->setUsername( $user );
        $this->setPassword( $password );
        $this->connect( $host, $port );
        $this->login( $user, $password );
    }
#    function setDataDir( )
#    {
#        
#        $this->
#    }
    function backupFile( $filename )
    {
        $mime = eZMimeType::findByFileContents( $filename );
        $backupfilename = $this->data_path . $mime['filename'];
        $ret = $this->put( $filename, $backupfilename, false, FTP_BINARY, 700 );
        if ( PEAR::isError( $ret ) )
            return false;
        else
            return true;
    }
}