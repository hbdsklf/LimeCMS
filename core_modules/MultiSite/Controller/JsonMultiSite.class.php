<?php

/**
 * JSON Adapter for Multisite
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Sudhir Parmar <sudhirparmar@cdnsol.com>
 * @version     4.0.0
 * @package     contrexx
 * @subpackage  Multisite
*/

namespace Cx\Core_Modules\MultiSite\Controller;

class MultiSiteJsonException extends \Exception {
    /**
     * Overwriting the default Exception constructor
     * The default Exception constructor only accepts $message to be a string.
     * We do overwrite this here to also allow $message to be an array
     * that can then be sent back in the JsonData-response.
     */
    public function __construct($message = null, $code = 0, Exception $previous = null) {
        parent::__construct('', $code, $previous);
        $this->message = $message;
    }
}
/**
 * JSON Adapter for Multisite
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Sudhir Parmar <sudhirparmar@cdnsol.com>
 * @version     4.0.0
 * @package     contrexx
 * @subpackage  Multisite
*/
class JsonMultiSite implements \Cx\Core\Json\JsonAdapter {

    /**
    * Returns the internal name used as identifier for this adapter
    * @return String Name of this adapter
    */
    public function getName() {
        return 'MultiSite';
    }

    /**
    * Returns an array of method names accessable from a JSON request
    * @return array List of method names
    */
    public function getAccessableMethods() {
        $multiSiteProtocol = (\Cx\Core\Setting\Controller\Setting::getValue('multiSiteProtocol') == 'mixed')? \Env::get('cx')->getRequest()->getUrl()->getProtocol(): \Cx\Core\Setting\Controller\Setting::getValue('multiSiteProtocol');
        return array(
            'signup'                => new \Cx\Core\Access\Model\Entity\Permission(array($multiSiteProtocol), array('post'), false),
            'email'                 => new \Cx\Core\Access\Model\Entity\Permission(array($multiSiteProtocol), array('post'), false),
            'address'               => new \Cx\Core\Access\Model\Entity\Permission(array($multiSiteProtocol), array('post'), false),
            'createWebsite'         => new \Cx\Core\Access\Model\Entity\Permission(array($multiSiteProtocol), array('post'), false, array($this, 'auth')),
            'createUser'            => new \Cx\Core\Access\Model\Entity\Permission(array($multiSiteProtocol), array('post'), false, array($this, 'auth')),
            'updateUser'            => new \Cx\Core\Access\Model\Entity\Permission(array($multiSiteProtocol), array('post'), false, array($this, 'auth')),
            'updateOwnUser'         => new \Cx\Core\Access\Model\Entity\Permission(array($multiSiteProtocol), array('post'), true),
            'mapDomain'             => new \Cx\Core\Access\Model\Entity\Permission(array($multiSiteProtocol), array('post'), false, array($this, 'auth')),
            'unMapDomain'           => new \Cx\Core\Access\Model\Entity\Permission(array($multiSiteProtocol), array('post'), false, array($this, 'auth')),
            'updateDefaultCodeBase' => new \Cx\Core\Access\Model\Entity\Permission(array($multiSiteProtocol), array('post'), true, array($this, 'checkPermission')),
            'setWebsiteState'       => new \Cx\Core\Access\Model\Entity\Permission(array($multiSiteProtocol), array('post'), false, array($this, 'auth'))
        );
    }

    /**
    * Returns all messages as string
    * @return String HTML encoded error messages
    */
    public function getMessagesAsString() {
        return '';
    }
    
    /**
     * Returns default permission as object
     * @return Object
     */
    public function getDefaultPermissions() {
        return null;
    }

    /**
     * Check if there is already an account present
     * by the supplied email address.
     * @param array $params supplied arguments from JsonData-request
     * @return void Returns nothing in case the email is not yet registered
     * @throws MultiSiteJsonException An array with further information about the already used email address
     */
    public function email($params) {
        if (!isset($params['post']['multisite_email_address'])) {
            return;
        }

        self::verifyEmail($params['post']['multisite_email_address']);
    }

