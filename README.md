Yii service classes for RestApi
=========

Contains three layers:

  - Model to array serializer (ModelResource.php)
  - Container for simple arraay (Resource.php)
  - Json response class uses Resource type (HttpJsonResponse.php)


Sample
--------------

```php
$model = ModelClass::model()->findAll();
$response = (new HttpJsonResponse(new ModelResource($model)));
$response->send();
```