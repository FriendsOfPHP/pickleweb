<?php

namespace PickleWeb\tests\unit\Entity;

use atoum;
use PickleWeb\Auth\ProviderMetadata;

/**
 * Class User
 *
 * @package PickleWeb\tests\unit\Entity
 */
class User extends atoum
{
    /**
     * @test
     */
    public function test__construct()
    {
        $this->object($this->newTestedInstance())
            ->isInstanceOf('PickleWeb\Entity\User');
    }

    /**
     * @test
     */
    public function testGettersSetters()
    {
        /* @var $user \PickleWeb\Entity\User */
        $user = $this->newTestedInstance();

        // mail/id
        $this->string($user->getEmail())
            ->isEmpty();
        $this->string($user->getId())
            ->isEmpty();
        $user->setEmail('FOO@BAR.com');
        $this->string($user->getEmail())
            ->isNotEmpty()
            ->isEqualTo('FOO@BAR.com');
        $this->string($user->getId())
            ->isNotEmpty()
            ->isEqualTo('foo@bar.com');

        // nickname
        $this->string($user->getNickname())
            ->isEmpty();
        $user->setNickname('foo');
        $this->string($user->getNickname())
            ->isNotEmpty()
            ->isEqualTo('foo');

        // name
        $this->string($user->getName())
            ->isEmpty();
        $user->setName('FOO Bar');
        $this->string($user->getName())
            ->isNotEmpty()
            ->isEqualTo('FOO Bar');

        // picture
        $this->string($user->getPicture())
            ->isEmpty();
        $user->setPicture('http://gravatar.com/foo.jpg');
        $this->string($user->getPicture())
            ->isNotEmpty()
            ->isEqualTo('http://gravatar.com/foo.jpg');

        // location
        $this->string($user->getLocation())
            ->isEmpty();
        $user->setLocation('Paris');
        $this->string($user->getLocation())
            ->isNotEmpty()
            ->isEqualTo('Paris');
    }

    /**
     * @test
     */
    public function testProviderMetadata()
    {
        /* @var $user \PickleWeb\Entity\User */

        // No provider defined
        $this->given($user = $this->newTestedInstance())
            ->then()
                ->boolean($user->hasProviderMetadata('github'))->isFalse()
                ->variable($user->getProviderMetadata('github'))->isNull()
                ->variable($user->getGithubId())->isNull()
                ->variable($user->getGithubHomepage())->isNull()
                ->variable($user->getGoogleId())->isNull()
                ->variable($user->getGoogleHomepage())->isNull()
                ->variable($user->getBitbucketId())->isNull()
                ->variable($user->getBitbucketHomepage())->isNull();

        // Github
        $this->given($user = $this->newTestedInstance())
            ->and($metadata = new ProviderMetadata(
                [
                    'uid' => 'foobar',
                    'homepage' => 'http://foo.bar'
                ]
            ))
            ->if($user->addProviderMetadata('github', $metadata))
            ->then
                ->boolean($user->hasProviderMetadata('github'))->isTrue()
                ->string($user->getGithubId())->isNotEmpty()->isEqualTo('foobar')
                ->string($user->getGithubHomepage())->isNotEmpty()->isEqualTo('http://foo.bar')
                ->object($user->getProviderMetadata('github'))->isEqualTo($metadata)
                ->variable($user->getGoogleId())->isNull()
                ->variable($user->getGoogleHomepage())->isNull()
                ->variable($user->getBitbucketId())->isNull()
                ->variable($user->getBitbucketHomepage())->isNull();

        // Google
        $this->given($user = $this->newTestedInstance())
            ->and($metadata = new ProviderMetadata(
                [
                    'uid' => 'gru',
                    'homepage' => 'http://gru.gru'
                ]
            ))
            ->if($user->addProviderMetadata('google', $metadata))
            ->then
                ->boolean($user->hasProviderMetadata('google'))->isTrue()
                ->string($user->getGoogleId())->isNotEmpty()->isEqualTo('gru')
                ->string($user->getGoogleHomepage())->isNotEmpty()->isEqualTo('http://gru.gru')
                ->object($user->getProviderMetadata('google'))->isEqualTo($metadata)
                ->variable($user->getGithubId())->isNull()
                ->variable($user->getGithubHomepage())->isNull()
                ->variable($user->getBitbucketId())->isNull()
                ->variable($user->getBitbucketHomepage())->isNull();

        // Bitbucket
        $this->given($user = $this->newTestedInstance())
            ->and($metadata = new ProviderMetadata(
                [
                    'uid' => 'git',
                    'homepage' => 'http://git.io'
                ]
            ))
            ->if($user->addProviderMetadata('bitbucket', $metadata))
            ->then
                ->boolean($user->hasProviderMetadata('bitbucket'))->isTrue()
                ->string($user->getBitbucketId())->isNotEmpty()->isEqualTo('git')
                ->string($user->getBitbucketHomepage())->isNotEmpty()->isEqualTo('http://git.io')
                ->object($user->getProviderMetadata('bitbucket'))->isEqualTo($metadata)
                ->variable($user->getGithubId())->isNull()
                ->variable($user->getGithubHomepage())->isNull()
                ->variable($user->getGoogleId())->isNull()
                ->variable($user->getGoogleHomepage())->isNull();
    }

    /**
     * @test
     */
    public function testExtensions()
    {
        /* @var $user \PickleWeb\Entity\User */

        $this->given($user = $this->newTestedInstance())
            ->then
                ->array($user->getExtensions())->isEmpty();

        $this->given($user = $this->newTestedInstance())
            ->if($user->addExtension('foobar'))
            ->then
                ->integer(count($user->getExtensions()))->isEqualTo(1)
                ->array($user->getExtensions())->isEqualTo(['foobar']);

        $this->given($user = $this->newTestedInstance())
            ->and($user->addExtension('foobar'))
            ->if($user->removeExtension('foobar'))
            ->then
                ->array($user->getExtensions())->isEmpty();
    }

    /**
     * @test
     */
    public function testSerialise()
    {
        /* @var $user \PickleWeb\Entity\User */
        /* @var $newUser \PickleWeb\Entity\User */

        $this->given($user = $this->newTestedInstance())
            ->and($user->setEmail('foo@bar.com'))
            ->and($serializedUser = $user->serialize())
            ->and($newUser = $this->newTestedInstance())
            ->if($newUser->unserialize($serializedUser))
            ->then
                ->string($newUser->getEmail())->isNotEmpty()->isEqualTo($user->getEmail());
    }
}

/* vim: set tabstop=4 shiftwidth=4 expandtab: fdm=marker */