    public static function verifyEmail($email) {
        global $_ARRAYLANG;

        if (!\User::isUniqueEmail($email)) {
            global $_ARRAYLANG, $objInit;
            $langData = $objInit->loadLanguageData('MultiSite');
            $_ARRAYLANG = array_merge($_ARRAYLANG, $langData);

// TODO: set login url
            $loginUrl = '';
            $loginLink = '<a class="alert-link" href="'.$loginUrl.'" target="_blank">'.$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_LOGIN'].'</a>';
            throw new MultiSiteJsonException(array(
                'object'    => 'email',
                'type'      => 'info',
                'message'   => sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_EMAIL_IN_USE'], $loginLink),
            ));
        }
    }

    public function address($params) {
        if (!isset($params['post']['multisite_address'])) {
            return;
        }
        try {
            \Cx\Core_Modules\MultiSite\Model\Entity\Website::validateName(contrexx_input2raw($params['post']['multisite_address']));
        } catch (\Cx\Core_Modules\MultiSite\Model\Entity\WebsiteException $e) {
            throw new MultiSiteJsonException(array('object' => 'address', 'type' => 'warning', 'message' => $e->getMessage()));
        }
    }

    /**
    * function signup
    * @param post parameters
    * */
    public function signup($params){
        // load text-variables of module MultiSite
        global $_ARRAYLANG, $objInit;
        $langData = $objInit->loadLanguageData('MultiSite');
        $_ARRAYLANG = array_merge($_ARRAYLANG, $langData);
        $objUser = new \Cx\Core_Modules\MultiSite\Model\Entity\User();
        if (!empty($params['post'])) {
            $websiteName = contrexx_input2raw($params['post']['multisite_address']);
            $user = $this->createUser(array('post' => array('email' => $params['post']['multisite_email_address'])));
            // create a new CRM Contact and link it to the User account
            if (!empty($user['userId'])) {
                $objFWUser = \FWUser::getFWUserObject();
                $objUser   = $objFWUser->objUser->getUser(intval($user['userId']));
                
                $objUser->objAttribute->first();
                while (!$objUser->objAttribute->EOF) {
                    $arrProfile[$objUser->objAttribute->getId()][] = $objUser->getProfileAttribute($objUser->objAttribute->getId());
                    $objUser->objAttribute->next();
                }
                
                foreach ($arrProfile as $key => $value) {
                    $arrProfile['fields'][] = array('special_type' => 'access_'.$key);
                    $arrProfile['data'][]   = $value[0];
                    unset($arrProfile[$key]);
                }
                $arrProfile['fields'][] = array('special_type' => 'access_email');
                $arrProfile['data'][]   = $objUser->getEmail();
                $objCrmLibrary = new \Cx\Modules\Crm\Controller\CrmLibrary('Crm');
                $objCrmLibrary->addCrmContact($arrProfile);
            }
            //call createWebsite method.
            return $this->createWebsite(array('post' => array('userId' => $user['userId'])), $websiteName);
        }
    }

    /**
     * Creates a new website
     * @param type $params  
    */
    public function createWebsite($params, $websiteName='') {
        // load text-variables of module MultiSite
        global $_ARRAYLANG, $objInit;
        
        $objFWUser   = \FWUser::getFWUserObject();
        $objUser     = $objFWUser->objUser->getUser(contrexx_input2raw($params['post']['userId']));
        $websiteId   = isset($params['post']['websiteId']) ? contrexx_input2raw($params['post']['websiteId']) : '';
        $websiteName = isset($params['post']['websiteName']) ? contrexx_input2raw($params['post']['websiteName']) : $websiteName;
        
        //load language file 
        $langData = $objInit->loadLanguageData('MultiSite');
        $_ARRAYLANG = array_merge($_ARRAYLANG, $langData);
        
        $basepath = \Cx\Core\Setting\Controller\Setting::getValue('websitePath');
        $websiteServiceServer = null;
        if (\Cx\Core\Setting\Controller\Setting::getValue('mode') == ComponentController::MODE_MANAGER) {
            //get default service server
            $defaultWebsiteServiceServer = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer')
            ->findBy(array('isDefault' => 1));
            $websiteServiceServer = $defaultWebsiteServiceServer[0];
        }

        try {
            $objWebsite = new \Cx\Core_Modules\MultiSite\Model\Entity\Website($basepath, $websiteName, $websiteServiceServer, $objUser, false);
            if ($websiteId!='') {
                $objWebsite->setId($websiteId);
            }
            \Env::get('em')->persist($objWebsite);
            \Env::get('em')->flush();
            return $objWebsite->setup();
        } catch (\Cx\Core_Modules\MultiSite\Model\Entity\WebsiteException $e) {
            throw new MultiSiteJsonException($e->getMessage());    
        }
    }

