<?php

namespace wittenejdek\ldap;

class Controller
{

    /** @var string Hostname */
    protected $host;

    /** @var int Port */
    protected $port = 389;

    /** @var string Distinguished Name */
    protected $dn;

    /** @var string Domain */
    protected $domain;

    /** @return string */
    public function getHost()
    {
        return $this->host;
    }

    /** @param string $host */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /** @return string */
    public function getDn()
    {
        return $this->dn;
    }

    /** @param string $dn */
    public function setDn($dn)
    {
        $this->dn = $dn;
    }

    /** @return string */
    public function getDomain()
    {
        return sprintf($this->domain, '');
    }

    /**
     * @param string $username
     * @return string
     */
    public function getUserDomain($username)
    {
        return sprintf($this->domain, $username);
    }

    /** @param string $domain */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

}
