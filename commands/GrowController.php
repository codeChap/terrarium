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
	// Store current humididy, temperature, moisture and soil temperature
	public $cH = 0;
	public $cT = 0;
	public $cM = 0;
	public $cS = 0;

	/**
	 * 
	 */
	public function actionIndex()
	{
		// On startup turn eveything on and off vagain as a test
		/*
		shell_exec('python /var/develop/ter/relay1On.py');
		usleep(100);
		shell_exec('python /var/develop/ter/relay1Off.py');
		shell_exec('python /var/develop/ter/relay2On.py');
		usleep(100);
		shell_exec('python /var/develop/ter/relay2Off.py');
		shell_exec('python /var/develop/ter/relay3On.py');
		usleep(100);
		shell_exec('python /var/develop/ter/relay3Off.py');
		shell_exec('python /var/develop/ter/relay4On.py');
		usleep(100);
		shell_exec('python /var/develop/ter/relay4Off.py');
		*/

		$count = -1;

		$climate = new \League\CLImate\CLImate;
		$climate->blue('Flytrap service started');

		// Main Loop
		while(true){

			// Reset sensor data
			$t = [];
			$h = [];
			$m = [];
			$s = [];
			$ta = 0;
			$ha = 0;
			$ma = 0;
			$sa = 0;
			$read = true;
			$readings = 0;

			while($read){

				// Get initial humidity, temperature & moisture
				list($readT, $readH) = explode(',', shell_exec('python /var/develop/ter/getHt.py'));
				$readM = shell_exec('python /var/develop/ter/getM.py');
				$readS = shell_exec('python /var/develop/ter/getS.py');

				// Clean and format
				$cleanT = round(trim($readT));
				$cleanH = round(trim($readH));
				$cleanM = round(trim($readM),3) * 100;
				$cleanS = round(trim($readS));

				// Ignore out of range values for humidity and temperature (Dont know why this happens....?)
				if($cleanH > 150){sleep(1); continue;}
				if($cleanT < 15){sleep(1); continue;}

				// Clean
				$t[] = trim($cleanT);
				$h[] = trim($cleanH);
				$m[] = trim($cleanM);
				$s[] = trim($cleanS);

				// Count three readings to get an average before continue
				if($readings < 3){
					//$climate->inline('.');
					$readings += 1;
					sleep(1);
				}
				else{
					$read = false;
				}
			}

			if(count($h)) {
				$h = array_filter($h);
				$ha = array_sum($h)/count($h);
				//$climate->blue('Average humidity is ' . $ha);
			}

			// Finished getting readings
			if(count($t)) {
				$t = array_filter($t);
				$ta = array_sum($t)/count($t);
				//$climate->blue('Average temperature is ' . $ta);
			}

			if(count($m)) {
				$m = array_filter($m);
				$ma = array_sum($m)/count($m);
				//$climate->blue('Average moisture is ' . $ma);
			}

			if(count($s)) {
				$s = array_filter($s);
				$sa = array_sum($s)/count($s);
				//$climate->blue('Average soil temperature is ' . $sa);
			}

			// If data changes, checkit
			if(
				$ha != $this->cH or 
				$ta != $this->cT or 
				$ma != $this->cM or 
				$sa != $this->cS
			){
				$this->cH = $ha;
				$this->cT = $ta;
				$this->cM = $ma;
				$this->cS = $sa;
				$this->checkHtms($ha, $ta, $ma, $sa);
			}

			/*
			$count += 1;
			$hour = 3*60;
			if($count == 0 or $count == $hour){
				$count = 1;
				$this->log($ha, $ta, $ma, $sa);
			}
			*/

			//$climate->inline(PHP_EOL);
			sleep(9);
		}

		// Done
		return ExitCode::OK;
	}

	/**
	 * Water for x seconds
	 */
	public function actionDrip($seconds = 15)
	{
		$climate = new \League\CLImate\CLImate;
		$climate->green('Dripping for '.$seconds.'...');

		$this->water(true);
		sleep($seconds);
		$this->water(false);

		// Done
		return true;
	}

	/**
	 * Turn off everything
	 */
	public function actionOff()
	{
		// Turn all off
		$this->warm(false);
		$this->water(false);
		$this->fan(false);

		// Done
		return true;
	}

	// Day vs night mode
	function actionTest(){
		foreach([
			0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23
		] as $hour){

			$now     = $hour;
			$sunUp   = 7;
			$sunDown = 18;

			if ($now >= $sunUp xor $now <= $sunDown){
				$night = 1;
			}else{
				$night = 0;
			}

			print $hour . ': ' . ($night == 1 ? 'Night' : 'Day') . PHP_EOL;
		}
	}

	/**
	 * Logic responsible for growing plants based on temp and humidity
	 * 
	 * @param string $temperature
	 * @param string $humidity
	 * @param string $moisture
	 * @param string What we are growing
	 * 
	 * @return int Exit code
	 */
	private function checkHtms($h, $t, $m, $s, $mode = 'flytrap')
	{
		// Vars
		$warm = 0;
		$fan  = 0;
		$drip = 0;
		
		// Day vs night mode
		$hour    = Date("G");
		$sunUp   = 7;
		$sunDown = 18;
		if ($hour >= $sunUp xor $hour <= $sunDown){
			$night = 1;
		}else{
			$night = 0;
		}

		// Find ideal Temp based on max and min values
		$minT = \Yii::$app->params['modes'][$mode]['temp']['min'];
		$maxT = \Yii::$app->params['modes'][$mode]['temp']['max'];
		$avT  = ($minT + $maxT) / 2;
		$warm = ($t <= $avT) ? 1 : 0;

		// Find ideal Humidity based on max and min values
		$minH = \Yii::$app->params['modes'][$mode]['humi']['min'];
		$maxH = \Yii::$app->params['modes'][$mode]['humi']['max'];
		$avH  = ($minH + $maxH) / 2;
		$fan = ($h >= $avH) ? 1 : 0;

		// Find ideal Moisture based on max and min values
		$minM = \Yii::$app->params['modes'][$mode]['moist']['min'];
		$maxM = \Yii::$app->params['modes'][$mode]['moist']['max'];
		$avM  = ($minM + $maxM) / 2;
		if($avM <= $minM){
			if( ! $night){
				$drip = 1;
			}
		}

		// Determine action
		if($drip == 0){

			// Increase humidity
			if($h < $minH){
				$fan  = 0;
				$warm = 0;
			}

			// Drop humidity
			if($h > $maxH){
				$fan = 0;
				if($t < $maxT){
					$warm = 1;
				}
			}

			// No sun at night, but keep humidity lower
			if($night){
				$warm = 0;
				$fan = ($h >= $avH) ? 1 : 0;
			}
		}
		// Turn off eveything and water
		else{
			$fan  = 0;
			$warm = 0;
		}

		// Log it
		$this->log($h, $t, $m, $s, $fan, $warm, $drip);

		// Action it
		$this->fan($fan);
		$this->warm($warm);
		$this->drip($drip);

		// Allow water to seep in a little before meature again
		if($drip){
			sleep(60);
		}

		// Info it
		$climate = new \League\CLImate\CLImate;
		$climate->green('HTMS : ' . $h .'/'. $t .'/'. $m .'/'. $s);
		$climate->cyan('FW: ' . ($fan    ? 'On' : 'Off') .'/'. ($warm ? 'On' : 'Off'));

		// Done
		return true;
	}

	/**	
	 * Truncates the HT table
	 */
	public function actionTruncate()
	{
		\Yii::$app->db->createCommand()->truncateTable('htms')->execute();
		return ExitCode::OK;
	}

	public function actionGetM()
	{
		$readM = shell_exec('python /var/develop/ter/getM.py');
		print round(trim($readM),3) * 100;
	}

	private function log($h, $t, $m, $s, $f, $w, $d)
	{
		// Log readings to database
		$log = new \app\models\Htms();
		$log->h = $h;
		$log->t = $t;
		$log->m = $m;
		$log->s = $s;
		$log->d = $d;
		$log->f = $f;
		$log->w = $w;
		$log->save();

		// Keep a text record of the latest values
		$latest = fopen("/var/develop/ter/web/htms.txt", "w") or die("Unable to open file!");
		fputcsv($latest, [$h, $t, $m, $s, $d]);

		// Done
		return true;
	}

	/**
	 * Fan
	 * 
	 * Boolean True or false
	 */
	private function fan($v)
	{
		if($v == true){
			shell_exec('python /var/develop/ter/relay1On.py');
		}else{
			shell_exec('python /var/develop/ter/relay1Off.py');
		}

		// Done
		return true;
	}

	/**
	 * Temp
	 *
	 * Boolean True or false
	 */
	private function warm($v)
	{
		if($v == true){
			shell_exec('python /var/develop/ter/relay2On.py');
		}else{
			shell_exec('python /var/develop/ter/relay2Off.py');
		}

		// Done
		return true;
	}

	/**
	 * Water
	 * 
	 * Boolean True or false
	 */
	private function drip($v)
	{
		if($v == true){
			shell_exec('python /var/develop/ter/relay3On.py');
		}else{
			shell_exec('python /var/develop/ter/relay3Off.py');
		}

		// Done
		return true;
	}

	/**
	 * Leds
	 * 
	 * Boolean True or false
	 */
	private function led($v)
	{
		if($v == true){
			shell_exec('python /var/develop/ter/relay4On.py');
		}else{
			shell_exec('python /var/develop/ter/relay4Off.py');
		}

		// Done
		return true;
	}
}
