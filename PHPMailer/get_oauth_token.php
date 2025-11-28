<?php

/**
 * PHPMailer - PHP email creation and transport class.
 * PHP Version 5.5
 * @package PHPMailer
 * @see https://github.com/PHPMailer/PHPMailer/ The PHPMailer GitHub project
 * @author Marcus Bointon (Synchro/coolbru) <phpmailer@synchromedia.co.uk>
 * @author Jim Jagielski (jimjag) <jimjag@gmail.com>
 * @author Andy Prevost (codeworxtech) <codeworxtech@users.sourceforge.net>
 * @author Brent R. Matzelle (original founder)
 * @copyright 2012 - 2020 Marcus Bointon
 * @copyright 2010 - 2012 Jim Jagielski
 * @copyright 2004 - 2009 Andy Prevost
 * @license https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * Get an OAuth2 token from an OAuth2 provider.
 * * Install this script on your server so that it's accessible
 * as [https/http]://<yourdomain>/<folder>/get_oauth_token.php
 * e.g.: http://localhost/phpmailer/get_oauth_token.php
 * * Ensure dependencies are installed with 'composer install'
 * * Set up an app in your Google/Yahoo/Microsoft account
 * * Set the script address as the app's redirect URL
 * If no refresh token is obtained when running this file,
 * revoke access to your app and run the script again.
 */

namespace PHPMailer\PHPMailer;

// Ensure this script is being accessed securely
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    die('This script must be accessed over HTTPS');
}

// Basic security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\';');

// Start session with secure settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

/**
 * Aliases for League Provider Classes
 * Make sure you have added these to your composer.json and run `composer install`
 * Plenty to choose from here:
 * @see https://oauth2-client.thephpleague.com/providers/thirdparty/
 */
//@see https://github.com/thephpleague/oauth2-google
use League\OAuth2\Client\Provider\Google;
//@see https://packagist.org/packages/hayageek/oauth2-yahoo
use Hayageek\OAuth2\Client\Provider\Yahoo;
//@see https://github.com/stevenmaguire/oauth2-microsoft
use Stevenmaguire\OAuth2\Client\Provider\Microsoft;
//@see https://github.com/greew/oauth2-azure-provider
use Greew\OAuth2\Client\Provider\Azure;

require 'vendor/autoload.php';

session_start();

// CSRF protection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
        die('CSRF token validation failed');
    }
}

$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

if (!isset($_GET['code']) && !isset($_POST['provider'])) {
?>
    <html>

    <head>
        <title>OAuth2 Token Generator</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 800px;
                margin: 20px auto;
                padding: 20px;
            }

            .container {
                background: #f5f5f5;
                padding: 20px;
                border-radius: 5px;
            }

            input[type="text"] {
                width: 100%;
                padding: 8px;
                margin: 5px 0;
            }

            input[type="submit"] {
                background: #4CAF50;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }

            .warning {
                color: #f44336;
            }
        </style>
    </head>

    <body>
        <div class="container">
            <form method="post">
                <h1>Select Provider</h1>
                <input type="radio" name="provider" value="Google" id="providerGoogle" required>
                <label for="providerGoogle">Google</label><br>
                <input type="radio" name="provider" value="Yahoo" id="providerYahoo">
                <label for="providerYahoo">Yahoo</label><br>
                <input type="radio" name="provider" value="Microsoft" id="providerMicrosoft">
                <label for="providerMicrosoft">Microsoft</label><br>
                <input type="radio" name="provider" value="Azure" id="providerAzure">
                <label for="providerAzure">Azure</label><br>

                <h1>Enter id and secret</h1>
                <p>These details are obtained by setting up an app in your provider's developer console.</p>
                <p class="warning">Warning: Keep these credentials secure and never share them publicly.</p>

                <p>ClientId: <input type="text" name="clientId" required pattern="[A-Za-z0-9\-_\.]+"></p>
                <p>ClientSecret: <input type="text" name="clientSecret" required pattern="[A-Za-z0-9\-_\.]+"></p>
                <p>TenantID (only for Azure): <input type="text" name="tenantId" pattern="[A-Za-z0-9\-]+"></p>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="submit" value="Continue">
            </form>
        </div>
    </body>

    </html>
<?php
    exit;
}

$providerName = '';
$clientId = '';
$clientSecret = '';
$tenantId = '';

// Validate and sanitize inputs
if (array_key_exists('provider', $_POST)) {
    $validProviders = ['Google', 'Yahoo', 'Microsoft', 'Azure'];
    $providerName = in_array($_POST['provider'], $validProviders) ? $_POST['provider'] : die('Invalid provider');

    // Validate client ID and secret format
    if (
        !preg_match('/^[A-Za-z0-9\-_\.]+$/', $_POST['clientId']) ||
        !preg_match('/^[A-Za-z0-9\-_\.]+$/', $_POST['clientSecret'])
    ) {
        die('Invalid client credentials format');
    }

    $clientId = $_POST['clientId'];
    $clientSecret = $_POST['clientSecret'];
    $tenantId = isset($_POST['tenantId']) ? preg_replace('/[^A-Za-z0-9\-]/', '', $_POST['tenantId']) : '';

    $_SESSION['provider'] = $providerName;
    $_SESSION['clientId'] = $clientId;
    $_SESSION['clientSecret'] = $clientSecret;
    $_SESSION['tenantId'] = $tenantId;
} elseif (array_key_exists('provider', $_SESSION)) {
    $providerName = $_SESSION['provider'];
    $clientId = $_SESSION['clientId'];
    $clientSecret = $_SESSION['clientSecret'];
    $tenantId = $_SESSION['tenantId'];
}

//If you don't want to use the built-in form, set your client id and secret here
//$clientId = 'RANDOMCHARS-----duv1n2.apps.googleusercontent.com';
//$clientSecret = 'RANDOMCHARS-----lGyjPcRtvP';

//If this automatic URL doesn't work, set it yourself manually to the URL of this script
$redirectUri = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
//$redirectUri = 'http://localhost/PHPMailer/redirect';

$params = [
    'clientId' => $clientId,
    'clientSecret' => $clientSecret,
    'redirectUri' => $redirectUri,
    'accessType' => 'offline'
];

$options = [];
$provider = null;

    switch ($providerName) {
        case 'Google':
            $provider = new League\OAuth2\Client\Provider\Google($params);
            $options = [
                'scope' => [
                    'https://mail.google.com/'
                ]
            ];
            break;
            
        case 'Azure':
            $params['tenantId'] = $tenantId;
            $provider = new Azure($params);
            $options = [
                'scope' => [
                'https://outlook.office.com/SMTP.Send',
                'offline_access'
            ]
        ];
        break;
}

if (null === $provider) {
    exit('Provider missing');
}

if (!isset($_GET['code'])) {
    //If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl($options);
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: ' . $authUrl);
    exit;
    //Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
    unset($_SESSION['oauth2state']);
    unset($_SESSION['provider']);
    exit('Invalid state');
} else {
    unset($_SESSION['provider']);
    //Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken(
        'authorization_code',
        [
            'code' => $_GET['code']
        ]
    );
    //Use this to interact with an API on the users behalf
    //Use this to get a new access token if the old one expires
    echo 'Refresh Token: ', htmlspecialchars($token->getRefreshToken());
}
