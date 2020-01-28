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
class Version100000000Date20200123120856 extends SimpleMigrationStep {

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
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable('share')) {
            $table = $schema->getTable('share');

            $elbCalendarObjectColumn = 'elb_calendar_object_id';

            if (!$table->hasColumn($elbCalendarObjectColumn)) {
                $table->addColumn($elbCalendarObjectColumn, 'bigint', [
                    'notnull' => false,
                    'length' => 20,
                    'default' => null,
                    'unsigned' => true
                ]);

                $table->addForeignKeyConstraint('oc_calendarobjects', [$elbCalendarObjectColumn], ['id'], ["onDelete" => "SET NULL"], 'frgk_calendarobjects_id');
            }
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
