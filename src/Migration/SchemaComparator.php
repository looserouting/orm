<?php
namespace Orm\Migration;

/**
 * Vergleicht zwei Schemata (alt/neu) und liefert Differenzen für Migrationen.
 * Nur einfache Fälle: neue/entfernte Tabellen und Spalten.
 */
class SchemaComparator
{
    /**
     * @param array $oldSchema
     * @param array $newSchema
     * @return array ['up' => [...], 'down' => [...]]
     */
    public function compare(array $oldSchema, array $newSchema): array
    {
        $up = [];
        $down = [];

        // Tabellen vergleichen
        $oldTables = array_keys($oldSchema);
        $newTables = array_keys($newSchema);

        // Neue Tabellen (nur in neu)
        foreach (array_diff($newTables, $oldTables) as $table) {
            $up[] = ['action' => 'create_table', 'table' => $table, 'columns' => $newSchema[$table]['columns']];
            $down[] = ['action' => 'drop_table', 'table' => $table];
        }

        // Entfernte Tabellen (nur in alt)
        foreach (array_diff($oldTables, $newTables) as $table) {
            $up[] = ['action' => 'drop_table', 'table' => $table];
            $down[] = ['action' => 'create_table', 'table' => $table, 'columns' => $oldSchema[$table]['columns']];
        }

        // Gemeinsame Tabellen: Spalten vergleichen
        foreach (array_intersect($oldTables, $newTables) as $table) {
            $oldCols = array_keys($oldSchema[$table]['columns']);
            $newCols = array_keys($newSchema[$table]['columns']);

            // Neue Spalten
            foreach (array_diff($newCols, $oldCols) as $col) {
                $up[] = ['action' => 'add_column', 'table' => $table, 'column' => $col, 'definition' => $newSchema[$table]['columns'][$col]];
                $down[] = ['action' => 'drop_column', 'table' => $table, 'column' => $col];
            }

            // Entfernte Spalten
            foreach (array_diff($oldCols, $newCols) as $col) {
                $up[] = ['action' => 'drop_column', 'table' => $table, 'column' => $col];
                $down[] = ['action' => 'add_column', 'table' => $table, 'column' => $col, 'definition' => $oldSchema[$table]['columns'][$col]];
            }

            // Geänderte Spalten (nur Typ/Nullable, keine Constraints)
            foreach (array_intersect($oldCols, $newCols) as $col) {
                $oldDef = $oldSchema[$table]['columns'][$col];
                $newDef = $newSchema[$table]['columns'][$col];
                if ($oldDef != $newDef) {
                    $up[] = ['action' => 'modify_column', 'table' => $table, 'column' => $col, 'definition' => $newDef];
                    $down[] = ['action' => 'modify_column', 'table' => $table, 'column' => $col, 'definition' => $oldDef];
                }
            }
        }

        return ['up' => $up, 'down' => $down];
    }
}