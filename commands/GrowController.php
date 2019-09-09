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

	/**
	 * 
	 */
	public function actionIndex()
	{
		$climate = new \League\CLImate\CLImate;
		$climate->blue('Flytrap service started');

		while(true){

			// Do a quick moisture check first
			if(shell_exec('python /var/develop/ter/getM.py') == 1){
				sleep(1);
				if(shell_exec('python /var/develop/ter/getM.py') == 1){
					sleep(1);
					if(shell_exec('python /var/develop/ter/getM.py') == 1){
						$climate->green('Too dry, watering for 30s');
						$this->water(true);
						$this->warm(false);
						$this->fan(false);
						sleep(15);
						continue;
					}
				}
			}
			
			//$climate->blue('Moisture is good');
			$this->water(false);
			$this->fan(true);
			
			// Reset
			$t = [];
			$h = [];
			$ta = 0;
			$ha = 0;
			$read = true;
			$readings = 0;

			// Get initial humidity and temperature
			while($read){
				list($readT, $readH) = explode(',', shell_exec('python /var/develop/ter/getHt.py'));

				$cleanT = trim($readT);
				$cleanH = trim($readH);

				// Ignore out of range values
				if($cleanH > 150){sleep(1); continue;}
				if($cleanT < 15){sleep(1); continue;}

				// Clean
				$t[] = trim($cleanT);
				$h[] = trim($cleanH);

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

			// If data changes, log it
			if($ta != $this->cT or $ha != $this->cH){
				
				// Check and correct according to latest readings
				$this->checkHt($ta, $ha);
				
				// Log the readings only if changed
				$this->log($ta, $ha);
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
	 * @param string What we are growing
	 * 
	 * @return int Exit code
	 */
	private function checkHt($t, $h, $mode = 'flytrap')
	{
		$climate = new \League\CLImate\CLImate;

		// Find ideal Temp based on max and miin values
		$minT = \Yii::$app->params['modes'][$mode]['temp']['min'];
		$maxT = \Yii::$app->params['modes'][$mode]['temp']['max'];
		$avT  = ($minT + $maxT) / 2;

		// Find ideal Humidity based on max and miin values
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

		$info = ('Temperature: '. $avr[0] . '% => ' . $t .'/'.$avT . ' | Humidity: '.$avr[1] . '% => ' . $h .'/'.$avH);

		if($warm){
			$climate->green($info . ' (Warming)');
		}else{
			$climate->cyan($info . ' (Cooling)');
		}

		// Do it
		$this->warm($warm);
		
		// Done
		return true;
	}

	/**	
	 * Logs the current temp and humidity
	 */
	private function log($t, $h)
	{
		// Create sqlite table if it does not exist
		$fileDb = new \PDO('sqlite:hts.sqlite');
		$fileDb->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		$fileDb->exec('
			CREATE TABLE IF NOT EXISTS ht
			(
			`h` CHAR(90) NOT NULL,
			`t` CHAR(90) NOT NULL,
			`ts` CHAR(100) NOT NULL
			)'
		);
		
		// Insert Humidity, temperature and timestamp
		$rows = [
			[
				'h'  => $h,
				't'  => $t,
				'ts' => time()
			]
		];
		$insert = "INSERT INTO ht (h, t, ts) VALUES (:h, :t, :ts)";
		$stmt = $fileDb->prepare($insert);
		$stmt->bindParam(':h',  $h);
		$stmt->bindParam(':t',  $t);
		$stmt->bindParam(':ts', $ts);
		foreach ($rows as $r) {
			$h  = $r['h'];
			$t  = $r['t'];
			$ts = $r['ts'];
			$stmt->execute();
		}

		// Keep a text record of the latest values
		$latest = fopen("/var/develop/ter/web/ht.txt", "w") or die("Unable to open file!");
		fputcsv($latest, [$t, $h]);

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
