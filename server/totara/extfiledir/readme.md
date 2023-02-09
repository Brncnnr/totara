# External filedir stores

The main purpose of this plugin is to simplify reusing of filedir between different environments
such as when testing upgrades. The idea is to use a clone of database with empty dataroot
and point external filedir store at backup of production filedir, this eliminates unnecessary
copying of filedir files.

Another use case is to create a full historic backup of all file contents in external storage while
keeping the local filedir free of unreferenced content files. 


## Configuration

List of options:

* idnumber - the internal identifier of the store (only following characters are allowed [a-zA-Z0-9_])
* filedir - location of the file store
* directorypermissions - permissions used for newly crated files, defaults to $CFG->directorypermissions
* filepermissions - permissions used for newly created files, defaults to directorypermissions minus executable bits 
* add - true means new file contents will be added to external store
* delete - true means file contents will be deleted from external store when not used locally any more
* restore - true means use the external store contents to recover contents that are missing in local filedir
* active  - enable/disable switch for all store operations

The configuration is stored in /config.php file, admins may access an overview of configured
external file stores via a site administration page. 

Examples:
```
$CFG->totara_extfiledir_stores = [
    [
        'idnumber' => 'full_backup',
        'filedir' => '/path/to/external/backup/directory/',
        'directorypermissions' => 02777,
        'add' => true,
        'delete' => false,
        'restore' => true,
        'active' => true,
   ],
   [
       'idnumber' => 'shared_storage',
        'filedir' => '/path/to/directory/shared/by/all/web/servers/',
        'directorypermissions' => 02777,
        'filepermissions' => 0666,
        'add' => true,
        'delete' => true,
        'restore' => true,
        'active' => true,
   ],
   [
       'idnumber' => 'production',
       'description' => 'File recovered for this dev site',
        'filedir' => '/path/to/production/filedir/',
        'add' => false,
        'delete' => false,
        'restore' => true,
        'active' => true,
    ],
];
```


## Bulk file sync

This plugin does not implement tool for initial upload of filedir contents to external stores,
please use regular file copy and sync tools from your OS.


## CLI script for maintenance of local filedir

There is a new CLI script for maintenance of local filedir directory, see /admin/cli/check_filedir.php.

Expected use cases:

1. Consistency check of all content files in local filedir.
2. Restoring of missing content files from external stores.
3. Deleting of orphaned files from local filedir.
