<?PHP
/**
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @version     1.0.0
 * @package     contrexx
 * @subpackage  module_shop
 */

/**
 * The Distribution class provides the different distribution methods.
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @access      public
 * @version     1.0.0
 * @package     contrexx
 * @subpackage  module_shop
 */
class Distribution
{
    /**
     * The types of distribution
     *
     * @static
     * @access  private
     * @var     array   $arrDistributionTypes
     */
    //static
    var $arrDistributionTypes;


    /**
     * The default distribution type
     *
     * @static
     * @access  private
     * @var     string  $defaultDistributionType
     */
    //static
    var $defaultDistributionType;


    /**
     * Set up a Distribution object (PHP4)
     *
     * @access      public
     * @package     contrexx
     * @subpackage  module_shop
     * @return      Distribution object
     */
    function Distribution()
    {
        $this->__construct();
    }

    /**
     * Set up a Distribution object (PHP5)
     *
     * Mind that there is one additional delivery type here, as compared to
     * the database, called 'undefined' (index 0, zero). This type *MUST NOT* be tried to
     * be written to the database, but is only used to determine whether
     * the user actually chose a valid distribution type.
     * @access      public
     * @package     contrexx
     * @subpackage  module_shop
     * @return      Distribution object
     */
    function __construct()
    {
        $this->arrDistributionTypes = array(
            'delivery',
            'download',
            'none',
        );
        $this->defaultDistributionType = $this->arrDistributionTypes[0];
    }


    /**
     * Verifies whether the string argument is the name of a valid
     * Distribution type.
     *
     * @param   string      $string
     * return   boolean                 True if it is valid, false otherwise
     */
    function isDistributionType($string)
    {
        if (array_search($string, $this->arrDistributionTypes) !== false) {
            return true;
        }
        return false;
    }


    /**
     * Returns the default distribution type as string
     *
     * @return  string  The default distribution type
     */
    function getDefault()
    {
        return $this->defaultDistributionType;
    }


    /**
     * Returns a string containing the HTML code for the distribution type
     * dropdown menu.
     *
     * @param   string  $selected   The distribution type to preselect
     * @param   string  $menuName   The name and ID for the select element
     * @param   string  $selectAttributes   Optional attributes for the select tag
     * @return  string              The dropdown menu code
     *
     */
    function getDistributionMenu(
        $selected='', $menuName='shopDistribution',
        $onChange='', $selectAttributes='')
    {
        global $_ARRAYLANG;

        $menu = "<select name='$menuName' id='$menuName'".
            ($selectAttributes == '' ? '' : " $selectAttributes").
            ($onChange         == '' ? '' : ' onchange="'.$onChange.'"').
            ">".
            ($selected == ''
                ? "<option value='0' selected='selected'>".
                  $_ARRAYLANG['TXT_SHOP_PLEASE_SELECT'].
                  "</option>\n"
                : ''
            );
        foreach ($this->arrDistributionTypes as $type) {
            $menu .= "<option value='$type'".
                ($selected == $type ? ' selected="selected"' : '').
                '>'.
                $_ARRAYLANG['TXT_DISTRIBUTION_'.strtoupper($type)].
                "</option>\n";
        }
        return $menu.'</select>';
    }
}

?>
