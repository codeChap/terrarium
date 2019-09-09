<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class SiteController extends Controller
{
	/**
	 * {@inheritdoc}
	 */
	public function behaviors()
	{
		return [
			'access' => [
				'class' => AccessControl::className(),
				'only' => ['logout'],
				'rules' => [
					[
						'actions' => ['logout'],
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
			'verbs' => [
				'class' => VerbFilter::className(),
				'actions' => [
					'logout' => ['post'],
				],
			],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function actions()
	{
		return [
			'error' => [
				'class' => 'yii\web\ErrorAction',
			],
			'captcha' => [
				'class' => 'yii\captcha\CaptchaAction',
				'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
			],
		];
	}

	/**
	 * Displays homepage.
	 *
	 * @return string
	 */
	public function actionIndex()
	{
		if(($h = fopen(realpath('../').'/data.csv', 'r')) !== false){
			while (($data = fgetcsv($h, 1000, ',')) !== false){

				$temps[]    = [(int)$data[1]];
				$rTemps[]   = [30];

				$humid[]    = [(int)$data[2]];
				$minHumid[] = [80];

				$times[]    = [date('D H:i', $data[0])];

				$heating[]  = [$data[3]];
				$watering[] = [$data[4]];
			}
			fclose($h);
		}

		return $this->render('index', [
			'temps'    => isset($temps)    ? $temps    : 0,
			'rTemps'   => isset($rTemps)   ? $rTemps   : 0,
			'humid'    => isset($humid)    ? $humid    : 0,
			'minHumid' => isset($minHumid) ? $minHumid : 0,
			'times'    => isset($times)    ? $times    : 0,
			'heating'  => isset($heating)  ? $heating  : 0,
			'watering' => isset($watering) ? $watering : 0,
			'planted'  => 1561228460
		]);
	}

	/**
	 * Login action.
	 *
	 * @return Response|string
	 */
	public function actionLogin()
	{
		if (!Yii::$app->user->isGuest) {
			return $this->goHome();
		}

		$model = new LoginForm();
		if ($model->load(Yii::$app->request->post()) && $model->login()) {
			return $this->goBack();
		}

		$model->password = '';
		return $this->render('login', [
			'model' => $model,
		]);
	}

	/**
	 * Logout action.
	 *
	 * @return Response
	 */
	public function actionLogout()
	{
		Yii::$app->user->logout();

		return $this->goHome();
	}
}
