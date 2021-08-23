# Yii2 wallet module v2.0

#### include in ./config/web.php
```php
    [
    'bootstrap' => [           
           'wallet',            
        ],
    'modules' => [
           'wallet' => [           
                'class' => 'asmbr\wallet\Module',
                'modelMap' => [] 
            ]             
        ]
    ]
```

Command for create base migration module
 
`path/to/you/baseProject$ ./yii migrate/up --migrationPath=vendor/binary/yii2-wallet/migrations`

