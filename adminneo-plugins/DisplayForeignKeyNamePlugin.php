<?php

namespace AdminNeo;

class DisplayForeignKeyNamePlugin extends Plugin
{
    protected $cache = [];

    public function renderFieldValue(string $table, array $field, array $row)
    {
        die('foo');
        // Only apply to foreign keys
        if (empty($field['foreign'])) {
            return null;
        }

        $value = $row[$field['field']];

        if ($value === null) {
            return '<i>NULL</i>';
        }

        // Parse foreign key target
        [$foreignTable, $foreignColumn] = explode('.', $field['foreign']);
        $cacheKey = md5("$foreignTable:$value");

        if (isset($this->cache[$cacheKey])) {
            return $this->wrapDisplay($value, $this->cache[$cacheKey]);
        }

        // Get fields from foreign table
        $columns = adminer()->fields($foreignTable);

        $displayColumn = null;
        foreach ($columns as $col) {
            if (preg_match('~char|text~i', $col['type'])) {
                $displayColumn = $col['field'];
                break;
            }
        }

        if (!$displayColumn) {
            return null; // No displayable column
        }

        // Fetch label value from foreign table
        $quotedValue = adminer()->dbh->quote($value);
        $query = "SELECT `$displayColumn` FROM `$foreignTable` WHERE `$foreignColumn` = $quotedValue LIMIT 1";

        $result = adminer()->dbh->query($query);
        if (!$result) return null;

        $label = null;
        if ($rowData = $result->fetch_assoc()) {
            $label = $rowData[$displayColumn];
        }

        $result->free();

        if (!$label) return null;

        $this->cache[$cacheKey] = $label;

        return $this->wrapDisplay($value, $label);
    }

    protected function wrapDisplay($original, $label)
    {
        $safeOriginal = htmlspecialchars($original);
        $safeLabel = htmlspecialchars($label);
        return "<span title='ID: $safeOriginal'><strong>$safeLabel</strong></span>";
    }
}
