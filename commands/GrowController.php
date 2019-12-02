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
	// Store current humididy and temperature
	public $cH = 0;
	public $cT = 0;
	public $cM = 0;

	/**
	 * 
	 */
	public function actionIndex()
	{
		#$this->actionOff();
		$this->fan(true);

		$count = -1;

		$climate = new \League\CLImate\CLImate;
		$climate->blue('Flytrap service started');

		// Main Loop
		while(true){

			// Reset
			$t = [];
			$h = [];
			$m = [];
			$ta = 0;
			$ha = 0;
			$ma = 0;
			$read = true;
			$readings = 0;

			while($read){

				// Get initial humidity, temperature & moisture
				list($readT, $readH) = explode(',', shell_exec('python /var/develop/ter/getHt.py'));
				$readM = shell_exec('python /var/develop/ter/getM.py');

				// Clean and format
				$cleanT = trim($readT);
				$cleanH = trim($readH);
				$cleanM = round(trim($readM),2) * 100;

				// Ignore out of range values for humidity and temperature (Dont know why this happens....?)
				if($cleanH > 150){sleep(1); continue;}
				if($cleanT < 15){sleep(1); continue;}

				// Clean
				$t[] = trim($cleanT);
				$h[] = trim($cleanH);
				$m[] = trim($cleanM);

				// Count three readings to get an average before continue
				if($readings < 3){
					//$climate->inline('.');
					$readings += 1;
					sleep(2);
				}
				else{
					//$climate->inline(PHP_EOL);
					$read = false;
				}
			}

			// Finished getting readings
			if(count($t)) {
				$t = array_filter($t);
				$ta = array_sum($t)/count($t);
				//$climate->blue('Average temperature is ' . $ta);
			}

			if(count($h)) {
				$h = array_filter($h);
				$ha = array_sum($h)/count($h);
				//$climate->blue('Average humidity is ' . $ha);
			}

			if(count($m)) {
				$m = array_filter($m);
				$ma = array_sum($m)/count($m);
				//$climate->blue('Average moisture is ' . $ma);
			}

			// If data changes, checkit
			if($ta != $this->cT or $ha != $this->cH or $ma != $this->cM){
				$this->cT = $ta;
				$this->cH = $ha;
				$this->cM = $ma;
				$this->checkHtm($ta, $ha, $ma);
			}

			$count += 1;
			$hour = 3*60;
			if($count == 0 or $count == $hour){
				$count = 1;
				$this->log($ta, $ha, $ma);
			}

			//$climate->inline(PHP_EOL);
			sleep(9);
		}

		// Done
		return ExitCode::OK;
	}

	/**
	 * Water for 60 seconds
	 */
	public function actionDrip($seconds = 60)
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
	private function checkHtm($t, $h, $m, $mode = 'flytrap')
	{
		$climate = new \League\CLImate\CLImate;

		// Get moisture requirement
		$moist = \Yii::$app->params['modes'][$mode]['moist'];
		if($m <= $moist){
			$this->water(true);
			$this->warm(false);
			$this->fan(false);
			sleep(15);
			return true; // Do another moisture check
		}

		// Find ideal Temp based on max and min values
		$minT = \Yii::$app->params['modes'][$mode]['temp']['min'];
		$maxT = \Yii::$app->params['modes'][$mode]['temp']['max'];

		// Drop temperature by 10 during the night
		$time = date("G");
		if ($time >= "18" && $time > "7") {
			$dayNight = 'Night mode';
			$maxT = $maxT - 10;
		}else{
			$dayNight = 'Day mode';
		}
		$avT  = ($minT + $maxT) / 2;

		// Find ideal Humidity based on max and min values
		$minH = \Yii::$app->params['modes'][$mode]['humi']['min'];
		$maxH = \Yii::$app->params['modes'][$mode]['humi']['max'];
		$avH  = ($minH + $maxH) / 2;

		// Convert to percentages and find worst key
		$avr = [
			round(($t / $avT) * 100),
			round(($h / $avH) * 100)
		];
		$key = array_keys($avr,min($avr));
		switch($key[0]){
			case 0 : $warm = true; break;
			case 1 : $warm = false; break;
		}

		$info = ('Humidity: '.$avr[1] . '% => ' . $h .'/'.$avH. ' | Temperature: '. $avr[0] . '% => ' . $t .'/'.$avT . ' | Moisture: '.$m);

		if($warm){
			$climate->green($info . ' (Warming, '.$dayNight.')');
		}else{
			$climate->cyan($info . ' (Cooling, '.$dayNight.')');
		}

		// Do it
		$this->warm($warm);
		
		// Done
		return true;
	}

	/**	
	 * Logs the current temp and humidity
	 */
	private function log($t, $h, $m)
	{
		// Create sqlite table if it does not exist
		$fileDb = new \PDO('sqlite:/var/develop/ter/htm.sqlite');
		$fileDb->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		$fileDb->exec('
			CREATE TABLE IF NOT EXISTS htm
			(
			`h` CHAR(90) NOT NULL,
			`t` CHAR(90) NOT NULL,
			`m` CHAR(90) NOT NULL,
			`ts` CHAR(100) NOT NULL
			)'
		);
		
		// Insert Humidity, temperature and timestamp
		$rows = [
			[
				'h' => $h,
				't' => $t,
				'm' => $m,
				'ts' => time()
			]
		];
		$insert = "INSERT INTO htm (h, t, m, ts) VALUES (:h, :t, :m, :ts)";
		$stmt = $fileDb->prepare($insert);
		$stmt->bindParam(':h',  $h);
		$stmt->bindParam(':t',  $t);
		$stmt->bindParam(':m',  $m);
		$stmt->bindParam(':ts', $ts);
		foreach ($rows as $r) {
			$h  = $r['h'];
			$t  = $r['t'];
			$m  = $r['m'];
			$ts = $r['ts'];
			$stmt->execute();
		}

		// Keep a text record of the latest values
		$latest = fopen("/var/develop/ter/web/ht.txt", "w") or die("Unable to open file!");
		fputcsv($latest, [$t, $h, $m]);

		// Done
		return true;
	}

	/**	
	 * Truncates the HT table
	 */
	public function actionTruncate()
	{
		$fileDb = new \PDO('sqlite:hts.sqlite');
		$fileDb->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		$fileDb->exec('DELETE FROM ht');
		
		// Done
		return ExitCode::OK;
	}

	/**
	 * Fan
	 * 
	 * Boolean True or false
	 */
	private function fan($v)
	{
		// Keep a text record of the latest values
		$fp = fopen("/var/develop/ter/web/vent.txt", "w") or die("Unable to open file!");

		if($v == true){
			fwrite($fp, '1');
			shell_exec('python /var/develop/ter/relay1On.py');
		}else{
			fwrite($fp, '0');
			shell_exec('python /var/develop/ter/relay1Off.py');
		}

		fclose($fp);

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
		
		// Keep a text record of the latest values
		$fp = fopen("/var/develop/ter/web/warm.txt", "w") or die("Unable to open file!");

		if($v == true){
			fwrite($fp, '1');
			shell_exec('python /var/develop/ter/relay2On.py');
		}else{
			fwrite($fp, '0');
			shell_exec('python /var/develop/ter/relay2Off.py');
		}

		fclose($fp);

		// Done
		return true;
	}

	/**
	 * Water
	 * 
	 * Boolean True or false
	 */
	private function water($v)
	{
		// Keep a text record of the latest values
		$fp = fopen("/var/develop/ter/web/water.txt", "w") or die("Unable to open file!");

		if($v == true){
			fwrite($fp, '1');
			shell_exec('python /var/develop/ter/relay3On.py');
		}else{
			fwrite($fp, '0');
			shell_exec('python /var/develop/ter/relay3Off.py');
		}

		fclose($fp);

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
