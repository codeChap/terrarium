<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;
use PhpGpio\Gpio;

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class GrowController extends Controller
{
	/**
	 * This command echoes what you have entered as the message.
	 * @param string $message the message to be echoed.
	 * @return int Exit code
	 */
	public function actionIndex($mode = 'flytrap')
	{
		// Get Temperature and Humidity
		list($t, $h) = explode(',', shell_exec('python /var/develop/ter/getHt.py'));

		// Min temperature and humidity
		$t = trim($t);
		$h = trim($h);
		$mT = Yii::$app->modes[$mode][0];
		$mH = Yii::$app->modes[$mode][1];

		// Temperature
		if($t < $mT){
			$this->heat(true);
			$heating = 1;
		}
		else{
			$this->heat(false);
			$heating = 0;
		}

		// Humidity
		if($h < $mH){
			$this->water(true);
			$watering = 1;
		}
		else{
			$this->water(false);
			$watering = 0;
		}

		// Log the data
		$file = fopen("/var/develop/ter/data.csv", "a") or die("Unable to open file!");
		fputcsv($file, [time(), $t, $h, $heating, $watering]);

		return ExitCode::OK;
	}

	/**
	 * Turn off everything
	 */
	public function actionOff()
	{
		// Turn all off
		$this->heat(false);
		$this->water(false);
	}

	/**
	 * Increase temp
	 *
	 * Boolean True or false
	 */
	private function heat($v)
	{
		$this->gpioHeat = new GPIO();
		$this->gpioHeat->setup(17, "out");

		if($v == true){
			$this->gpioHeat->output(17, 0);

		}else{
			$this->gpioHeat->output(17, 1);
		}
	}

	/**
	 * Water
	 */
	private function water($v)
	{
		$this->gpioWater = new GPIO();
		$this->gpioWater->setup(2, "out");

		if($v == true){
			$this->gpioWater->output(2, 0);

		}else{
			$this->gpioWater->output(2, 1);
		}
	}
}
