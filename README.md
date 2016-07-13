Yii2 Giixer
===========

Extended gii module for Yii2 including a bunch of useful handler, helpers, traits
and other components. This module generates required models, controllers and other
classes with dependency on own components. Default yii-gii generator is not available 
when your are usint yii2-giiixer module.

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```bash
$ composer require dlds/yii2-giixer
```

or add

```
"dlds/yii2-giixer": "~3.0.0"
```

to the `require` section of your `composer.json` file.

## Migration

There is not any migration required to run. Module itself does not store any data in DB.

## Structure

Giixer defines its own easy to maintain and extendable application structure which
is sligtly different from default (gii generated) structure. Below you can find 
what all and how giixer generates.

### ActiveRecords

Giixer uses its own ActiveRecords (AR) strucutre. Below are all 4 ARs 
with descriptions about what they stand for. Each is placed on notional level as in application.

1. **Base AR**
    * Top level and not editable AR
    * Maintained only by giixer itself.
    * Always extends **GxActiveRecord**
    * Manual changes are lost after next giixer generation
    * File is placed in `common\models\db\base` or `common\modules\modulename\models\db\base`
2. **Common AR**
    * Extends **Base AR**
    * Editable and maintained by developer
    * Manual changes **are not** lost after any giixer generation
    * File is placed in `common\models\db` or `common\modules\modulename\models\db`
3. **Frontend/Backend AR**
    * Extends **Common AR**
    * Low level AR 
    * Editable and maintained by developer
    * Lie in separate application scopes `frontend` or `backend`
    * Only these models can be directly used by application
    * Namespaces are usually `app\models\db` or `app\modules\modulename\models\db` 
    * Files are placed in corresponding location to their namespaces with `app` replaced by `frontend` or `backend`

