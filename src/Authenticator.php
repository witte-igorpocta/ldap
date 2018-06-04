<?php

namespace wittenejdek\ldap;

use Nette;
use wittenejdek\ldap\Exception\LDAPErrorException;

final class Authenticator implements Nette\Security\IAuthenticator
{
    /** @var LDAP $ldap */
    private $ldap;

    /** @var Callable */
    public $parseAttributes;

    /** @var Callable */
    public $identityGenerator;

    /** @var callable|array */
    public $onSuccess = [];

    public function __construct(LDAP $ldap)
    {
        $this->ldap = $ldap;
        $this->setParseAttributes([$this, 'parseAttributes']);
        $this->setIdentityGenerator([$this, 'createIdentity']);
    }

    function authenticate(array $credentials)
    {
        // Get username and password from credentials
        [$username, $password] = $credentials;

        try {

            // Login to LDAP
            $this->ldap->login($username, $password);

            // Search user
            $obtainedAttributes = $this->ldap->search($username);

            // Get attributes
            $attributes = call_user_func_array($this->parseAttributes, [$obtainedAttributes]);

            // Success handlers
            foreach ($this->onSuccess as $key => $handler) {
                $data[$key] = call_user_func_array($handler, [$this->ldap, $attributes]);
            }

            // Get & return the identity
            return call_user_func_array($this->identityGenerator, [$this->ldap, $attributes]);

        } catch (LDAPErrorException $e) {

            throw new Nette\Security\AuthenticationException($e->getMessage(), self::FAILURE);

        } finally {

            // Disconnect
            $this->ldap->disconnect();

        }
    }

    /**
     * @param LDAP $ldap
     * @param array $attributes
     * @return Nette\Security\Identity
     */
    public function createIdentity(LDAP $ldap, array $attributes = [])
    {
        // Create identity & return
        return new Nette\Security\Identity($attributes['employeeNumber'], [], $attributes);
    }

    /**
     * @param array $obtainedAttributes
     * @return array
     */
    public function parseAttributes(array $obtainedAttributes = []) {
        return $this->ldap->parseAttributes($obtainedAttributes);
    }

    /**
     * @param callable $handler <string>function($obtainedAttributes)
     */
    public function setParseAttributes($handler)
    {
        $this->parseAttributes = $handler;
    }

    /**
     * @param string $dataKey $userData[$dataKey] = return of the callback
     * @param Callable $handler <void>function(LDAP, $attributes)
     */
    public function addSuccessHandler($dataKey, $handler)
    {
        $this->onSuccess[$dataKey] = $handler;
    }

    /**
     * @param string $dataKey data key to be removed
     */
    public function removeSuccessHandler($dataKey)
    {
        unset($this->onSuccess[$dataKey]);
    }

    /**
     * @param callable $handler <IIdentity>function(LDAP, $attributes)
     */
    public function setIdentityGenerator($handler)
    {
        $this->identityGenerator = $handler;
    }

}