    public function createUser($params) {
        if (!empty($params['post'])) {
            $objUser = new \Cx\Core_Modules\MultiSite\Model\Entity\User();
            if (!empty($params['post']['userId'])) {
                $objUser->setId($params['post']['userId']);
            }
            $objUser->setEmail(!empty($params['post']['email']) ? contrexx_input2raw($params['post']['email']) : '');
            $objUser->setActiveStatus(!empty($params['post']['active']) ? (bool)$params['post']['active'] : false);
            $objUser->setAdminStatus(!empty($params['post']['admin']) ? (bool)$params['post']['admin'] : false);
            $objUser->setPassword(\User::make_password(8,true));
            
            //check email validity
            if (!\FWValidator::isEmail($params['post']['email'])) {
                throw new MultiSiteJsonException('The email you entered is invalid.');
            }
            //check email existence
            self::verifyEmail($params['post']['email']);
            
            //call \User\store function to store all the info of new user
            if (!$objUser->store()) {
                throw new MultiSiteJsonException($objUser->error_msg);
            } else {
                return array('userId' => $objUser->getId());
            }
        }
    }

    /**
     * Update an existing user account
     * @param array $params POST-data based on with the account shall be updated to
     * @return  boolean     TRUE on success, FALSE on failure.
     */
    public function updateUser($params) {
        $objFWUser = \FWUser::getFWUserObject();
        if (empty($params['post']['userId'])) {
            throw new MultiSiteJsonException(array(
                'object'    => 'form',
                'type'      => 'danger',
                'message'   => 'Unknown user account',
            ));
        }

        \Cx\Core\Setting\Controller\Setting::init('MultiSite', '','FileSystem');
        switch(\Cx\Core\Setting\Controller\Setting::getValue('mode')) {
            case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_SERVICE:
                if (empty($params['post']['websiteId'])) {
                    return;
                }
                $webRepo    = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
                $objWebsite = $webRepo->findById($params['post']['websiteId']);
                $hostName   = $objWebsite->getBaseDn()->getName();
                $installationId = \Cx\Core\Setting\Controller\Setting::getValue('managerInstallationId');  
                $secretKey = \Cx\Core\Setting\Controller\Setting::getValue('managerSecretKey');
                $httpAuth = array(
                    'httpAuthMethod' => \Cx\Core\Setting\Controller\Setting::getValue('managerHttpAuthMethod'),
                    'httpAuthUsername' => \Cx\Core\Setting\Controller\Setting::getValue('managerHttpAuthUsername'),
                    'httpAuthPassword' => \Cx\Core\Setting\Controller\Setting::getValue('managerHttpAuthPassword'),
                );
                $params = array(
                    'auth'   => \Cx\Core_Modules\MultiSite\Controller\JsonMultiSite::getAuthenticationObject($secretKey, $installationId),
                    'userId' => $params['post']['userId'],
                    'multisite_user_profile_attribute' => $params['post']['multisite_user_profile_attribute']
                );
                $objJsonData = new \Cx\Core\Json\JsonData();
                $objJsonData->getJson(ComponentController::getApiProtocol() . $hostName . '/cadmin/index.php?cmd=JsonData&object=MultiSite&act=updateUser', $params, false, '', $httpAuth);
                return;
                break;
            case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE:
                $websiteUserId = \Cx\Core\Setting\Controller\Setting::getValue('websiteUserId');
                $objUser = $objFWUser->objUser->getUser(intval($websiteUserId));
                if (!$objUser) {
                    throw new MultiSiteJsonException(array(
                        'object'    => 'form',
                        'type'      => 'danger',
                        'message'   => 'Unknown user account',
                    ));
                }
                break;
            case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER:    
            case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID:    
                $objUser = $objFWUser->objUser->getUser(intval($params['post']['userId']));
                if (!$objUser) {
                    throw new MultiSiteJsonException(array(
                        'object'    => 'form',
                        'type'      => 'danger',
                        'message'   => 'Unknown user account',
                    ));
                }
            default:
                break;
        }
        $data = $params['post']['multisite_user_profile_attribute'];
        
        isset($data['multisite_user_username']) ? $objUser->setUsername(trim(contrexx_input2raw($data['multisite_user_username']))) : null;
        $objUser->setEmail(isset($data['multisite_user_email']) ? trim(contrexx_input2raw($data['multisite_user_email'])) : (isset($params['post']['multisite_user_account_email']) ? trim(contrexx_input2raw($params['post']['multisite_user_account_email'])) : $objUser->getEmail()));
        $currentLangId = $objUser->getFrontendLanguage();
        $objUser->setFrontendLanguage(isset($data['multisite_user_frontend_language']) ? intval($data['multisite_user_frontend_language']) : $objUser->getFrontendLanguage());
        $objUser->setBackendLanguage(isset($data['multisite_user_backend_language']) ? intval($data['multisite_user_backend_language']) : $objUser->getBackendLanguage());
        $objUser->setEmailAccess(isset($data['multisite_user_email_access']) && $objUser->isAllowedToChangeEmailAccess() ? contrexx_input2raw($data['multisite_user_email_access']) : $objUser->getEmailAccess());
        $objUser->setProfileAccess(isset($data['multisite_user_profile_access']) && $objUser->isAllowedToChangeProfileAccess() ? contrexx_input2raw($data['multisite_user_profile_access']) : $objUser->getProfileAccess());
        if (!empty($data['multisite_user_password']) || !empty($params['post']['multisite_user_account_password'])) {
            $password = !empty($data['multisite_user_password']) ? trim(contrexx_stripslashes($data['multisite_user_password'])) : (!empty($params['post']['multisite_user_account_password']) ? trim(contrexx_stripslashes($params['post']['multisite_user_account_password'])) : '');
            $confirmedPassword = !empty($data['multisite_user_password_confirmed']) ? trim(contrexx_stripslashes($data['multisite_user_password_confirmed'])) : (!empty($params['post']['multisite_user_account_password_confirmed']) ? trim(contrexx_stripslashes($params['post']['multisite_user_account_password_confirmed'])) : '');
            if (!$objUser->setPassword($password, $confirmedPassword)) {
                throw new MultiSiteJsonException(array(
                    'object'    => 'password',
                    'type'      => 'danger',
                    'message'   => $objUser->getErrorMsg(),
                ));
            }
        }
        
        $objUser->setProfile($data);
        if (!$objUser->store()) {
            throw new MultiSiteJsonException(array(
                'object'    => 'form',
                'type'      => 'danger',
                'message'   => $objUser->getErrorMsg(),
            ));
        }
                
        return true;
    } 

