<?php
/**
 * FOSSBilling
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license   Apache-2.0
 *
 * This file may contain code previously used in the BoxBilling project.
 * Copyright BoxBilling, Inc 2011-2021
 *
 * This source file is subject to the Apache-2.0 License that is bundled
 * with this source code in the file LICENSE
 */

namespace Box\Mod\MFA;

use Box\InjectionAwareInterface;
use RobThree\Auth\TwoFactorAuth;

class Service implements InjectionAwareInterface{
    /**
     * @var \Box_Di
     */
    protected $di = null;

    /*
     * @param \Box_Di $di
     */
    public function setDi($di){
        $this->di = $di;
    }

    /**
     * @return \Box_Di
     */
    public function getDi(){
        return $this->di;
    }

    public function newMFA($appName = null){
        if(is_null($appName)){
            $systemService = $this->di['mod_service']('system');
            $company = $systemService->getCompany();
            $appName = $company['name'];
        }
        $mfa = new TwoFactorAuth($appName);
        return $mfa;
    }

    /**
     * Returns a new MFA secret key.
     *
     * @return string
     */
    public function newSecret(){
        $mfa = $this->newMFA();
        return $mfa->createSecret();
    }

    /**
     * Returns a formatted MFA secret.
     *
     * @return string
     */
    public function displaySecret($secret){
        return chunk_split($secret, 4, ' ');
    }

    public function getCode($secret){
        $mfa = $this->newMFA();
        return $mfa->getCode();
    }

    public function verifyCode($secret, $code){
        $mfa = $this->newMFA();
        return $mfa->verifyCode($secret, $code);
    }

    public function checkTime(){
        $mfa = $this->newMFA();
        try {
            $mfa->ensureCorrectTime();
            return true;
        } catch (\RobThree\Auth\TwoFactorAuthException $ex) {
            throw new \Box_Exception ("warning: host time appears to be off" . $ex->getMessage());
        }
    }

}
