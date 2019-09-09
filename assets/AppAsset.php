<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Main application asset bundle.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
	public $basePath = '@webroot';
	public $baseUrl = '@web';
	public $css = [
		'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.css',
		'css/semantic.min.css',
		'css/site.css',
	];
	public $js = [
		'js/jquery.min.js',
		'js/vue.min.js',
		'js/semantic.min.js',
		'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.bundle.min.js',
		'js/actions.js'
	];
	public $depends = [
		'yii\web\YiiAsset',
	];
}
