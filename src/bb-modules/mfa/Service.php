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

class Service implements InjectionAwareInterface{
    /**
     * @var \Box_Di
     */
    protected $di = null;

    /**
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

    /**
     * Returns a new MFA secret key.
     *
     * @return string
     */
    public function newSecret(){
        $mfa = $this->di['MFA'];
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
        $mfa = $this->di['MFA'];
        return $mfa->getCode();
    }

    public function verifyCode($secret, $code){
        $mfa = $this->di['MFA'];
        return $mfa->verifyCode($secret, $code);
    }

    public function checkTime(){
        $mfa = $this->di['MFA'];
        try {
            $mfa->ensureCorrectTime();
            returns true;
        } catch (RobThree\Auth\TwoFactorAuthException $ex) {
            new \Box_exception ("warning: host time appears to be off" . $ex->getMessage());
        }
    }

}
