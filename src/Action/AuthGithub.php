<?php
namespace PickleWeb\Action;
use \OAuth\OAuth2\Service\GitHub as Github;
use \OAuth\Common\Storage\Session as Session;
use \OAuth\Common\Consumer\Credentials as Credentials;
use \OAuth\Common\Http\Uri\Uri as Uri;
use \OAuth\Common\Http\Uri\UriFactory as UriFactory;

class AuthGithub {
	function login() {
		$storage = new Session();
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
		if (!empty($_GET['code'])) {
			// This was a callback request from github, get the token
			$gitHub->requestAccessToken($_GET['code']);
			$result = json_decode($gitHub->request('user/emails'), true);
			echo 'The first email on your github account is ' . $result[0];
		} elseif (!empty($_GET['go']) && $_GET['go'] === 'go') {
			$url = $gitHub->getAuthorizationUri();
			header('Location: ' . $url);
		} else {
			$url = $currentUri->getRelativeUri() . '?go=go';
			echo "<a href='$url'>Login with Github!</a>";
		}
	}
}
