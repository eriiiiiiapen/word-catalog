<?php

namespace App\Services;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;

class SqlSchemaParser
{
    public function parse(string $sql): array
    {
        $parser = new Parser($sql);
        $results = [];

        foreach ($parser->statements as $statement) {
            if ($statement instanceof CreateStatement) {
                $tableName = $statement->name->table;
                
                foreach ($statement->fields as $field) {
                    if ($field->name) {
                        $results[] = [
                            'table_name'  => $tableName,
                            'physical_name' => $field->name->column,
                            'logical_name'  => $field->options?->has('COMMENT') 
                                                ? trim($field->options->get('COMMENT'), "'")
                                                : '',
                        ];
                    }
                }
            }
        }
        return $results;
    }
}