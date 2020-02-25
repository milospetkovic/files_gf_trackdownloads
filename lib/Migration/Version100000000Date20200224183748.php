<?php

declare(strict_types=1);

namespace OCA\FilesGFTrackDownloads\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version100000000Date20200224183748 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        $table = $schema->getTable('share');

        $elbNewColumn = 'elb_share_for_user_group';

        if (!$table->hasColumn($elbNewColumn)) {
            $table->addColumn($elbNewColumn, 'bigint', [
                'notnull' => false,
                'length' => 20,
                'default' => null,
                'unsigned' => false
            ])->setComment('If share is assigned to user group then set flag when cron creates the share for each user in the user group');

            $table->addForeignKeyConstraint('oc_share', [$elbNewColumn], ['id'], ["onDelete" => "CASCADE"], 'frgk_elb_share_for_user_group');
        }

        return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
	}
}
