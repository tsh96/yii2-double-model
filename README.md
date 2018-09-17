yii2-double-model-gii
=====================

This generator generates two ActiveRecord class for the specified database table. An empty one you can extend and a Base one which is the same as the original model generatior.

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
$ php composer.phar require --dev "prowebcraft/yii2-double-model": "dev-master"
```

or add

```
"prowebcraft/yii2-double-model": "dev-master"
```

to the ```require``` section of your `composer.json` file.

## Usage

By Default Extension comes with Bootstrap file. Just install it. If you need manual installation, follow these steps:

```php
//if your gii modules configuration looks like below:
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = 'yii\gii\Module';

//remove this two lines
```

```php
//Add this into common/config/main-local.php
    'bootstrap' => 'gii',
    'modules' => [
        'gii' => [
            'class' => 'yii\gii\Module',
            'generators' => [
                'doubleModel' => [
                    'class' => 'prowebcraft\yii2doublemodel\generators\model\Generator',
                ],
                'kartik-crud' => [
                    'class'     => 'prowebcraft\yii2doublemodel\generators\kcrud\Generator',
                ],
            ],
        ],
    ],
```

Open URL https://YOUR-YII2-INSTANCE/gii/doubleModel in browser and follow instructions
