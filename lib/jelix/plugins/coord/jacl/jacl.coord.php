<?php
/**
* @package    jelix
* @subpackage coord_plugin
* @author     Laurent Jouanneau
* @contributor  
* @copyright  2008 Laurent Jouanneau
* @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
*/

/**
 *
 */
require(JELIX_LIB_ACL_PATH.'jAcl.class.php');

/**
* @package    jelix
* @subpackage coord_plugin
*/
class jAclCoordPlugin implements jICoordPlugin {
    public $config;

    function __construct($conf){
        $this->config = $conf;
    }

    /**
     * @param  array  $params   plugin parameters for the current action
     * @return null or jSelectorAct  if action should change
     */
    public function beforeAction ($params){
        $selector = null;
        $aclok = false;

        if(isset($params['jacl.right'])) {
            $aclok = jAcl::check($params['jacl.right'][0], $params['jacl.right'][1]);

        }elseif(isset($params['jacl.rights.and'])) {
            $aclok = true; 
            foreach($params['jacl.rights.and'] as $right) {
                if(!jAcl::check($right[0], $right[1])) {
                    $aclok = false;
                    break;
                }
            }
        }elseif(isset($params['jacl.rights.or'])) {
            foreach($params['jacl.rights.or'] as $right) {
                if(jAcl::check($right[0], $right[1])) {
                    $aclok = true;
                    break;
                }
            }
        }

        if(!$aclok){
            if($this->config['on_error'] == 1 
                || !$GLOBALS['gJCoord']->request->isAllowedResponse('jResponseRedirect')){
                throw new jException($this->config['error_message']);
            }else{
                $selector= new jSelectorAct($this->config['on_error_action']);
            }
        }

        return $selector;
    }

    public function beforeOutput(){}

    public function afterProcess (){}

}
?>
