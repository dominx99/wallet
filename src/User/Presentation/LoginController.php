<?php declare (strict_types = 1);

namespace Wallet\User\Presentation;

use Overtrue\Socialite\SocialiteManager;
use Slim\Http\Request;
use Slim\Http\Response;
use Wallet\System\Responses\ValidationFail;
use Wallet\System\System;
use Wallet\User\Application\Exception\NotFoundSocialUserException;
use Wallet\User\Application\FindUserByEmail;
use Wallet\User\Application\GetSocialUserByAccessTokenAndProvider;
use Wallet\User\Application\LoginSocial;
use Wallet\User\Application\LoginStandard;
use Wallet\User\Application\RegisterSocial;
use Wallet\User\Application\Validation\ProviderAuthValidator;
use Wallet\User\Responses\LoginFail;

class LoginController
{
    /**
     * @var \Wallet\System\System
     */
    protected $system;

    /**
     * @var \Overtrue\Socialite\SocialiteManager
     */
    protected $socialite;

    /**
     * @var \Wallet\User\Application\Validation\ProviderAuthValidator
     */
    protected $providerValidator;

    /**
     * @param \Wallet\System\System $system
     * @param \Overtrue\Socialite\SocialiteManager $socialite
     * @param \Wallet\User\Application\Validation\ProviderAuthValidator $providerValidator
     */
    public function __construct(
        System $system,
        SocialiteManager $socialite,
        ProviderAuthValidator $providerValidator
    ) {
        $this->system            = $system;
        $this->socialite         = $socialite;
        $this->providerValidator = $providerValidator;
    }

    /**
     * @param  \Slim\Http\Request $request
     * @return \Slim\Http\Response
     */
    public function login(Request $request): Response
    {
        $params = $request->getParams();

        $query = new LoginStandard($params['email'], $params['password']);

        $result = $this->system->execute($query);

        return $result->toResponse();
    }

    /**
     * @param \Slim\Http\Request $request
     * @param string $provider
     * @return \Slim\Http\Response
     */
    public function loginByProvider(Request $request, string $provider): Response
    {
        $validation = $this->providerValidator->validate($request->getParams());

        if ($validation->failed()) {
            return (new ValidationFail($validation->getErrors()))->toResponse();
        }

        try {
            $socialUser = $this->system->execute(
                new GetSocialUserByAccessTokenAndProvider($request->getParam('access_token'), $provider)
            );
        } catch (NotFoundSocialUserException $e) {
            return (new LoginFail($e->getMessage()))->toResponse();
        }

        $user = $this->system->execute(new FindUserByEmail($socialUser->getEmail()));

        if (!$user) {
            $this->system->handle(new RegisterSocial($socialUser));
        }

        $result = $this->system->execute(new LoginSocial($socialUser));

        return $result->toResponse();
    }
}
