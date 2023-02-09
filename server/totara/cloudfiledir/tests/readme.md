# Cloud filedir storage testing

Configuration for manual site testing is stored in main config.php file.


## Installation of PHP libraries

Before using this plugin optional libraries must be installed via composer in /libraries/ directory.


## Amazon S3 compatible MinIO test setup

1. install minio ``docker run -it --name minio -p 127.0.0.1:9000:9000 minio/minio server /data``
2. copy credentials and login to admin UI in web browser: http://127.0.0.1:9000
3. create test buckets and change permissions to read+write
4. define PHPUnit test configuration constants in config.php or phpunit.xml
5. add $CFG->totara_cloudfiledir_stores store configurations
6. install composer libraries into totara/cloudfiledir/lib/vendor
   or specify alternative SDK library path in TOTARA_CLOUDFILEDIR_S3_AUTOLOAD constant


## Azure Blob storage dev emulator

1. install Azurite: ``docker run --name=azurite -p 127.0.0.1:10000:10000 mcr.microsoft.com/azure-storage/azurite azurite-blob --blobHost 0.0.0.0 --blobPort 10000``
2. install UI https://azure.microsoft.com/en-us/features/storage-explorer/
3. create test buckets
4. define PHPUnit test configuration constants in config.php or phpunit.xml
5. add $CFG->totara_cloudfiledir_stores store configurations
6. install composer libraries into totara/cloudfiledir/lib/vendor
   or specify alternative SDK library path in TOTARA_CLOUDFILEDIR_AZURE_AUTOLOAD constant

NOTE: credentials are hardcoded in Azurite image


## Examples

```
$CFG->totara_cloudfiledir_stores = [
    [
       'idnumber' => 'shared_storage',
        'provider' => 's3',
        'bucket' => 'shared',
        'options' => [
            'endpoint' => 'http://127.0.0.1:9000',
            'credentials' => ['key' => '7S0J0QJ1UFMGHL6L5OWW', 'secret' => 'iQ2tZln6fGhSlND8vngTB8KYw0Z2srSQ9nyzHY4a'], // Copy values from Docker console
        ],
        'add' => true,
        'delete' => true,
        'restore' => true,
        'active' => true,
    ],
    [
       'idnumber' => 'persistent_backup',
        'provider' => 's3',
        'bucket' => 'backup',
        'options' => [
            'endpoint' => 'http://127.0.0.1:9000',
            'credentials' => ['key' => '7S0J0QJ1UFMGHL6L5OWW', 'secret' => 'iQ2tZln6fGhSlND8vngTB8KYw0Z2srSQ9nyzHY4a'], // Copy values from Docker console
        ],
        'add' => true,
        'delete' => false,
        'restore' => false,
        'active' => true,
        'maxinstantuploadsize' => 0,
    ],
    [
       'idnumber' => 'shared_alternative',
        'provider' => 'azure',
        'bucket' => 'shared',
        'options' => [
            'DefaultEndpointsProtocol' => 'http',
            'AccountName' => 'devstoreaccount1', // Default for Azurit Docker image
            'AccountKey' => 'Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==', // Default for Azurite Docker image
            'BlobEndpoint' => 'http://127.0.0.1:10000/devstoreaccount1',
        ],
        'add' => true,
        'delete' => true,
        'restore' => true,
        'active' => true,
    ],
];
```

```
define('TEST_CLOUDFILEDIR_S3_BUCKET', 'phpunitbucket');
define('TEST_CLOUDFILEDIR_S3_OPTIONS', json_encode([
    'endpoint' => 'http://127.0.0.1:9000',
    'credentials' => ['key' => 'CE8N6PD1E43LACA3BJ42', 'secret' => 'sA19X+rIkJstT3ge42pa4HDBu0Yf8ndiGkV+VBZU'], // Copy values from Docker console
]));
```

```
define('TEST_CLOUDFILEDIR_AZURE_BUCKET', 'phpunitbucket');
define('TEST_CLOUDFILEDIR_AZURE_OPTIONS', json_encode([
    'DefaultEndpointsProtocol' => 'http',
    'AccountName' => 'devstoreaccount1',
    'AccountKey' => 'Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==',
    'BlobEndpoint' => 'http://127.0.0.1:10000/devstoreaccount1',
]));
```