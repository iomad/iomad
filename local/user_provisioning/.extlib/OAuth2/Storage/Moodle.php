<?php

namespace OAuth2\Storage;

use OAuth2\OpenID\Storage\UserClaimsInterface;
use OAuth2\OpenID\Storage\AuthorizationCodeInterface as OpenIDAuthorizationCodeInterface;

/**
 * Simple PDO storage for all storage types
 *
 * NOTE: This class is meant to get users started
 * quickly. If your application requires further
 * customization, extend this class or create your own.
 *
 * NOTE: Passwords are stored in plaintext, which is never
 * a good idea.  Be sure to override this for your application
 *
 * @author Brent Shaffer <bshafs at gmail dot com>
 */
class Moodle implements
    AuthorizationCodeInterface,
    AccessTokenInterface,
    ClientCredentialsInterface,
    UserCredentialsInterface,
    RefreshTokenInterface,
    JwtBearerInterface,
    ScopeInterface,
    PublicKeyInterface,
    UserClaimsInterface,
    OpenIDAuthorizationCodeInterface
{
    protected $config;

    public function __construct($connection, $config = array())
    {
        $this->config = $config;
    }

    /* OAuth2\Storage\ClientCredentialsInterface */
    public function checkClientCredentials($client_id, $client_secret = null)
    {
        global $DB;
        $client_secret_db = $DB->get_field('oauth_clients', 'client_secret', array('client_id' => $client_id));
        return $client_secret == $client_secret_db;
    }

    public function isPublicClient($client_id)
    {
        global $DB;
        $client = $DB->get_record('oauth_clients', array('client_id' => $client_id));
        if (!$client) {
            return false;
        }
        return empty($client->client_secret);
    }

    /* OAuth2\Storage\ClientInterface */
    public function getClientDetails($client_id)
    {
        global $DB;
        $client = $DB->get_record('oauth_clients', array('client_id' => $client_id));
        if (!$client) {
            return false;
        }
        unset($client->id);
        return (array) $client;
    }

    public function setClientDetails($client_id, $client_secret = null, $redirect_uri = null, $grant_types = null, $scope = null, $user_id = null)
    {
        global $DB;
        if ($client = $DB->get_record('oauth_clients', array('client_id' => $client_id))) {
            $client->client_secret = $client_secret;
            $client->redirect_uri = $redirect_uri;
            $client->grant_types = $grant_types;
            $client->scope = $scope;
            $client->user_id = $user_id;
            $DB->update_record('oauth_clients', $client);
        } else {
            $client = new \StdClass();
            $client->client_secret = $client_secret;
            $client->redirect_uri = $redirect_uri;
            $client->grant_types = $grant_types;
            $client->scope = $scope;
            $client->user_id = $user_id;
            $client->client_id = $client_id;
            $DB->insert_record('oauth_clients', $client);
        }

        return true;
    }

    public function checkRestrictedGrantType($client_id, $grant_type)
    {
        $details = $this->getClientDetails($client_id);
        if (isset($details['grant_types'])) {
            $grant_types = explode(' ', $details['grant_types']);

            return in_array($grant_type, (array) $grant_types);
        }

        // if grant_types are not defined, then none are restricted
        return true;
    }

    /* OAuth2\Storage\AccessTokenInterface */
    public function getAccessToken($access_token)
    {
        global $DB;
        $token = $DB->get_record('oauth_access_tokens', array('access_token' => $access_token));
        if (!$token) {
            return false;
        }
        unset($token->id);
        return (array) $token;
    }

    public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null)
    {
        global $DB;

        // if it exists, update it.
        if ($token = $DB->get_record('oauth_access_tokens', array('access_token' => $access_token))) {
            $token->client_id = $client_id;
            $token->expires = $expires;
            $token->user_id = $user_id;
            $token->scope = $scope;
            $DB->update_record('oauth_access_tokens', $token);
        } else {
            $token = new \StdClass();
            $token->client_id = $client_id;
            $token->expires = $expires;
            $token->user_id = $user_id;
            $token->scope = $scope;
            $token->access_token = $access_token;
            $DB->insert_record('oauth_access_tokens', $token);
        }
        return true;
    }

    /* OAuth2\Storage\AuthorizationCodeInterface */
    public function getAuthorizationCode($code)
    {
        global $DB;
        $code = $DB->get_record('oauth_authorization_codes', array('authorization_code' => $code));
        if (!$code) {
            return false;
        }
        unset($code->id);
        return (array) $code;
    }

    public function setAuthorizationCode($code, $client_id, $user_id, $redirect_uri, $expires, $scope = null, $id_token = null)
    {
        global $DB;
        if (func_num_args() > 6) {
            // we are calling with an id token
            return call_user_func_array(array($this, 'setAuthorizationCodeWithIdToken'), func_get_args());
        }

        // if it exists, update it.
        if ($auth_code = $DB->get_record('oauth_authorization_codes', array('authorization_code' => $code))) {
            $auth_code->client_id = $client_id;
            $auth_code->user_id = $user_id;
            $auth_code->redirect_uri = $redirect_uri;
            $auth_code->expires = $expires;
            $auth_code->scope = $scope;
            $DB->update_record('oauth_authorization_codes', $auth_code);
        } else {
            $auth_code = new \StdClass();
            $auth_code->client_id = $client_id;
            $auth_code->user_id = $user_id;
            $auth_code->redirect_uri = $redirect_uri;
            $auth_code->expires = $expires;
            $auth_code->scope = $scope;
            $auth_code->authorization_code = $code;
            $DB->insert_record('oauth_authorization_codes', $auth_code);
        }
        return true;
    }

    private function setAuthorizationCodeWithIdToken($code, $client_id, $user_id, $redirect_uri, $expires, $scope = null, $id_token = null)
    {
        global $DB;

        // if it exists, update it.
        if ($auth_code = $DB->get_record('oauth_authorization_codes', array('authorization_code' => $code))) {
            $auth_code->client_id = $client_id;
            $auth_code->user_id = $user_id;
            $auth_code->redirect_uri = $redirect_uri;
            $auth_code->expires = $expires;
            $auth_code->scope = $scope;
            $auth_code->id_token = $id_token;
            $DB->update_record('oauth_authorization_codes', $auth_code);
        } else {
            $auth_code = new \StdClass();
            $auth_code->client_id = $client_id;
            $auth_code->user_id = $user_id;
            $auth_code->redirect_uri = $redirect_uri;
            $auth_code->expires = $expires;
            $auth_code->scope = $scope;
            $auth_code->id_token = $id_token;
            $auth_code->authorization_code = $code;
            $DB->insert_record('oauth_authorization_codes', $auth_code);
        }
        return true;
    }

    public function expireAuthorizationCode($code)
    {
        global $DB;
        return $DB->delete_records('oauth_authorization_codes', array('authorization_code' => $code));
    }

    /* OAuth2\Storage\UserCredentialsInterface */
    public function checkUserCredentials($username, $password)
    {
        if ($user = $this->getUser($username)) {
            return $this->checkPassword($user, $password);
        }

        return false;
    }

    public function getUserDetails($username)
    {
        return $this->getUser($username);
    }

    /* UserClaimsInterface */
    public function getUserClaims($user_id, $claims)
    {
        if (!$userDetails = $this->getUserDetails($user_id)) {
            return false;
        }

        $claims = explode(' ', trim($claims));
        $userClaims = array();

        // for each requested claim, if the user has the claim, set it in the response
        $validClaims = explode(' ', self::VALID_CLAIMS);
        foreach ($validClaims as $validClaim) {
            if (in_array($validClaim, $claims)) {
                if ($validClaim == 'address') {
                    // address is an object with subfields
                    $userClaims['address'] = $this->getUserClaim($validClaim, $userDetails['address'] ?: $userDetails);
                } else {
                    $userClaims = array_merge($userClaims, $this->getUserClaim($validClaim, $userDetails));
                }
            }
        }

        return $userClaims;
    }

    protected function getUserClaim($claim, $userDetails)
    {
        $userClaims = array();
        $claimValuesString = constant(sprintf('self::%s_CLAIM_VALUES', strtoupper($claim)));
        $claimValues = explode(' ', $claimValuesString);

        foreach ($claimValues as $value) {
            $userClaims[$value] = isset($userDetails[$value]) ? $userDetails[$value] : null;
        }

        return $userClaims;
    }

    /* OAuth2\Storage\RefreshTokenInterface */
    public function getRefreshToken($refresh_token)
    {
        global $DB;
        $token = $DB->get_record('oauth_refresh_tokens', array('refresh_token' => $refresh_token));
        if (!$token) {
            return false;
        }
        unset($token->id);
        return (array) $token;
    }

    public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = null)
    {
        global $DB;

        $token = new \StdClass();
        $token->refresh_token = $refresh_token;
        $token->client_id = $client_id;
        $token->user_id = $user_id;
        $token->expires = $expires;
        $token->scope = $scope;
        $DB->insert_record('oauth_refresh_tokens', $token);

        return true;
    }

    public function unsetRefreshToken($refresh_token)
    {
        global $DB;
        return $DB->delete_records('oauth_refresh_tokens', array('refresh_token' => $refresh_token));
    }

    // plaintext passwords are bad!  Override this for your application
    protected function checkPassword($user, $password)
    {
        $user = (object)$user;
        return validate_internal_user_password($user, $password);
    }

    public function getUser($username)
    {
        global $DB;
        $userInfo = $DB->get_record('user', array('username' => $username));
        if (!$userInfo) {
            return false;
        }
        $userInfo = (array) $userInfo;
        $userInfo['user_id'] = $username;

        return $userInfo;
    }

    public function setUser($username, $password, $firstName = null, $lastName = null)
    {
        global $DB;
        $user = $DB->get_record('user', array('username' => $username));
        if($user){
            if ($firstName) {
                $DB->set_field('user','firstname', $firstName, array('id'=>$user->id));
            }
            if ($lastName) {
                $DB->set_field('user','lastname', $lastName, array('id'=>$user->id));
            }
            update_internal_user_password($userInfo, $password);
        } else {
            $user = create_user_record($username, $password);
            if($user){
                if ($firstName) {
                    $DB->set_field('user','firstname', $firstName, array('id'=>$user->id));
                }
                if ($lastName) {
                    $DB->set_field('user','lastname', $lastName, array('id'=>$user->id));
                }
            }
        }
        return true;
    }

    /* ScopeInterface */
    public function scopeExists($scope)
    {
        global $DB;
        $scope = explode(' ', $scope);
        $whereIn = implode(',', array_fill(0, count($scope), '?'));
        $count = $DB->count_records_sql('SELECT count(scope) as count FROM {oauth_scopes} WHERE scope IN ('.$whereIn.')');

        return $count == count($scope);
    }

    public function getDefaultScope($client_id = null)
    {
        global $DB;
        $scope = $DB->get_fieldset_select('oauth_scopes','scope', 'is_default = :is_default', array('is_default'=>true));

        if ($scope) {
            return implode(' ', $scope);
        }

        return null;
    }

    /* JWTBearerInterface */
    public function getClientKey($client_id, $subject)
    {
        global $DB;
        return $DB->get_field('oauth_jwt', 'public_key' , array ('client_id'=>$client_id, 'subject'=>$subject));
    }

    public function getClientScope($client_id)
    {
        if (!$clientDetails = $this->getClientDetails($client_id)) {
            return false;
        }

        if (isset($clientDetails['scope'])) {
            return $clientDetails['scope'];
        }

        return null;
    }

    public function getJti($client_id, $subject, $audience, $expiration, $jti)
    {
        //TODO: Needs cassandra implementation.
        throw new \Exception('getJti() for the Moodle driver is currently unimplemented.');
    }

    public function setJti($client_id, $subject, $audience, $expiration, $jti)
    {
        //TODO: Needs cassandra implementation.
        throw new \Exception('setJti() for the Moodle driver is currently unimplemented.');
    }

    /* PublicKeyInterface */
    public function getPublicKey($client_id = null)
    {
        global $DB;
        return $DB->get_field_select('oauth_public_keys', 'public_key' , 'client_id=:client_id OR client_id IS NULL', array('client_id'=>$client_id), 'client_id IS NOT NULL DESC');
    }

    public function getPrivateKey($client_id = null)
    {
        global $DB;
        return $DB->get_field_select('oauth_public_keys', 'private_key' , 'client_id=:client_id OR client_id IS NULL', array('client_id'=>$client_id), 'client_id IS NOT NULL DESC');
    }

    public function getEncryptionAlgorithm($client_id = null)
    {
        global $DB;
        $alg = $DB->get_field_select('oauth_public_keys', 'encryption_algorithm' , 'client_id=:client_id OR client_id IS NULL', array('client_id'=>$client_id), 'client_id IS NOT NULL DESC');
        if($alg){
            return $alg;
        }
        return 'RS256';
    }

    /**
     * DDL to create OAuth2 database and tables for PDO storage
     *
     * @see https://github.com/dsquier/oauth2-server-php-mysql
     */
    public function getBuildSql($notused = false)
    {
        $sql = "
        CREATE TABLE mdl_oauth_clients (
          client_id             VARCHAR(80)   NOT NULL,
          client_secret         VARCHAR(80)   NOT NULL,
          redirect_uri          VARCHAR(2000),
          grant_types           VARCHAR(80),
          scope                 VARCHAR(4000),
          user_id               VARCHAR(80),
          PRIMARY KEY (client_id)
        );

        CREATE TABLE mdl_oauth_access_tokens (
          access_token         VARCHAR(40)    NOT NULL,
          client_id            VARCHAR(80)    NOT NULL,
          user_id              VARCHAR(80),
          expires              TIMESTAMP      NOT NULL,
          scope                VARCHAR(4000),
          PRIMARY KEY (access_token)
        );

        CREATE TABLE mdl_oauth_authorization_codes (
          authorization_code  VARCHAR(40)    NOT NULL,
          client_id           VARCHAR(80)    NOT NULL,
          user_id             VARCHAR(80),
          redirect_uri        VARCHAR(2000),
          expires             TIMESTAMP      NOT NULL,
          scope               VARCHAR(4000),
          id_token            VARCHAR(1000),
          PRIMARY KEY (authorization_code)
        );

        CREATE TABLE mdl_oauth_refresh_tokens (
          refresh_token       VARCHAR(40)    NOT NULL,
          client_id           VARCHAR(80)    NOT NULL,
          user_id             VARCHAR(80),
          expires             TIMESTAMP      NOT NULL,
          scope               VARCHAR(4000),
          PRIMARY KEY (refresh_token)
        );

        CREATE TABLE mdl_oauth_scopes (
          scope               VARCHAR(80)  NOT NULL,
          is_default          BOOLEAN,
          PRIMARY KEY (scope)
        );

        CREATE TABLE mdl_oauth_jwt (
          client_id           VARCHAR(80)   NOT NULL,
          subject             VARCHAR(80),
          public_key          VARCHAR(2000) NOT NULL
        );

        CREATE TABLE mdl_oauth_public_keys (
          client_id            VARCHAR(80),
          public_key           VARCHAR(2000),
          private_key          VARCHAR(2000),
          encryption_algorithm VARCHAR(100) DEFAULT 'RS256'
        );

        CREATE TABLE {user} (
          username            VARCHAR(80),
          password            VARCHAR(80),
          first_name          VARCHAR(80),
          last_name           VARCHAR(80),
          email               VARCHAR(80),
          email_verified      BOOLEAN,
          scope               VARCHAR(4000)
        );

";

        return $sql;
    }
}
