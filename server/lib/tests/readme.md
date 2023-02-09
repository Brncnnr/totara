# LDAP configuration for PHPUnit testing of ldaplib

## Install OpenLDAP via docker

```
docker run -p 127.0.0.1:389:389 -p 127.0.0.1:636:636 --name openldap --detach osixia/openldap:1.3.0
```

More information: https://github.com/osixia/docker-openldap


## Add test settings to config.php

```
define('TEST_LDAPLIB_HOST_URL', 'ldap://127.0.0.1:389');
define('TEST_LDAPLIB_BIND_DN', 'cn=admin,dc=example,dc=org');
define('TEST_LDAPLIB_BIND_PW', 'admin');
define('TEST_LDAPLIB_DOMAIN', 'dc=example,dc=org');
```


## Execute tests

```
./vendor/bin/phpunit core_ldaplib_testcase
```
