<?php

namespace DingoTipPOC;

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Aws\Result;

class CognitoSMSPasswordless
{

    private CognitoIdentityProviderClient $cognitoClient;
    private array $config;

    public function __construct()
    {

        $this->config = parse_ini_file(__DIR__ . '/../config.ini');
        $this->cognitoClient = new CognitoIdentityProviderClient([
            'region' => $this->config['region'],
            'version' => 'latest',
            'credentials' => [
                'key' => $this->config['access_key_id'],
                'secret' => $this->config['secret_key_id'],
            ]
        ]);
    }

    //This can potentially create two users with same mobile number, not good
    public function createUser(string $mobile): void
    {
        /*$this->cognitoClient->adminCreateUser(
            [
                'Username' => $mobile,
                'UserPoolId' => $this->config['user_pool_id'],
                'MessageAction' => 'SUPPRESS'
            ]);*/
        $this->cognitoClient->signUp([
            'Username' => $mobile,
            'UserPoolId' => $this->config['user_pool_id'],
            'MessageAction' => 'SUPPRESS'
        ]);
    }

    public function initAuth(string $mobile, bool $retry = true): string
    {
        try {
            $response = $this->cognitoClient->initiateAuth([
                'AuthFlow' => 'CUSTOM_AUTH',
                'AuthParameters' => ['USERNAME' => $mobile],
                'ClientId' => $this->config['client_id']
            ]);
            return $response->get('Session');

        } catch (CognitoIdentityProviderException $e) {
            //If user is not found, then create user, and retry initAuth
            if ($this->isUserNotFoundException($e) && $retry) {
                $this->createUser($mobile);
                return $this->initAuth($mobile, false);
            } else {
                throw $e;
            }
        }
    }

    public function verifyCode(string $code, string $mobile, string $session): Result
    {

        try {
            return $this->cognitoClient->respondToAuthChallenge([
                'ChallengeName' => 'CUSTOM_CHALLENGE',
                'ChallengeResponses' => ['ANSWER' => $code, 'USERNAME' => $mobile],
                'Session' => $session,
                'ClientId' => $this->config['client_id']
            ]);
        } catch (\Throwable $e) {
            var_dump($e);
            exit;
        }

    }

    private function isUserNotFoundException(CognitoIdentityProviderException $e): bool
    {
        $body = json_decode((string)$e->getResponse()->getBody(), false);
        return $body->__type === 'UserNotFoundException';
    }

    public function redirectTo(string $key, array $params): never
    {
        $url = 'http://localhost:8106/';
        $params[$key] = 1;
        $url = $url . '?' . http_build_query($params);

        header('Location: ' . $url);
        exit;
    }
}