    /**
     * Update the user account of the signed-in user
     * @param array $params POST-data based on with the account shall be updated to
     * @return  boolean     TRUE on success, FALSE on failure.
     */
    public function updateOwnUser($params) {
        $objFWUser = \FWUser::getFWUserObject();
        $objUser = $objFWUser->objUser;
        if (!$objUser->login()) {
            throw new MultiSiteJsonException(array(
                'object'    => 'form',
                'type'      => 'danger',
                'message'   => 'Operation denied',
            ));
        }

        // Only administrators or users with sufficient permissions
        // may update their own account.
        if (!\Permission::hasAllAccess() && !\Permission::checkAccess(31, 'static', true)) {
            throw new MultiSiteJsonException(array(
                'object'    => 'form',
                'type'      => 'danger',
                'message'   => 'Operation denied',
            ));
        }

        $params['post']['userId'] = $objUser->getId();
        return $this->updateUser($params);
    } 

    /**
     *  callback authentication for verifing secret key and installation id based on mode
     * 
     * @return boolean
     */
    public function auth(array $params = array()) 
    {   
        $authenticationValue = isset($params['post']['auth']) ? json_decode($params['post']['auth'], true) : '';

        if (   empty($authenticationValue)
            || !is_array($authenticationValue)
            || !isset($authenticationValue['sender'])
            || !isset($authenticationValue['key'])
        ) {
            return false;
        }
        $config = \Env::get('config');
        $installationId = $config['installationId'];
        switch (\Cx\Core\Setting\Controller\Setting::getValue('mode')) {
            case ComponentController::MODE_MANAGER:
                try {
                    $WebsiteServiceServerRepository = \Env::get('em')->getRepository('\Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer');
                    $objWebsiteService = $WebsiteServiceServerRepository->findBy(array('hostName' => $authenticationValue['sender']));
                    $secretKey = $objWebsiteService->getSecretKey();
                } catch(\Exception $e) {
                    return $e->getMessage();
                }
                break;

            case ComponentController::MODE_SERVICE:
            case ComponentController::MODE_HYBRID:
                //Check if the sender is manager or not
                if ($authenticationValue['sender'] == \Cx\Core\Setting\Controller\Setting::getValue('managerHostname')) {
                    $secretKey = \Cx\Core\Setting\Controller\Setting::getValue('managerSecretKey');
                } else {
                    try {
                        $domainRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Domain');
                        $domain     = $domainRepo->findOneBy(array('name' => $authenticationValue['sender']));
                        $secretKey  = $domain->getWebsite()->getSecretKey();
                    } catch (\Exception $e) {
                        return $e->getMessage();
                    }
                }
                break;
                
            case ComponentController::MODE_WEBSITE:
                $secretKey = \Cx\Core\Setting\Controller\Setting::getValue('serviceSecretKey');
                break;
        }
        
        if (md5($secretKey.$installationId) === $authenticationValue['key']) {
            return true;
        }
        
        return false;
    }

