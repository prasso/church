<?php

namespace Prasso\Church\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class ChurchModel extends Model
{
    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'mysql';
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table;
    
    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        if (! isset($this->table)) {
            // Get the class name without namespace
            $className = class_basename($this);

            // Convert to snake_case and pluralize properly
            $snakeCase = Str::snake($className);
            $plural = Str::plural($snakeCase);

            // Add prefix
            $this->table = 'chm_' . $plural;
        }
        
        return $this->table;
    }
}
