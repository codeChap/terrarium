<?php

use yii\db\Migration;

/**
 * Class m200128_200440_init
 */
class m200128_200440_init extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		// Create tables
		$this->createTable('{{%htms}}', [
			'id'         => $this->primaryKey(11),
			'h'          => $this->float()->notNull(),
			't'          => $this->float()->notNull(),
			'm'          => $this->float()->notNull(),
			's'          => $this->float()->notNull(),
			'f'          => $this->integer(1)->notNull(),
			'w'          => $this->integer(1)->notNull(),
			'created_at' => $this->timestamp()->notNull()
		]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		$this->dropTable('{{%htms}}');
	}
}
