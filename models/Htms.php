<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "{{%htms}}".
 *
 * @property integer $id
 * @property string $h
 * @property string $t
 * @property string $m
 * @property string $s
 * @property string $updated_at
 * @property string $created_at
 */
class Htms extends \yii\db\ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%htms}}';
	}
}