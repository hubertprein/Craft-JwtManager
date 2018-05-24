# Craft-JwtManager

_Manage JWTs for users which can be used to login._

## Requirements

This plugin requires Craft CMS 3.0.0-RC1 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require hubertprein/craft-jwtmanager

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for JWT Manager.

## Functionality

Use JWT manager to enable auto login features for your websites and mobile apps using JWTs.

You can also use it as a sort of *framework* to manually call the JWT and auto login features.

When do you use it? Your mobile apps requests data which needs the user to login with every request that they do.
While you should never send the username and password everytime, one should use a JWT.
Bonus to it is that logging in using a JWT, is much faster.

## Available actions

* Login returns JSON with a `token`, `refreshToken` and `user` (filled with user information of the logged in user). You can send credentials here filled with `username` and `password`. You can send a JWT with a `Authorization Bearer` header.
`https://www.yourdomainhere.nl/actions/jwt-manager/auth/login`

* `https://www.yourdomainhere.nl/actions/jwt-manager/auth/logout`
* Send a refresh token in a `Authorization Bearer` header. Which will return a new JWT based on the refresh token.
`https://www.yourdomainhere.nl/actions/jwt-manager/tokens/use-refresh`