> Structure schema is shown in [this diagram](https://github.com/dlds/yii2-giixer/blob/master/docs/schemas/ar_structure.png)

This AR model structure gives you opportunity to easily update your DB schema
and still be able to regenerate your AR models without loosing your current code changes.

Because of same namespace for backend and frontend AR you can easily move some application logic to common scope
and avoid code duplication while AR models will be still found.

> Some additional features stored in **Base AR** is shown in [MyModel](https://github.com/dlds/yii2-giixer/blob/master/docs/classes/models/MyModel.php)

### ActiveQueries

Each AR model is generated with its custom ActiveQuery class which is assigned to **Base AR**.

Giixer creates following 3 ActiveQuery (AQ) classes during ARs generation.

1. **Common AQ**
    * Extends `\yii\db\ActiveQuery`
    * Editable and maintained by developer
    * File is placed in `common\models\db\base` or `common\modules\modulename\models\db\base`
2. **Frontend/Backend AQ**
    * Low level AQ which extends **Common AQ**
    * Editable and maintained by developer
    * Always loaded in **Base AR** (Only these can be directly used by application)
    * Namespaces are usually `app\models\db\query` or `app\modules\modulename\models\db\query` 
    * Files are placed in corresponding location to their namespaces with `app` part replaced by `frontend` or `backend`

Base AR will automatically loads appropriate AQ based on current application scope even 
both low level AQs have same namespace. That is because frontend application 
does not have access to backend application scope and vice versa.

> Structure schema is shown in [this diagram](https://github.com/dlds/yii2-giixer/blob/master/docs/schemas/aq_structure.png)

Giixer generated default AQ logic stored in **Common AQ** to be able to easily use appropriate AR model class in custom queries.

> Default AQ logic is show in [MyModelQuery](https://github.com/dlds/yii2-giixer/blob/master/docs/classes/models/MyModelQuery.php)

### Helpers

Giixer defines bunch of useful helpers. Below you can see which helpers giixer offers.

#### URL Helpers

Own url route and rule helpers are generated for each AR model.

Giixer creates following 2 helpers (HLP) for both `backend` and `frontend` application.

1. **Route HLP**
    * Extends `\dlds\giixer\components\helpers\GxRouteHelper` or your custom class set in `helperRouteBaseClass` (see **Configuration** section)
    * Contains all default routes generated by giixer
    * Directly used by application
    * Frontend file is placed in `frontend\components\helpers\url\routes` or `frontend\modules\modulename\components\helpers\url\routes`
    * Backend file is placed in `backend\components\helpers\url\routes` or `backend\modules\modulename\components\helpers\url\routes`
2. **Rule HLP**
    * Extends `\dlds\giixer\components\helpers\GxUrlRuleHelper` or your custom class set in `helperRuleBaseClass` (see **Configuration** section)
    * Contains rules for all giixer generated routes
    * Usually used in url rules configuration
    * Frontend file is placed in `frontend\components\helpers\url\rules` or `frontend\modules\modulename\components\helpers\url\rules`
    * Backend file is placed in `backend\components\helpers\url\rules` or `backend\modules\modulename\components\helpers\url\rules`

Main idea of both helpers is to encapsulate rules/routes in single class and provide developer with easy interface for using it.

> Basic route and rule helpers examples: [MyBasicRouteHelper](https://github.com/dlds/yii2-giixer/blob/master/docs/classes/url/MyBasicRouteHelper.php), [MyBasicUrlRuleHelper](https://github.com/dlds/yii2-giixer/blob/master/docs/classes/url/MyBasicUrlRuleHelper.php)

To be able to have translatable url slug defined in translation files (i18n) you have to load your rules in application bootstrap like bellow.
Otherwise translation will not work. See [Yii2 Adding Rules Dynamically](http://www.yiiframework.com/doc-2.0/guide-runtime-routing.html#adding-rules)

```
class AppBootstrapHandler implements \yii\base\BootstrapInterface {

    public function bootstrap($app)
    {
        return $this->addMainRules($app);
    }

    /**
     * Adds main application rules
     */
    protected function addMainRules(\yii\web\Application $app)
    {
        $rules = require(\Yii::getAlias('@frontend/config/url/rules.php'));

        return $app->getUrlManager()->addRules($rules, false);
    }
}
```

Where `@frontend/config/url/rules.php` should look like below.

```
$rules = [
    // ...
    MyBasicUrlRuleHelper::index(),
    MyBasicUrlRuleHelper::view(),
    // ...
];

return array_merge(
    $rules, require(__DIR__.'/rules-local.php')
);
?>
```

Than in application config you just set your AppBootstrapHandler to be processed.

```
// ...
'bootstrap' => [
    '\frontend\components\handlers\AppBootstrapHandler',
    // ...
]    
// ...
```

**TIP:** Usually you want to have better looking urls without model primary key shown in it. For instance you have own CMS system
where each of your `Post` model has its own slug looks like `my-custom-post-title` and you want to have final url be
like `http://www.mydomain.com/my-custom-post-title/`. For this case you have to update your **UrlRuleHelper** class according to [MyAdvancedRuleHelper](https://github.com/dlds/yii2-giixer/blob/master/docs/classes/url/MyAdvancedUrlRuleHelper.php)

#### Model Helper

This helper defines methods used in **GxActiveRecord** class. There are methods for adapting query params to pass mass assignemnt, methods to easily change validation rules or scenario definitions.

> For more information see [GxModelHelper](https://github.com/dlds/yii2-giixer/blob/master/src/components/helpers/GxModelHelper.php)

#### Other Helpers

Giixer defines a few another helper class which is more or less complex and useful in basic manipulation with application. Each helper has it own well-documented class.

> For more information see [Giixer Helpers](https://github.com/dlds/yii2-giixer/blob/master/src/components/helpers)

### Handlers

Giixer defines two main handler class useful for controllers to keep its methods lean and well-arranged.

1. **GxCrudHandler**
    * Defines its own approach of Create, Read, Update, Delete methods
    * Invokes **GxCrudEvent** during each action which holds all data about action result
    * Usually used for manipulationg standart AR model classes and their appropriate DB entries
2. **GxHandler**
    * Defines its own approach to validate model and based on validation result processes appropriate callback
    * Usually used for non AR models manipulation when save() means creating multiple ARs etc...

> For more information see [Giixer Handlers](https://github.com/dlds/yii2-giixer/blob/master/src/components/handlers)

## Configuration

Enable gii module in your config file by adding it to app bootstrap.

```
$config['bootstrap'][] = 'gii';
```

Replace default gii module class with giixer one.

```
$config['modules']['gii'] = [
    'class' => 'dlds\giixer\Module',
];
```

You can also modify giixer module behavior to your requirements by 
setting additional config options. See bellow.

#### `namespaces` option

Defines namespaces map to generated classes. This is useful if the namespace
for some class does not match the default one. For instance if your application
is divided into modules and you need to generate classes for these modules.

```
[
    '^ModA[a-zA-Z]+Form$' => 'app\\modules\\moda\models\\forms'
    '^ModB[a-zA-Z]+Form$' => 'app\\modules\\modb\\models\\forms'
    '^ModA[a-zA-Z]+Search$' => 'app\\modules\\moda\\models\\db\\search',
    '^ModB[a-zA-Z]+Search$' => 'app\\modules\\modb\\models\\db\\search',
]
```

Regex is used as array keys and required namespace is used for array values. 
Giixer than use appropriate namespaces for matched class names and generates
its files in path corresponding namespace.

---

#### `bases` option

Defines base classes for specific components, controllers and other application classes. It is based on regex of descendant class and fully qualified name of base class.

```
[
	dlds\giixer\Module::BASE_CONTROLLER_BACKEND => [
    	'^ModA[a-zA-Z]+Controller$' => 'backend\\modules\\edu\\controllers\\base\\EduBaseController',
		'^Shop[a-zA-Z]+Controller$' => 'backend\\modules\\shop\\controllers\\base\\ShopBaseController',
	],
	dlds\giixer\Module::BASE_CONTROLLER_FRONTEND => [
		'^Edu[a-zA-Z]+Controller$' => 'frontend\\modules\\edu\\controllers\\base\\EduBaseController',
		'^Shop[a-zA-Z]+Controller$' => 'frontend\\modules\\shop\\controllers\\base\\ShopBaseController',
	],
	dlds\giixer\Module::BASE_URL_ROUTE_HELPER => 'common\\components\\helpers\\url\\UrlRouteHelper',
	dlds\giixer\Module::BASE_URL_RULE_HELPER => 'common\\components\\helpers\\url\\UrlRuleHelper',
	dlds\giixer\Module::BASE_ELEMENT_HELPER_BACKEND => [
		'^Edu[a-zA-Z]+Helper$' => 'backend\\modules\\edu\\components\\helpers\\EduElementHelper',
		'^Shop[a-zA-Z]+Helper' => 'backend\\modules\\shop\\components\\helpers\\ShopElementHelper',
	],
	dlds\giixer\Module::BASE_ELEMENT_HELPER_FRONTEND => [
		'^Edu[a-zA-Z]+Helper$' => 'frontend\\modules\\edu\\components\\helpers\\EduElementHelper',
		'^Shop[a-zA-Z]+Helper' => 'frontend\\modules\\shop\\components\\helpers\\ShopElementHelper',
	],
]	
```

Above you can see configuration for two application modules Edu and Shop which both has custom base controller classes, UrlRoute and UrlRule base class and Helper class.

> If custom controller (frontend or backend) base class is specified it must extends **GxController**. Otherwise the **GxController** will be used directly as parent class. (**GxController** extends default `\yii\web\Controller`).

> If custom route helper base class is set it must extends **GxRouteHelper**.
Otherwise the **GxRouteHelper** will be used directly as parent class.

> If custom route helper base class is set it must extends **GxUrlRuleHelper**.
Otherwise the **GxUrlRuleHelper** will be used directly as parent class.

---

#### `translations` option

Defines which translations files should be automatically generated. This option is 
defined by array containing languages codes.

```
['en', 'de', 'cs']
```

For above the english, german and czech translations files will be generated.

---

#### `messages` option

Defines custom translations categories.

```
[
	'dynagrid' => [
 		'^Edu[a-zA-Z]+$' => 'edu/dynagrid	',
		'^Shop[a-zA-Z]+$' => 'shop/dynagrid',
    ],
],
```

For above generator use category 'edu/dynagrid' instead of 'dynagrid' everywhere in class which match regex '^Edu[a-zA-Z]+$'.

---

> For more information see [Giixer Module main class](https://github.com/dlds/yii2-giixer/blob/master/src/Module.php)

Copyright 2016  &copy; Digital Deals s.r.o.