    /**
     * Callback authentication for checking the user's access permission
     * 
     * @return boolean
     */
    public function checkPermission() 
    {
        if (\Permission::checkAccess(183, 'static', true)) {
            return true;
        }
        
        return false;
    }
    
    /**
     *  Get the Authentication Object
     * 
     * @param String $secretKey
     * @param String $remoteInstallationId
     * 
     * @return json
     */
    public static function getAuthenticationObject($secretKey, $remoteInstallationId) 
    {
        $key = md5($secretKey . $remoteInstallationId);
        $config = \Env::get('config');

        return json_encode(array(
            'key'     => $key,
            'sender' => $config['domainUrl'],
        ));
    }
    /**
     *  Get the auto-generated SecretKey
     * 
     * @return string 
     */
    public static function generateSecretKey(){
        return bin2hex(openssl_random_pseudo_bytes(16));    
    }
    
     /**
     * Map the website domain
     * 
     * @param type $params
     * @return type
     */
    public function mapDomain($params){
        if (!empty($params['post']) && !empty($params['post']['domainName'])) {
            try {                
                $authenticationValue = isset($params['post']['auth']) ? json_decode($params['post']['auth'], true) : '';

                if (empty($authenticationValue) || !is_array($authenticationValue)) {
                    return false;
                }
                $domainRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Domain');
                $domain     = $domainRepo->findOneBy(array('name' => $authenticationValue['sender']));
                $website    = $domain ? $domain->getWebsite() : '';
                
                if (isset($website) && $website instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Website) {
                    $objDomain = new \Cx\Core_Modules\MultiSite\Model\Entity\Domain($params['post']['domainName']);                
                    $website->mapDomain($objDomain);
                
                    \Env::get('em')->persist($objDomain);
                    \Env::get('em')->persist($website);
                } else {
                    $objDomain = new \Cx\Core_Modules\MultiSite\Model\Entity\Domain($params['post']['domainName']);
                    \Env::get('em')->persist($objDomain);
                }
                
                \Env::get('em')->flush();
                
            } catch (\Exception $e) {
                return $e->getMessage();
            }
        }
    }        
    
