<?php

namespace PickleWeb\tests\unit;

use atoum;

/**
 * Class Application.
 */
class Application extends atoum
{
    public function test__construct()
    {
        $this->object($this->newTestedInstance([]))
            ->isInstanceOf('Slim\Slim')
            ->isInstanceOf('RKA\Slim');
    }

    public function testRedirectIf()
    {
        $this->mockGenerator->shunt('redirect');
        /* @var $applicationMock \PickleWeb\Application */

        $this->given($applicationMock = new \mock\PickleWeb\Application([]))
            ->if($applicationMock->redirectIf(false, uniqid()))
            ->then
                ->mock($applicationMock)
                ->call('redirect')->never
            ->if($applicationMock->redirectIf(true, $url = uniqid()))
            ->then
                ->mock($applicationMock)
                ->call('redirect')->withArguments($url)->once;
    }

    public function testRedirectUnless()
    {
        $this->mockGenerator->shunt('redirect');
        /* @var $applicationMock \PickleWeb\Application */

        $this->given($applicationMock = new \mock\PickleWeb\Application([]))
            ->if($applicationMock->redirectUnless(true, uniqid()))
            ->then
                ->mock($applicationMock)
                ->call('redirect')->never
            ->if($applicationMock->redirectUnless(false, $url = uniqid()))
            ->then
                ->mock($applicationMock)
                ->call('redirect')->withArguments($url)->once;
    }

    public function testNotFoundIf()
    {
        $this->mockGenerator->shunt('notFound');
        /* @var $applicationMock \PickleWeb\Application */

        $this->given($applicationMock = new \mock\PickleWeb\Application([]))
            ->if($applicationMock->notFoundIf(false))
            ->then
                ->mock($applicationMock)
                ->call('notFound')->never
            ->if($applicationMock->notFoundIf(true))
            ->then
                ->mock($applicationMock)
                ->call('notFound')->once;
    }

    public function testRenderError()
    {
        /* @var $applicationMock \PickleWeb\Application */

        $this->given(
            $applicationMock = new \mock\PickleWeb\Application([]),
            $responseMock = new \mock\Slim\Http\Response(),
            $this->calling($applicationMock)->render = null,
            $this->calling($applicationMock)->stop = null,
            $this->calling($applicationMock)->response = $responseMock,
            $this->calling($responseMock)->status = null
        )
            ->if($applicationMock->renderError($code = rand(0, PHP_INT_MAX)))
            ->then
                ->mock($applicationMock)
                ->call('render')->withArguments('errors/'.$code.'.html')
                ->before(
                    $this->mock($responseMock)->call('status')->withArguments($code)
                        ->before($this->mock($applicationMock)->call('stop')->once)
                        ->once
                )
                ->once;
    }

    public function testRender()
    {
        /* @var $applicationMock \PickleWeb\Application */

        $this->given(
            $viewMock = new \mock\Slim\View(),
            $applicationMock = new \mock\PickleWeb\Application(['view' => $viewMock]),
            $this->calling($viewMock)->display = null
        )
            ->if($applicationMock->render('index.html', $data = ['foo' => 'bar']))
            ->then
                ->mock($viewMock)
                ->call('appendData')->withArguments(array_merge(['user' => null], $data))->once;
    }
}
