<?php

namespace Prasso\Church\Models;

use Illuminate\Database\Eloquent\Model;

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
            
            // Convert to snake case
            $snakeCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
            
            // Add prefix and pluralize
            $this->table = 'chm_' . $snakeCase . 's';
        }
        
        return $this->table;
    }
}
