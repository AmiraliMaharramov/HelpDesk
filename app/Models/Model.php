<?php
/**
 * QuickFixDesk — Base Model
 * Provides a static db() shorthand for all child models.
 */

abstract class Model
{
    protected static string $table = '';

    /**
     * Return the shared PDO connection via the global db() helper
     * that is registered by config/database.php.
     */
    protected static function db(): PDO
    {
        return db();
    }
}
