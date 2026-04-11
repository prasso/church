<?php

namespace Prasso\Church\Livewire;

use Livewire\Component;

class CleaningChecklist extends Component
{
    public $regularTasks = [
        "Vacuum auditorium carpet",
        "Vacuum nursery carpet", 
        "Vacuum runners in foyer",
        "Vacuum blue classroom",
        "Sweep and mop fellowship room",
        "Sweep and mop foyer and bathrooms",
        "Sweep and mop back door hallway",
        "Sweep and mop auditorium platform",
        "Sweep children's classroom",
        "Clean toilets in all four bathrooms",
        "Clean sinks in all four bathrooms",
        "Clean mirrors in bathrooms",
        "Sanitize baby changing table with Lysol spray",
        "Clean four glass doors and the nursery door",
        "Collect and empty trash in children's classroom",
        "Collect and empty trash from all four bathrooms",
        "Spray and wipe down tables and countertops",
        "Clean trash off of tables and pews",
    ];

    public $extraTasks = [
        "Sweep front porch and sidewalks if needed",
        "Vacuum pew seats",
        "Dust furniture",
        "Dust window sills", 
        "Clean glass in front of baptistry",
    ];

    public function render()
    {
        return view('church::livewire.cleaning-checklist');
    }
}
