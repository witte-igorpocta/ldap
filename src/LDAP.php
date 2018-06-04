<?php

namespace wittenejdek\ldap;

use wittenejdek\ldap\Exception\LDAPErrorException;

class LDAP
{

    /** @var array */
    protected $controllers = [];

    /** @var string */
    protected $filter = "(sAMAccountName=%s)";

    /** @var array */
    protected $attributes = [];

    /** LDAP */
    protected $ldap;

    /** @var null|Controller */
    protected $loggedIn = NULL;

    public function __construct($controllers = [], $attributes = [])
    {
        // Create controllers
        foreach ($controllers as $_) {
            $controller = new Controller();
            $controller->setHost($_["host"]);
            $controller->setPort($_["port"]);
            $controller->setDomain($_["domain"]);
            $controller->setDn($_["dn"]);
            $this->addController($controller);
        }

        $this->setAttributes($attributes);

    }

    /**
     * @param $username
     * @param $password
     * @return bool
     * @throws LDAPErrorException
     */
    public function login($username, $password)
    {
        /** @var Controller $controller */
        foreach ($this->controllers as $controller) {

            if ($this->ldap = @ldap_connect($controller->getHost(), $controller->getPort())) {

                // Configure ldap params
                ldap_set_option($this->ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($this->ldap, LDAP_OPT_REFERRALS, 0);

                // Try to bind like user
                if ($bind = @ldap_bind($this->ldap, $controller->getUserDomain($username), $password)) {
                    $this->loggedIn = $controller;
                    break;
                } else {
                    @ldap_unbind($this->ldap);
                }
            }
        }

        if (!$this->loggedIn) {
            throw new LDAPErrorException("Unable to connect to any server!");
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isLoggedIn()
    {
        return $this->loggedIn !== null;
    }

    /**
     * @return null|Controller
     */
    public function getLoggedInController()
    {
        return $this->loggedIn ?? null;
    }

    /**
     * @param $username
     * @return array|null
     * @throws LDAPErrorException
     */
    public function search($username)
    {
        if (!$this->isLoggedIn()) {
            throw new LDAPErrorException("Not connected to any server!");
        }

        $filter = sprintf($this->filter, $username);
        if ($result = ldap_search($this->ldap, $this->loggedIn->getDn(), $filter, $this->attributes)) {

            // Get entries
            $entries = ldap_get_entries($this->ldap, $result);

            // Check number of entries
            if ($entries["count"] > 1) {
                throw new LDAPErrorException("Founded more than one record");
            }

            if ($entries["count"] === 0) {
                return NULL;
            }

            // Send result
            return ldap_get_attributes($this->ldap, ldap_first_entry($this->ldap, $result));

        } else {
            throw new LDAPErrorException("Unable to search LDAP server");
        }
    }

    /**
     * @param array $attributes LDAP Attributes
     * @return array
     */
    public function parseAttributes(array $attributes = [])
    {
        $output = [];

        foreach ($attributes as $key => $attribute) {
            if (in_array($key, $this->attributes)) {
                if ($key === "memberOf") {
                    // Remove number of groups from attributes
                    unset($attribute["count"]);
                    $output[$key] = $attribute;
                } elseif (is_array($attribute)
                    && array_key_exists("count", $attribute)
                    && $attribute["count"] !== 0
                    && array_key_exists(0, $attribute)) {
                    $output[$key] = $attribute[0];
                }
            }
        }

        return $output;
    }

    /**
     * @return void
     */
    public function disconnect()
    {
        @ldap_unbind($this->ldap);
        $this->loggedIn = null;
    }

    /**
     * @param Controller $controller
     * @return int index
     */
    public function addController(Controller $controller)
    {
        return array_push($this->controllers, $controller);
    }

    /**
     * @return array
     */
    public function getControllers()
    {
        return $this->controllers;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param array $attributes
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
    }

}

