<?php
namespace PickleWeb\Action;
use \OAuth\OAuth2\Service\GitHub as Github;
use \OAuth\Common\Storage\Session as Session;
use \OAuth\Common\Consumer\Credentials as Credentials;
use \OAuth\Common\Http\Uri\Uri as Uri;
use \OAuth\Common\Http\Uri\UriFactory as UriFactory;

class AuthGithub {
	protected $app;

	function __construct(\Slim\Slim $app) {
		$this->app = $app;	
	}

	function login() {
		$storage = new Session();

		$code = $this->app->request->get('code');
		$go = $this->app->request->get('go');

		if (empty($go) && empty($code)) {
			$token = $storage->retrieveAccessToken('github')->getAccessToken();
			if (!empty($token)) {
				$this->app->redirect('/');
			}
		}

		$servicesCredentials['github'] = [
			'key' => getenv('GITHUB_CLIENT_ID'),
			'secret' => getenv('GITHUB_CLIENT_SECRET')
		];

		$uriFactory = new UriFactory();
		$currentUri = $uriFactory->createFromSuperGlobalArray($_SERVER);

		$credentials = new Credentials(
					$servicesCredentials['github']['key'],
					$servicesCredentials['github']['secret'],
					$currentUri->getAbsoluteUri()
				);

		$serviceFactory = new \OAuth\ServiceFactory();
		$gitHub = $serviceFactory->createService('GitHub', $credentials, $storage, array('user:email', 'read:repo_hook'));
		
		if ($code) {
			/* This was a callback request from github, get the token */
			$token = $gitHub->requestAccessToken($code);
			$result = json_decode($gitHub->request('user/emails'), true);
			
			$storage->storeAccessToken('github', $token);

			$result = json_decode($gitHub->request('user'), true);
			$result['email'] = json_decode($gitHub->request('user/emails'), true)[0];

			$this->app->view()->setData([
				'github_content' => 'Github email: ' . $result['email'] . "\n" . 
									'Username: ' . $result['login']
			]);
			$this->app->render('registergithub.html');
			$_SESSION['user'] = $result;
			$_SESSION['token'] = $token;
			$jsonPath = $this->app->config('json_path') . 'users/github/' . $result['login'] . '.json';

			if (!file_exists($jsonPath)) {
				file_put_contents($jsonPath, json_encode($result, JSON_PRETTY_PRINT));
			}
			header('Location: ' . /);
		} else {
			if (!empty($go) && $go === 'go') {
				$url = $gitHub->getAuthorizationUri();
				header('Location: ' . $url);
			} else {
				$this->app->redirect('/');
			}
		}
	}
}
