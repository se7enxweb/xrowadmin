<?php

class ezSiteAccessOperator
{
    function __construct()
    {
    }

    function operatorList()
    {
        return array('siteaccess');
    }

    function operatorTemplateHints()
    {
        return array( 'siteaccess' => array());
    }

    function namedParameterList()
    {
        return array();
    }

    function modify( $tpl, $operatorName, $operatorParameters, $rootNamespace, $currentNamespace, &$operatorValue, $namedParameters,  $placement)
    {
        switch ( $operatorName )
        {
           case 'siteaccess':
            {
                 $operatorValue = $GLOBALS['eZCurrentAccess'];
            } break;
         }
    }
}

?>