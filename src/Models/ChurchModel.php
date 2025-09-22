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
            $this->table = 'aph_' . str_replace(
                '\\', '', 
                str_replace('Prasso\\Church\\Models\\', '', get_class($this))
            );
            
            $this->table = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $this->table)) . 's';
        }
        
        return $this->table;
    }
}
