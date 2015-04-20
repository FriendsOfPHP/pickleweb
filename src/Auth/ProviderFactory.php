<?php
namespace PickleWeb\Auth;

/**
 * Class ProviderFactory
 *
 * @package PickleWeb\Auth
 */
class ProviderFactory
{

    /**
     * @var array
     */
    protected $providers = [];

    /**
     * @param string   $type
     * @param callable $provider
     */
    public function add($type, callable $provider)
    {
        $this->providers[$type] = $provider;
    }

    /**
     * @param string $type
     *
     * @return ProviderInterface
     */
    public function get($type)
    {
        if (!array_key_exists($type, $this->providers)) {
            throw new \InvalidArgumentException(sprintf("Provider %s doesn't exist", $type));
        }

        return $this->providers[$type]();
    }
}