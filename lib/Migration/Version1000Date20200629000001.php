<?php

declare(strict_types=1);

namespace OCA\Deck\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * @copyright Copyright (c) 2020, Giovanny Avila  (gjavilae@gmail.com)
 * 
 * add a new column named belongs_board_id for define
 * a hierarchy reflective relationship with itself
 */
class Version1000Date20200629000001 extends SimpleMigrationStep
{
    /**
     * @param IOutput $output
     * @param Closure $schemaClosure  The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();


        $table = $schema->getTable('deck_boards');

        $table->addColumn('belongs_board_id', 'integer', [
            'notnull' => false,
            'default' => null,
        ]);

        return $schema;
    }
}