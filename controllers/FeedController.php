<?php

namespace app\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\filters\AccessControl;
use yii\web\Response;
use PhpGpio\Gpio;

class FeedController extends ActiveController
{
	public $modelClass = 'app\models\User';

	public function actions()
	{
		$actions = parent::actions();

		// Disable these actions so that we can overwrite then below
		unset($actions['view'], $actions['update'], $actions['create']);

		// customize the data provider preparation with the "prepareDataProvider()" method
		//$actions['view']['prepareDataProvider'] = [$this, 'prepareDataProvider'];

		return $actions;
	}

	/**
	 * Returns the latest humidity and temperature readings
	 */
	public function actionRead()
	{
		$fileDb = new \PDO('sqlite:/var/develop/ter/htms.sqlite');
		$fileDb->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		$result = $fileDb->query('SELECT * FROM `htms`');
		$return = [];

		foreach ($result as $row) {
			$return[] = [
				$row['h'],
				$row['t'],
				$row['m'],
				$row['s'],
				date('d M H:i', $row['ts'])
			];
		}

		return $return;
	}

	public function actionVent()
	{
		if($p = \Yii::$app->request->post()){
			if($p['onOff'] == 1){
				shell_exec('python /var/develop/ter/relay1On.py');
			}else{
				shell_exec('python /var/develop/ter/relay1Off.py');
			}
		}
	}

	public function actionWarm()
	{

		if($p = \Yii::$app->request->post()){
			if($p['onOff'] == 1){
				shell_exec('python /var/develop/ter/relay2On.py');
			}else{
				shell_exec('python /var/develop/ter/relay2Off.py');
			}
		}
	}

	public function actionWater()
	{
		if($p = \Yii::$app->request->post()){
			if($p['onOff'] == 1){
				shell_exec('python /var/develop/ter/relay3On.py');
			}else{
				shell_exec('python /var/develop/ter/relay3Off.py');
			}
		}
	}
}
