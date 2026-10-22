<?php
/**
 * File containing the eZUserAddition class.
 *
 * @package ezadmin
 * @version //autogentag//
 * @copyright Copyright (C) 2007 xrow. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl.txt GPL License
 */
class eZUserAddition
{
	function __construct()
	{

	}
	static function loginDifferentUser($ObjectID)
	{
		$http = eZHTTPTool::instance();
		$currentuser = eZUser::currentUser();
		$user = eZUser::fetch($ObjectID);
		if ($user==null)
			return false;
		
		//bye old user 
		$currentID = $http->sessionVariable( 'eZUserLoggedInID' );
		$http  = eZHTTPTool::instance();
		$currentuser->logoutCurrent();

		//welcome new user
		$user->loginCurrent();
		$http->setSessionVariable( 'eZUserAdditionOldID', $currentID );
		return true;
	}
	static function recallUser()
	{
		$http = eZHTTPTool::instance();
		if ( $http->hasSessionVariable( 'eZUserAdditionOldID' ) )
		{
			$ObjectID = $http->sessionVariable( 'eZUserAdditionOldID' );
			$http->removeSessionVariable( 'eZUserAdditionOldID' );
			$user = eZUser::currentUser();
			$user->logoutCurrent();
		
			$user = eZUser::fetch($ObjectID);
			$user->loginCurrent();
			return true;
		}
		return false;
	}
    function recallUserObject()
	{
		$http =& eZHTTPTool::instance();
		if ( $http->hasSessionVariable( 'eZUserAdditionOldID' ) )
		{
			$user = &eZUser::fetch( $http->sessionVariable( 'eZUserAdditionOldID' ) );
			if ( is_object( $user ) )
			return $user;
		}
		return false;
	}
    static function recallUserID()
	{
		$http = eZHTTPTool::instance();
		if ( $http->hasSessionVariable( 'eZUserAdditionOldID' ) )
		{
			return $http->sessionVariable( 'eZUserAdditionOldID' );
		}
		return false;
	}
}