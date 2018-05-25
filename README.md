# Craft-JwtManager

_Manage JWTs for users which can be used to login._

## Requirements

This plugin requires Craft CMS 2.6.3005 or later.

## Installation

To install JWT Manager, follow these steps:

1. Download & unzip the file in a new `jwtmanager` directory into your `craft/plugins` directory.
2.  -OR- do a `git clone https://github.com/hubertprein/Craft-JwtManager.git` directly into your `craft/plugins` folder.  You can then update it with `git pull`.
3. In the Control Panel, go to Settings → Plugins and click the “Install” button for JWT Manager.

## Functionality

Use JWT manager to enable auto login features for your websites and mobile apps using JWTs.

You can also use it as a sort of *framework* to manually call the JWT and auto login features.

When do you use it? Your mobile apps requests data which needs the user to login with every request that they do.
While you should never send the username and password everytime, one should use a JWT.
Bonus to it is that logging in using a JWT, is much faster.

## Available actions

* Login returns JSON with a `token`, `refreshToken` and `user` (filled with user information of the logged in user). You can send credentials here filled with `username` and `password`. You can send a JWT with a `Authorization Bearer` header.
`https://www.yourdomainhere.nl/actions/jwtManager/auth/login`

* `https://www.yourdomainhere.nl/actions/jwtManager/auth/logout`
* Send a refresh token in a `Authorization Bearer` header. Which will return a new JWT based on the refresh token.
`https://www.yourdomainhere.nl/actions/jwtManager/tokens/useRefresh`

## Basic Example

You could use this in elementapi.php and don't return any endpoints when the user isn't logged in.

```php
$error = '';
$token = null;
$refreshToken = null;

$jwtManager = craft()->plugins->getPlugin('jwtmanager');
if ($jwtManager) {
    // Let's see if we can login!
    if (craft()->jwtManager_login->auto()) {
        // This can be used to login
        $token = craft()->jwtManager_login->getToken();

        // Refresh token can be used to refresh the login token
        // Although! It is only available when a new JWT was generated. So save this somewhere.
        $refreshToken = craft()->jwtManager_login->getRefreshToken();
    } else {
        // Can we find a JWT based on the current user?
        $user = craft()->userSession->getUser();
        if ($user) {
            // Well, we do have a user it seems.
            $jwt = craft()->jwtManager_jwts->getOneJwtForUser($user, JwtManager_JwtModel::TYPE_LOGIN);
            $token = $jwt ? $jwt->token : null;
        } else {
            // Error occurred..
            $error = craft()->jwtManager_login->getError();
        }
    }
}

// Logged in user?
$user = craft()->userSession->getUser();
if (!$user) {
    // Don't return any data.
    // End request or whatever..
    // Possibly use the $error that could be filled with a message when JWT Manager was used.
}
```

## New BaseController for your plugin

You could use this as a new *BaseController* for your plugin, and when your own controller actions are called, they will use this controller to ensure that it always requires a user.

```php
<?php
namespace Craft;

/**
 * YourPlugin Base controller.
 */
class YourPlugin_BaseController extends BaseController
{
    /**
     * @var UserModel Current logged in user.
     */
    protected $currentUser;

    /**
     * @var array Found token from auto login.
     */
    protected $token;

    /**
     * @var array Found refresh token from auto login.
     */
    protected $refreshToken;

    /**
     * @var bool Allow all requests as we will ensure that we require a user.
     */
    protected $allowAnonymous = true;

    /**
     * Init controller.
     */
    public function init()
    {
        parent::init();

        $autoLoginError = null;
        $jwtManager = craft()->plugins->getPlugin('jwtmanager');
        if ($jwtManager) {
            // Let's see if we can login!
            if (craft()->jwtManager_login->auto()) {
                // This can be used to login
                $this->token = craft()->jwtManager_login->getToken();

                // Refresh token can be used to refresh the login token
                // Although! It is only available when a new JWT was generated. So save this somewhere.
                $this->refreshToken = craft()->jwtManager_login->getRefreshToken();
            } else {
                // Can we find a JWT based on the current user?
                $user = craft()->userSession->getUser();
                if ($user) {
                    // Well, we do have a user it seems.
                    $jwt = craft()->jwtManager_jwts->getOneJwtForUser($user, JwtManager_JwtModel::TYPE_LOGIN);
                    $this->token = $jwt ? $jwt->token : null;
                } else {
                    // Error occurred..
                    $autoLoginError = craft()->jwtManager_login->getError();
                }
            }
        }

        // Logged in user?
        $this->currentUser = craft()->userSession->getUser();
        if (!$this->currentUser) {
            $this->terminate(($autoLoginError ? $autoLoginError : Craft::t('Not logged in.')));
        }
    }

    /**
     * Terminate the application.
     *
     * @param string $message
     * @param int    $status  [Optional] HTTP status code.
     */
    public function terminate($message, $status = 401)
    {
        // Find our server protocol
        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');

        // Display error message
        header($protocol . ' ' . $status . ' ' . $message);
        echo $message;
        craft()->end();
    }
}
```
