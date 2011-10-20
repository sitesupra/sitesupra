<?php

use Supra\ObjectRepository\ObjectRepository;
use Project\AutoregisterAuthenticationAdapter\AutoregisterAuthenticationAdapter;
use Supra\User\UserProvider;
use Supra\User\Validation\EmailValidation;

$userProvider = new UserProvider();

$userProvider->addValidationFilter(new EmailValidation());
$userProvider->setAuthAdapter(new AutoregisterAuthenticationAdapter());

// This is provider for CMS
//ObjectRepository::setUserProvider('Supra\Cms', $userProvider);

// Experimental: set by ID
ObjectRepository::setUserProvider('#cms', $userProvider);

// Experimental: added extra rules for controllers
ObjectRepository::setUserProvider('Supra\Cms\CmsController', $userProvider);
ObjectRepository::setUserProvider('Project\Authentication\AuthenticationPreFilterController', $userProvider);
ObjectRepository::setUserProvider('Project\SampleAuthentication\AuthenticateController', $userProvider);
ObjectRepository::setUserProvider('Project\SampleAuthentication\AuthenticatePreFilterController', $userProvider);
