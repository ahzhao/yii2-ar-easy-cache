# yii2-ar-easycache
===================
Extended ActiveQuery to cache everything

##HOW TO USE
File:models/Some.php

```
namespace models;
use components\yii2ArEasyCache\ActiveRecordCacheTrait; 

class Some extends \yii\db\ActiveRecord{
	use ActiveRecordCacheTrait;//just add this line
	//if you want config the cache
	public static function cacheConfig()
    {
        return [
        	'cacheKeyPrefix' => 'ar-',
       		'cacheComponentName' => 'cache',
     	 	'cacheDuration' => 60,
        ];
    }
	......your code here.
}
```