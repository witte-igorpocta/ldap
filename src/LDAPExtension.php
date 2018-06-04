<?php

namespace wittenejdek\ldap;

use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette\Reflection\Extension;

class LDAPExtension extends CompilerExtension
{
    /** @var array */
    private $defaults = [
        'attributes' => [
            "employeeNumber",
            "employeeID",
            "mail",
            "cn",
        ],
        'controllers' => [],
    ];

    public function loadConfiguration()
    {

        $this->validateConfig($this->defaults);
        $builder = $this->getContainerBuilder();

        $builder->addDefinition($this->prefix('main'))
            ->setFactory(LDAP::class, [
                $this->config['controllers'],
                $this->config['attributes'],
            ]);
    }

    /**
     * @param Configurator $configurator
     */
    public static function register(Configurator $configurator)
    {
        $configurator->onCompile[] = function ($config, Compiler $compiler) {
            $compiler->addExtension('WITTENejdek_LDAP', new Extension());
        };
    }

}