    /**
     * Unmap the website domain
     * 
     * @param type $params
     * @return type
     */
    public function unMapDomain($params)
    {
        if (!empty($params['post']) && !empty($params['post']['domainName'])) {
            $authenticationValue = isset($params['post']['auth']) ? json_decode($params['post']['auth'], true) : '';

            if (empty($authenticationValue) || !is_array($authenticationValue)) {
                return false;
            }
            
            $domainRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Domain');
            $domain     = $domainRepo->findOneBy(array('name' => $authenticationValue['sender']));
            $website    = $domain ? $domain->getWebsite() : '';
            
            if (isset($website) && $website instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Website) {
                $website->unmapDomain($params['post']['domainName']);
                \Env::get('em')->persist($website);
            } else {
                $objDomainRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Domain');
                $objDomain     = $objDomainRepo->findOneBy(array('name' => $params['post']['domainName']));
                \Env::get('em')->remove($objDomain);
                \Env::get('em')->persist($objDomain);
            }
            
            \Env::get('em')->flush();
        }
    }
    /**
     * update the default codeBase
     * 
     * @param array $params
     * 
     * @return string
     */
    public function updateDefaultCodeBase($params) 
    {
        global $_ARRAYLANG,$objInit;
        $langData = $objInit->loadLanguageData('MultiSite');
        $_ARRAYLANG = array_merge($_ARRAYLANG, $langData);
        if (!empty($params['post'])) {
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', 'websiteSetup','FileSystem');
            \Cx\Core\Setting\Controller\Setting::set('defaultCodeBase',$params['post']['defaultCodeBase']);
            if (\Cx\Core\Setting\Controller\Setting::update('defaultCodeBase')) {
                return $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_DEFAULTCODEBASE_SUCCESSFUL_CREATION'];
            }
        }
    }
    /**
     * update the domain alias
     * 
     * @param array $params
     * 
     * @return string
     */
    public function updateDomain($params) {

        if (!empty($params['post']) && !empty($params['post']['domainName']) && !empty($params['post']['domainId'])) {
            $authenticationValue = isset($params['post']['auth']) ? json_decode($params['post']['auth'], true) : '';

            if (empty($authenticationValue) || !is_array($authenticationValue)) {
                return false;
            }
            try {
                switch (\Cx\Core\Setting\Controller\Setting::getValue('mode')) {
                    case ComponentController::MODE_MANAGER:
                    case ComponentController::MODE_SERVICE:
                    case ComponentController::MODE_HYBRID:
                        $domainRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Domain');
                        $domain = $domainRepo->findOneBy(array('coreNetDomainId' => $params['post']['domainId']));
                        $domain->setName($params['post']['domainName']);
                        break;

                    case ComponentController::MODE_WEBSITE:
                        $domainRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Domain');
                        $objDomain = $domainRepo->findOneBy(array('name' => $authenticationValue['sender']));
                        $domain = $domainRepo->findOneBy(array('websiteId' => $objDomain->getWebsite()->getId(), 'coreNetDomainId' => $params['post']['domainId']));
                        $domain->setName($params['post']['domainName']);
                        break;
                }
                \Env::get('em')->persist($domain);
                \Env::get('em')->flush();
            } catch (\Exception $e) {
                return $e->getMessage();
            }
        }
    }
    /**
     * set Website State
     * 
     * @param array $params
     * 
     */
    public function setWebsiteState($params) {
         if (!empty($params['post'])) {
            $webRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
            $website = $webRepo->findOneById($params['post']['websiteId']);
            $website->setStatus($params['post']['status']);
            \Env::get('em')->persist($website);
            \Env::get('em')->flush();
        }
    }

}
