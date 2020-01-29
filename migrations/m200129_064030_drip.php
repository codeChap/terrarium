<?php

use yii\db\Migration;

/**
 * Class m200129_064030_drip
 */
class m200129_064030_drip extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		// Add column
		$this->addColumn('{{%htms}}', 'd', $this->integer(1));
	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		$this->dropColumn('{{%htms}}', 'd');
		return true;
	}
}
