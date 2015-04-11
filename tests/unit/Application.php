<?php

namespace PickleWeb\Tests\Unit;

use atoum;

class Application extends atoum
{
    public function test__construct()
    {
        $this
            ->object($this->newTestedInstance(new \mock\Slim\Slim))->isInstanceOfTestedClass
        ;
    }

    public function testRedirectIf()
    {
        $this
            ->given(
                $application = new \mock\Slim\Slim,
                $this->calling($application)->redirect = null
            )
            ->if($this->newTestedInstance($application))
            ->then
                ->object($this->testedInstance->redirectIf(false, uniqid()))->isTestedInstance
                ->mock($application)
                    ->call('redirect')->never
                ->object($this->testedInstance->redirectIf(true, $url = uniqid()))->isTestedInstance
                ->mock($application)
                    ->call('redirect')->withArguments($url)->once
        ;
    }

    public function testRedirectUnless()
    {
        $this
            ->given(
                $application = new \mock\Slim\Slim,
                $this->calling($application)->redirect = null
            )
            ->if($this->newTestedInstance($application))
            ->then
                ->object($this->testedInstance->redirectUnless(true, uniqid()))->isTestedInstance
                ->mock($application)
                    ->call('redirect')->never
                ->object($this->testedInstance->redirectUnless(false, $url = uniqid()))->isTestedInstance
                ->mock($application)
                    ->call('redirect')->withArguments($url)->once
        ;
    }

    public function testNotFoundIf()
    {
        $this
            ->given(
                $application = new \mock\Slim\Slim,
                $this->calling($application)->notFound = null
            )
            ->if($this->newTestedInstance($application))
            ->then
                ->object($this->testedInstance->notFoundIf(false, uniqid()))->isTestedInstance
                ->mock($application)
                    ->call('notFound')->never
                ->object($this->testedInstance->notFoundIf(true, $url = uniqid()))->isTestedInstance
                ->mock($application)
                    ->call('notFound')->once
        ;
    }

    public function testRenderError()
    {
        $this
            ->given(
                $application = new \mock\Slim\Slim,
                $response = new \mock\Slim\Http\Response,
                $this->calling($application)->render = null,
                $this->calling($application)->stop = null,
                $this->calling($application)->response = $response,
                $this->calling($response)->status = null
            )
            ->if($this->newTestedInstance($application))
            ->then
                ->object($this->testedInstance->renderError($code = rand(0, PHP_INT_MAX)))->isTestedInstance
                ->mock($application)
                    ->call('render')->withArguments('errors/' . $code . '.html')
                        ->before(
                            $this->mock($response)->call('status')->withArguments($code)
                                ->before($this->mock($application)->call('stop')->once)
                            ->once
                        )
                    ->once
        ;
    }

    public function testSetViewData()
    {
        $this
            ->given(
                $application = new \mock\Slim\Slim,
                $view = new \mock\Slim\View,
                $this->calling($application)->view = $view
            )
            ->if($this->newTestedInstance($application))
            ->then
                ->object($this->testedInstance->setViewData($data = ['foo' => 'bar']))->isTestedInstance
                ->mock($view)
                    ->call('setData')->withArguments($data)->once
        ;
    }

    public function testRun()
    {
        $this
            ->given(
                $application = new \mock\Slim\Slim,
                $this->calling($application)->run = null
            )
            ->if($this->newTestedInstance($application))
            ->then
                ->object($this->testedInstance->run())->isTestedInstance
                ->mock($application)
                    ->call('run')->once
        ;
    }
} 
