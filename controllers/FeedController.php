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
		$file_db = new \PDO('sqlite:/var/develop/ter/hts.sqlite');
		$file_db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		$result = $file_db->query('SELECT * FROM `ht`');
		$return = [];

		foreach ($result as $row) {
			$return[] = [
				$row['h'],
				$row['t'],
				date('d M H:i', $row['ts'])
			];
		}

		return $return;
	}

	public function actionVent()
	{
		if($p = \Yii::$app->request->post()){

			// Keep a text record of the latest values
			$fp = fopen("/var/develop/ter/web/vent.txt", "w") or die("Unable to open file!");

			// Do it
			if($p['onOff'] == 1){
				fwrite($fp, '1');
				shell_exec('python /var/develop/ter/relay1On.py');
			}else{
				shell_exec('python /var/develop/ter/relay1Off.py');
				fwrite($fp, '0');
			}

			fclose($fp);
		}
	}

	public function actionWarm()
	{
		// Keep a text record of the latest values
		$fp = fopen("/var/develop/ter/web/warm.txt", "w") or die("Unable to open file!");

		if($p = \Yii::$app->request->post()){
			if($p['onOff'] == 1){
				fwrite($fp, '1');
				shell_exec('python /var/develop/ter/relay2On.py');
			}else{
				fwrite($fp, '0');
				shell_exec('python /var/develop/ter/relay2Off.py');
			}
		}

		fclose($fp);
	}

	public function actionWater()
	{
		// Keep a text record of the latest values
		$fp = fopen("/var/develop/ter/web/water.txt", "w") or die("Unable to open file!");

		if($p = \Yii::$app->request->post()){
			if($p['onOff'] == 1){
				fwrite($fp, '1');
				shell_exec('python /var/develop/ter/relay3On.py');
			}else{
				fwrite($fp, '0');
				shell_exec('python /var/develop/ter/relay3Off.py');
			}
		}

		fclose($fp);
	}
}
